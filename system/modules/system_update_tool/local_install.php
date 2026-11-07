<?php
require_once __DIR__ . '/manifest.php';

/** Local discovery and planning never include or instantiate a module's PHP. */
class sut_local_install
{
    private $root;
    private $installer;

    public function __construct(string $root, $installer)
    {
        $this->root = realpath($root);
        if ($this->root === false) throw new Exception('Modulkatalogen saknas.');
        $this->installer = $installer;
    }

    private function directory(string $slug): string
    {
        sut_manifest::identifier($slug);
        if ($slug[0] === '_') throw new Exception('Modulmappar som börjar med _ är undantagna från lokal installation.');
        $path = $this->root . DIRECTORY_SEPARATOR . $slug;
        $resolved = realpath($path);
        if (is_link($path) || $resolved === false || dirname($resolved) !== $this->root || !is_dir($resolved)) {
            throw new Exception('Lokal modul saknas eller har en ogiltig sökväg: ' . $slug);
        }
        return $resolved;
    }

    private function manifest(string $slug): array
    {
        $dir = $this->directory($slug);
        sut_manifest::file($dir, $slug . '.php');
        sut_manifest::file($dir, 'config/install.json');
        $manifest = sut_manifest::read($dir, $slug);
        foreach ($manifest['database']['migrations'] ?? [] as $step) sut_manifest::file($dir, $step['file']);
        foreach ($manifest['ajax'] ?? [] as $ajax) sut_manifest::file($this->root, $ajax['script']);
        return $manifest;
    }

    public function discover(): array
    {
        $modules = [];
        foreach (scandir($this->root) as $slug) {
            if ($slug[0] === '_') continue;
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $slug) || !is_file($this->root . '/' . $slug . '/config/install.json')) continue;
            try {
                $manifest = $this->manifest($slug);
                $version = $this->installer->installedVersion($slug);
                $modules[] = ['slug' => $slug, 'version' => $manifest['version'], 'installed_version' => $version,
                    'needs_setup' => $version !== $manifest['version'], 'error' => null];
            } catch (Throwable $e) {
                $modules[] = ['slug' => $slug, 'error' => $e->getMessage(), 'needs_setup' => false];
            }
        }
        return $modules;
    }

    public function plan(string $slug): array
    {
        $visiting = [];
        $ordered = [];
        $visit = function (string $current) use (&$visit, &$visiting, &$ordered) {
            if (isset($ordered[$current])) return;
            if (isset($visiting[$current])) throw new Exception('Cirkulärt lokalt modulberoende: ' . $current);
            $visiting[$current] = true;
            $manifest = $this->manifest($current);
            foreach ($manifest['requires'] ?? [] as $dependency => $constraint) {
                $dep = $this->manifest($dependency);
                preg_match('/^(>=|=)?(.*)$/', $constraint, $match);
                if (!version_compare($dep['version'], $match[2], ($match[1] ?? '') === '=' ? '==' : '>=')) {
                    throw new Exception('Lokalt beroende uppfyller inte versionskravet: ' . $dependency . ' ' . $constraint);
                }
                $visit($dependency);
            }
            $installed = $this->installer->installedVersion($current);
            if ($installed !== null && version_compare($manifest['version'], $installed, '<')) throw new Exception('Lokal nedgradering stöds inte: ' . $current);
            unset($visiting[$current]);
            $ordered[$current] = ['slug' => $current, 'version' => $manifest['version'], 'fingerprint' => $this->fingerprint($current)];
        };
        $visit($slug);
        return array_values($ordered);
    }

    private function files(string $slug): array
    {
        $dir = $this->directory($slug);
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            $path = $entry->getPathname();
            $real = realpath($path);
            if ($entry->isLink() || $real === false || strpos($real, $dir . DIRECTORY_SEPARATOR) !== 0) throw new Exception('Länkar stöds inte i lokala installationspaket: ' . $slug);
            if ($entry->isFile()) {
                if (!is_readable($path)) throw new Exception('Installationsfilen kan inte läsas: ' . $path);
                $files[str_replace('\\', '/', substr($path, strlen($dir) + 1))] = $path;
            }
        }
        ksort($files);
        return $files;
    }

    public function fingerprint(string $slug): string
    {
        $hashes = [];
        foreach ($this->files($slug) as $name => $path) {
            $hash = hash_file('sha256', $path);
            if ($hash === false) throw new Exception('Kunde inte kontrollera lokal fil: ' . $name);
            $hashes[$name] = $hash;
        }
        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    }

    public function install(array $step, string $fileBackup, string $databaseBackup): array
    {
        $slug = $step['slug'];
        if (!hash_equals($step['fingerprint'], $this->fingerprint($slug))) throw new Exception('Modulfilerna har ändrats. Kontrollera lokala moduler igen: ' . $slug);
        $this->manifest($slug);
        if (!class_exists('ZipArchive')) throw new Exception('ZipArchive saknas på servern.');
        $zip = new ZipArchive();
        if ($zip->open($fileBackup, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new Exception('Kunde inte skapa lokal filbackup.');
        $hashes = [];
        try {
            foreach ($this->files($slug) as $name => $path) {
                $content = file_get_contents($path);
                if ($content === false || !$zip->addFromString('system/modules/' . $slug . '/' . $name, $content)) throw new Exception('Kunde inte säkerhetskopiera ' . $name);
                $hashes[$name] = hash('sha256', $content);
            }
        } finally { $closed = $zip->close(); }
        if (!$closed) throw new Exception('Filbackupen kunde inte slutföras.');
        $verify = new ZipArchive();
        if ($verify->open($fileBackup, ZipArchive::CHECKCONS) !== true) throw new Exception('Filbackupen kunde inte verifieras.');
        try {
            foreach ($hashes as $name => $hash) {
                $content = $verify->getFromName('system/modules/' . $slug . '/' . $name);
                if ($content === false || hash('sha256', $content) !== $hash) throw new Exception('Ofullständig filbackup.');
            }
        } finally { $verify->close(); }
        if (!hash_equals($step['fingerprint'], $this->fingerprint($slug))) throw new Exception('Modulfilerna ändrades under backup. Installationen stoppades.');
        $result = $this->installer->install($slug, $this->directory($slug), $databaseBackup);
        if (empty($result['managed'])) throw new Exception('Modulens installationsmanifest saknas. Kontrollera lokala moduler igen.');
        return $result;
    }
}
