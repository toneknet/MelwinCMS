<?php

/** Small, curated source installer for hosts without Composer or shell access. */
class dependencies
{
    private $root;
    private $modules;
    private $loaded = [];
    private $loading = [];

    public function __construct(?string $pluginPath = null, ?string $modulePath = null)
    {
        $this->root = rtrim($pluginPath ?? dirname(__DIR__, 2) . '/resources/plugins', '/\\');
        $this->modules = $modulePath ?? dirname(__DIR__) . '/modules';
    }

    public static function catalog(?string $modulePath = null): array
    {
        $packages = $requirements = [];
        foreach (glob(rtrim($modulePath ?? dirname(__DIR__) . '/modules', '/\\') . '/*/config/dependencies.json') as $file) {
            $config = json_decode(file_get_contents($file), true);
            if (!is_array($config) || !is_array($config['require'] ?? null) || !is_array($config['packages'] ?? [])) throw new RuntimeException('Invalid dependency config: ' . $file);
            foreach ($config['require'] as $slug => $minimum) {
                self::validateRequirement($slug, $minimum);
                if (!isset($requirements[$slug]) || version_compare($minimum, $requirements[$slug], '>')) $requirements[$slug] = $minimum;
            }
            foreach ($config['packages'] ?? [] as $slug => $package) {
                self::validateRequirement($slug, '0.0.0');
                if (!is_array($package) || !is_string($package['name'] ?? null) || !filter_var($package['source'] ?? '', FILTER_VALIDATE_URL) || parse_url($package['source'], PHP_URL_SCHEME) !== 'https' || !is_array($package['releases'] ?? null) || !$package['releases']) throw new RuntimeException('Invalid package: ' . $slug);
                foreach ($package['releases'] as $version => $release) {
                    self::validateRequirement($slug, $version);
                    if (!is_array($release) || !is_array($release['files'] ?? null) || !$release['files'] || !filter_var($release['base'] ?? '', FILTER_VALIDATE_URL) || parse_url($release['base'], PHP_URL_SCHEME) !== 'https') throw new RuntimeException('Invalid release: ' . $slug);
                    self::validateRequirement($slug, $release['php'] ?? '7.4.0');
                    if (!is_array($release['require'] ?? []) || !is_array($release['extensions'] ?? [])) throw new RuntimeException('Invalid release requirements: ' . $slug);
                    foreach ($release['extensions'] ?? [] as $extension) if (!is_string($extension)) throw new RuntimeException('Invalid PHP extension: ' . $slug);
                    if (isset($release['archive']) && (!is_array($release['archive']) || !filter_var($release['archive']['url'] ?? '', FILTER_VALIDATE_URL) || parse_url($release['archive']['url'], PHP_URL_SCHEME) !== 'https' || !is_string($release['archive']['prefix'] ?? null) || !preg_match('~^[a-zA-Z0-9_.-]+/$~D', $release['archive']['prefix']))) throw new RuntimeException('Invalid archive: ' . $slug);
                    foreach ($release['autoload']['psr-4'] ?? [] as $prefix => $directory) {
                        if (!is_string($prefix) || !preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_]*\\\\)+$/D', $prefix) || !is_string($directory) || !preg_match('~^(?:[a-zA-Z0-9_-]+/)*$~D', $directory)) throw new RuntimeException('Invalid autoload namespace: ' . $slug);
                    }
                    foreach ($release['autoload']['files'] ?? [] as $autoloadFile) if (!is_string($autoloadFile) || !isset($release['files'][$autoloadFile])) throw new RuntimeException('Invalid autoload file: ' . $slug);
                    foreach ($release['files'] as $path => $hash) {
                        if (!preg_match('~^[a-zA-Z0-9_() .-]+(?:/[a-zA-Z0-9_() .-]+)*$~D', $path) || in_array('..', explode('/', $path), true) || in_array('.', explode('/', $path), true) || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) throw new RuntimeException('Invalid package file: ' . $slug);
                    }
                    foreach ($release['require'] ?? [] as $dependency => $minimum) self::validateRequirement($dependency, $minimum);
                    if (isset($packages[$slug]['releases'][$version]) && $packages[$slug]['releases'][$version] !== $release) throw new RuntimeException('Conflicting release: ' . $slug . ' ' . $version);
                    $packages[$slug]['releases'][$version] = $release;
                }
                $packages[$slug]['name'] = $package['name'];
                $packages[$slug]['source'] = $package['source'];
            }
        }
        $catalog = [];
        foreach ($packages as $slug => $package) {
            uksort($package['releases'], function ($a, $b) { return version_compare($b, $a); });
            foreach ($package['releases'] as $version => $release) {
                if (version_compare($version, $requirements[$slug] ?? '0.0.0', '<') || version_compare(PHP_VERSION, $release['php'] ?? '7.4.0', '<')) continue;
                if (array_filter($release['extensions'] ?? [], function ($ext) { return !extension_loaded($ext); })) continue;
                $catalog[$slug] = array_merge($release, ['name' => $package['name'], 'source' => $package['source'], 'version' => $version, 'minimum' => $requirements[$slug] ?? '0.0.0', 'releases' => $package['releases']]);
                break;
            }
            if (!isset($catalog[$slug])) {
                // Keep unavailable packages visible without blocking unrelated libraries.
                $version = array_key_first($package['releases']);
                $catalog[$slug] = array_merge($package['releases'][$version], ['name' => $package['name'], 'source' => $package['source'], 'version' => $version, 'minimum' => $requirements[$slug] ?? '0.0.0', 'releases' => $package['releases']]);
            }
            if (version_compare($catalog[$slug]['version'], $requirements[$slug] ?? '0.0.0', '<')) throw new RuntimeException('Unsatisfied minimum version: ' . $slug);
        }
        foreach ($requirements as $slug => $minimum) if (!isset($catalog[$slug])) throw new RuntimeException('Missing dependency: ' . $slug);
        // Validate the complete graph before any downloads or writes.
        $done = $active = [];
        $visit = function ($slug) use (&$visit, &$done, &$active, &$catalog) {
            if (isset($active[$slug])) throw new RuntimeException('Circular dependency: ' . $slug);
            if (isset($done[$slug])) return;
            $active[$slug] = true;
            foreach ($catalog[$slug]['require'] ?? [] as $dependency => $minimum) {
                if (!isset($catalog[$dependency]) || version_compare($catalog[$dependency]['version'], $minimum, '<')) throw new RuntimeException('Unsatisfied dependency: ' . $dependency);
                if (version_compare($minimum, $catalog[$dependency]['minimum'], '>')) $catalog[$dependency]['minimum'] = $minimum;
                $visit($dependency);
            }
            unset($active[$slug]);
            $done[$slug] = true;
        };
        foreach ($catalog as $slug => $package) $visit($slug);
        return $catalog;
    }

    private static function validateRequirement($slug, $version): void
    {
        if (!is_string($slug) || !preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $slug) || !is_string($version) || !preg_match('/^\d+\.\d+\.\d+$/D', $version)) throw new RuntimeException('Invalid dependency name or minimum version.');
    }

    private function package(string $slug): array
    {
        $catalog = self::catalog($this->modules);
        if (!isset($catalog[$slug])) throw new RuntimeException('Okänt beroende.');
        return $catalog[$slug];
    }

    public function status(string $slug): string
    {
        $package = $this->package($slug);
        $path = $this->root . '/' . $slug;
        if (!file_exists($path) && !is_link($path)) return 'Saknas';
        if (is_link($path) || !is_dir($path)) return 'Behöver åtgärdas';
        foreach ($package['releases'] as $version => $release) {
            if ($this->matches($path, $release['files'])) return version_compare($version, $package['version'], '>=') ? 'Installerad' : 'Uppdatering tillgänglig';
        }
        return 'Behöver åtgärdas';
    }

    public function installedVersion(string $slug): ?string
    {
        $package = $this->package($slug);
        $path = $this->root . '/' . $slug;
        if (!is_dir($path) || is_link($path)) return null;
        foreach ($package['releases'] as $version => $release) if ($this->matches($path, $release['files'])) return $version;
        return null;
    }

    private function matches(string $path, array $files): bool
    {
        foreach ($files as $file => $hash) {
            $parent = dirname($path . '/' . $file);
            while ($parent !== $path && strlen($parent) > strlen($path)) {
                if (is_link($parent)) return false;
                $parent = dirname($parent);
            }
            if (!is_file($path . '/' . $file) || is_link($path . '/' . $file)
                || !hash_equals($hash, hash_file('sha256', $path . '/' . $file))) return false;
        }
        return true;
    }

    public function install(string $slug): void
    {
        $package = $this->package($slug);
        $this->checkPlatform($package);
        foreach ($package['require'] ?? [] as $dependency => $minimum) $this->install($dependency);
        $this->installPackage($slug);
    }

    private function checkPlatform(array $package): void
    {
        if (version_compare(PHP_VERSION, $package['php'] ?? '7.4.0', '<')) throw new RuntimeException('PHP ' . $package['php'] . ' eller senare krävs.');
        foreach ($package['extensions'] ?? [] as $extension) {
            if (!extension_loaded($extension)) throw new RuntimeException('PHP-tillägget ' . $extension . ' saknas.');
        }
        if (isset($package['archive']) && !class_exists('ZipArchive')) throw new RuntimeException('PHP-tillägget zip krävs för detta paket.');
    }

    /** Load only verified direct installations and their declared runtime dependencies. */
    public function load(string $slug): void
    {
        if (isset($this->loading[$slug])) throw new RuntimeException('Circular runtime dependency: ' . $slug);
        if (isset($this->loaded[$slug])) return;
        $this->loading[$slug] = true;
        try {
            $package = $this->package($slug);
            $version = $this->installedVersion($slug);
            if ($version === null || version_compare($version, $package['minimum'], '<')) throw new RuntimeException('Beroendet ' . $slug . ' saknas eller behöver uppdateras. Öppna Beroenden i uppdateringsverktyget.');
            $release = $package['releases'][$version];
            foreach ($release['require'] ?? [] as $dependency => $minimum) {
                $installed = $this->installedVersion($dependency);
                if ($installed === null || version_compare($installed, $minimum, '<')) throw new RuntimeException('Underberoendet ' . $dependency . ' saknas eller behöver uppdateras.');
                $this->load($dependency);
            }
            $path = $this->root . '/' . $slug . '/';
            $namespaces = $release['autoload']['psr-4'] ?? [];
            spl_autoload_register(function ($class) use ($path, $namespaces) {
                foreach ($namespaces as $prefix => $directory) {
                    if (strpos($class, $prefix) !== 0) continue;
                    $relative = substr($class, strlen($prefix));
                    if (!preg_match('/^[a-zA-Z0-9_\\\\]+$/D', $relative)) return;
                    $file = $path . $directory . str_replace('\\', '/', $relative) . '.php';
                    if (is_file($file)) require_once $file;
                }
            });
            foreach ($release['autoload']['files'] ?? [] as $file) require_once $path . $file;
            $this->loaded[$slug] = true;
        } finally { unset($this->loading[$slug]); }
    }

    private function installPackage(string $slug): void
    {
        $package = $this->package($slug);
        if (version_compare(PHP_VERSION, $package['php'] ?? '7.4.0', '<')) throw new RuntimeException('PHP ' . $package['php'] . ' eller senare krävs.');
        foreach ($package['extensions'] ?? [] as $extension) {
            if (!extension_loaded($extension)) throw new RuntimeException('PHP-tillägget ' . $extension . ' saknas.');
        }
        if (!is_dir($this->root) || !is_writable($this->root)) throw new RuntimeException('resources/plugins måste vara skrivbar för PHP.');
        $lock = fopen($this->root . '/.dependency-install.lock', 'c');
        if (!$lock) throw new RuntimeException('Kunde inte öppna installationslåset.');
        $stage = null;
        $zip = null;
        $zipOpen = false;
        $archivePath = null;
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('En installation pågår. Försök igen strax.');
            if ($this->status($slug) === 'Installerad') return;
            $target = $this->root . '/' . $slug;
            $updating = $this->status($slug) === 'Uppdatering tillgänglig';
            if (!$updating && (file_exists($target) || is_link($target))) {
                throw new RuntimeException('Befintlig katalog avviker. Flytta resources/plugins/' . $slug . ' åt sidan via filhanteraren och försök igen.');
            }
            $stage = $this->root . '/.dependency-' . bin2hex(random_bytes(12));
            if (!mkdir($stage, 0755)) throw new RuntimeException('Kunde inte skapa installationskatalogen.');
            if (isset($package['archive'])) {
                $archivePath = $stage . '.zip';
                $data = $this->downloadArchive($package['archive']['url']);
                if (file_put_contents($archivePath, $data, LOCK_EX) !== strlen($data)) throw new RuntimeException('Kunde inte skriva paketarkivet.');
                unset($data);
                $zip = new ZipArchive();
                if ($zip->open($archivePath) !== true) throw new RuntimeException('Ogiltigt paketarkiv.');
                $zipOpen = true;
            }
            foreach ($package['files'] as $file => $hash) {
                if ($zip !== null) {
                    $entry = $package['archive']['prefix'] . $file;
                    $stat = $zip->statName($entry);
                    if (!$stat || $stat['size'] > 32 * 1024 * 1024) throw new RuntimeException('Ogiltig arkivfil: ' . $file);
                    $data = $zip->getFromName($entry);
                    if ($data === false) throw new RuntimeException('Kunde inte läsa arkivfil: ' . $file);
                } else {
                    $data = $this->download($package['base'] . implode('/', array_map('rawurlencode', explode('/', $file))));
                }
                if (!hash_equals($hash, hash('sha256', $data))) throw new RuntimeException('Kontrollsumman stämmer inte för ' . $file . '. Inga filer har aktiverats.');
                $directory = dirname($stage . '/' . $file);
                if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Kunde inte skapa katalog.');
                if (file_put_contents($stage . '/' . $file, $data, LOCK_EX) !== strlen($data)) throw new RuntimeException('Kunde inte skriva ' . $file . '.');
            }
            $backup = $updating ? $this->root . '/.dependency-backup-' . $slug . '-' . bin2hex(random_bytes(8)) : null;
            if ($backup !== null && !rename($target, $backup)) throw new RuntimeException('Kunde inte säkerhetskopiera beroendet.');
            if (!rename($stage, $target)) {
                if ($backup !== null) rename($backup, $target);
                throw new RuntimeException('Kunde inte aktivera beroendet.');
            }
            $stage = null;
        } finally {
            if ($zipOpen) $zip->close();
            if ($archivePath !== null && is_file($archivePath)) unlink($archivePath);
            // Only remove the known files in our own random staging directory.
            if ($stage !== null && is_dir($stage)) {
                $directories = [];
                foreach ($package['files'] as $file => $hash) {
                    if (is_file($stage . '/' . $file)) unlink($stage . '/' . $file);
                    $directory = dirname($stage . '/' . $file);
                    while ($directory !== $stage) { $directories[$directory] = true; $directory = dirname($directory); }
                }
                uksort($directories, function ($a, $b) { return strlen($b) <=> strlen($a); });
                foreach ($directories as $directory => $unused) if (is_dir($directory)) rmdir($directory);
                rmdir($stage);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function download(string $url): string
    {
        return $this->downloadUrl($url, 2 * 1024 * 1024, 6);
    }

    protected function downloadArchive(string $url): string
    {
        return $this->downloadUrl($url, 64 * 1024 * 1024, 120);
    }

    private function downloadUrl(string $url, int $limit, int $timeout): string
    {
        // URLs are supplied only by the catalog; never accept a customer URL.
        if (function_exists('curl_init')) {
            $data = '';
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'MelwinCMS dependency installer',
                CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$data, $limit) {
                    if (strlen($data) + strlen($chunk) > $limit) return 0;
                    $data .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $errorCode = curl_errno($curl);
            curl_close($curl);
            if ($ok === false || $status !== 200) {
                error_log('Dependency download failed: HTTP ' . $status . '; ' . $error);
                if ($errorCode === 60) throw new RuntimeException('Serverns PHP kan inte verifiera HTTPS-certifikatet. Be webbhotellet konfigurera en aktuell CA-lista för PHP/cURL och försök igen.');
                throw new RuntimeException('Nedladdningen misslyckades. Kontrollera utgående HTTPS till ' . parse_url($url, PHP_URL_HOST) . ' och försök igen.');
            }
            return $data;
        }
        if (!ini_get('allow_url_fopen')) throw new RuntimeException('Aktivera PHP cURL eller allow_url_fopen för nedladdning via HTTPS.');
        $context = stream_context_create([
            'http' => ['timeout' => $timeout, 'follow_location' => 0, 'ignore_errors' => true, 'header' => "User-Agent: MelwinCMS dependency installer\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($url, false, $context, 0, $limit + 1);
        if ($data === false || strlen($data) > $limit || !preg_match('~^HTTP/\S+ 200(?: |$)~', $http_response_header[0] ?? '')) {
            throw new RuntimeException('Nedladdningen misslyckades. Kontrollera utgående HTTPS till ' . parse_url($url, PHP_URL_HOST) . ' och försök igen.');
        }
        return $data;
    }

    /** Render separate POST forms, outside any other form. Authorization belongs to caller. */
    public function render(string $token, string $actionField, string $tokenField): void
    {
        $h = function ($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); };
        try { $catalog = self::catalog($this->modules); }
        catch (RuntimeException $e) { echo '<p role="alert">' . $h($e->getMessage()) . '</p>'; return; }
        echo '<section class="dependency-list"><h2>Beroenden</h2><p>Hämta bibliotek direkt från deras officiella källa till denna server. Inget shell eller Composer behövs.</p>';
        echo '<table><thead><tr><th>Bibliotek</th><th>Installerad version</th><th>Tillgänglig version</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($catalog as $slug => $package) {
            $status = $this->status($slug);
            try { $this->checkPlatform($package); $platformError = ''; }
            catch (RuntimeException $e) { $platformError = $e->getMessage(); }
            echo '<tr><td><a href="' . $h($package['source']) . '">' . $h($package['name']) . '</a></td><td>' . $h($this->installedVersion($slug) ?? '—') . '</td><td>' . $h($package['version']) . ' (minst ' . $h($package['minimum']) . ')</td><td>' . $h($status) . '</td><td>';
            if ($platformError !== '') echo '<span role="status">' . $h($platformError) . '</span>';
            if ($status !== 'Installerad' && $platformError === '') {
                echo '<form method="post"><input type="hidden" name="' . $h($actionField) . '" value="install_dependency">';
                echo '<input type="hidden" name="' . $h($tokenField) . '" value="' . $h($token) . '">';
                echo '<input type="hidden" name="dependency" value="' . $h($slug) . '"><button type="submit">' . ($status === 'Uppdatering tillgänglig' ? 'Uppdatera' : 'Hämta och installera') . '</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table><p>Listan visar versioner från modulernas config/dependencies.json som stöds av serverns PHP. Underberoenden installeras automatiskt. Vid uppdatering sparas den gamla katalogen som .dependency-backup-* i resources/plugins.</p><p>Kräver utgående HTTPS, cURL eller allow_url_fopen samt skrivbehörighet i resources/plugins. Version och filernas kontrollsummor verifieras före aktivering.</p></section>';
    }
}
