<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('ROOTPATH', __DIR__ . '/');
define('SYSPATH', ROOTPATH . 'system/');
define('MODPATH', SYSPATH . 'modules/');
define('CFGFILE', SYSPATH . 'configs/localconfig.inc.php');

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

$GLOBALS['CONFIG'] = [];
if (is_file(SYSPATH . 'configs/config.inc.php')) {
    require SYSPATH . 'configs/config.inc.php';
}
if (is_file(CFGFILE)) {
    require CFGFILE;
}

class MelwinInstaller
{
    private array $errors = [];
    private array $messages = [];
    private bool $installed = false;

    public function run(): void
    {
        if (!$this->coreAvailable()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['install_action'] ?? '') === 'download_core') {
                $this->handleCoreDownload();
            }
            $this->renderBootstrap();
            return;
        }

        if (!$this->isAllowed()) {
            $this->renderLocked();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleInstall();
        }

        $this->render();
    }

    private function coreAvailable(): bool
    {
        return is_file(SYSPATH . 'configs/config.inc.php') && is_dir(MODPATH);
    }

    private function handleCoreDownload(): void
    {
        if (!$this->checkToken($_POST['csrf'] ?? '')) {
            $this->errors[] = 'Sessionen har gatt ut. Ladda om sidan och forsok igen.';
            return;
        }

        if (!class_exists('ZipArchive')) {
            $this->errors[] = 'PHP ZipArchive saknas. Installera zip-extension eller packa upp MelwinCMS manuellt.';
            return;
        }

        $repoUrl = trim((string)($_POST['repo_url'] ?? 'https://github.com/toneknet/MelwinCMS'));
        $zipUrl = rtrim($repoUrl, '/') . '/archive/refs/heads/master.zip';
        $tmpZip = sys_get_temp_dir() . '/melwincms_' . bin2hex(random_bytes(6)) . '.zip';

        try {
            $zipData = $this->httpGet($zipUrl);
            if (file_put_contents($tmpZip, $zipData, LOCK_EX) === false) {
                throw new RuntimeException('Kunde inte skriva temporar zip-fil.');
            }
            $this->extractCoreZip($tmpZip);
            $this->messages[] = 'MelwinCMS-filerna hamtades. Fortsatt med databasinstallationen.';
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        } finally {
            if (is_file($tmpZip)) {
                @unlink($tmpZip);
            }
        }
    }

    private function isAllowed(): bool
    {
        if (!is_file(CFGFILE)) {
            return true;
        }

        return (int)($GLOBALS['CONFIG']['allow_install'] ?? 0) === 1;
    }

    private function handleInstall(): void
    {
        if (!$this->checkToken($_POST['csrf'] ?? '')) {
            $this->errors[] = 'Sessionen har gatt ut. Ladda om sidan och forsok igen.';
            return;
        }

        $data = $this->collectPostData();
        if (!$this->validate($data)) {
            return;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $db = new mysqli($data['dbHost'], $data['dbUser'], $data['dbPass'], $data['dbName']);
            $db->set_charset('utf8mb4');

            $companies = $this->buildCompanies($data);
            $config = $this->buildConfig($data);
            $GLOBALS['CONFIG'] = $config;

            $created = $this->installSchema($db, $data['dbPrefix'], $companies);
            $this->seedBaseData($db, $data, $companies);
            $this->registerAjaxEndpoint($db, $data['dbPrefix'], 'system_update_tool', 'system_update_tool/system_update_tool_ajax.php');
            $this->writeConfig($config);
            $this->ensureWritableFolders();

            $this->installed = true;
            $this->messages[] = 'Installationen ar klar. ' . $created . ' tabellkommandon kontrollerades eller skapades.';
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function collectPostData(): array
    {
        $wwwRoot = trim((string)($_POST['www_root'] ?? $this->guessBaseUrl()));
        $wwwRoot = rtrim($wwwRoot, '/');

        return [
            'www-root' => $wwwRoot,
            'title' => trim((string)($_POST['title'] ?? 'My Melwin Site')),
            'language' => trim((string)($_POST['language'] ?? 'sv')),
            'timezone' => trim((string)($_POST['timezone'] ?? 'Europe/Stockholm')),
            'template' => trim((string)($_POST['template'] ?? 'default')),
            'dbHost' => trim((string)($_POST['db_host'] ?? 'localhost')),
            'dbUser' => trim((string)($_POST['db_user'] ?? '')),
            'dbPass' => (string)($_POST['db_pass'] ?? ''),
            'dbName' => trim((string)($_POST['db_name'] ?? '')),
            'dbPrefix' => trim((string)($_POST['db_prefix'] ?? 'mt_')),
            'multiCompany' => (string)($_POST['multi_company'] ?? '0') === '1',
            'companyName' => trim((string)($_POST['company_name'] ?? 'Default organisation')),
            'companyPrefix' => trim((string)($_POST['company_prefix'] ?? '')),
            'adminUser' => trim((string)($_POST['admin_user'] ?? '')),
            'adminName' => trim((string)($_POST['admin_name'] ?? '')),
            'adminEmail' => trim((string)($_POST['admin_email'] ?? '')),
            'adminPass' => (string)($_POST['admin_pass'] ?? ''),
            'adminPass2' => (string)($_POST['admin_pass2'] ?? ''),
        ];
    }

    private function validate(array $data): bool
    {
        if (!in_array($data['template'], $this->templateNames(), true)) {
            $this->errors[] = 'Valj en template som finns i listan.';
        }
        if ($data['title'] === '') {
            $this->errors[] = 'Ange ett namn pa webbplatsen.';
        }
        if ($data['dbHost'] === '' || $data['dbUser'] === '' || $data['dbName'] === '') {
            $this->errors[] = 'Databasens host, anvandare och namn maste anges.';
        }
        if (!$this->isValidPrefix($data['dbPrefix'])) {
            $this->errors[] = 'Databasprefix maste bara innehalla bokstaver, siffror och understreck, och sluta med understreck.';
        }
        if ($data['companyName'] === '') {
            $this->errors[] = 'Ange namn pa organisationen.';
        }
        if ($data['multiCompany']) {
            if (!$this->isValidPrefix($data['companyPrefix'])) {
                $this->errors[] = 'Organisationsprefix maste bara innehalla bokstaver, siffror och understreck, och sluta med understreck.';
            }
            if ($data['companyPrefix'] === $data['dbPrefix']) {
                $this->errors[] = 'Organisationsprefix bor skilja sig fran systemets publika prefix vid flerorganisation.';
            }
        }
        if ($data['adminUser'] === '' || $data['adminEmail'] === '') {
            $this->errors[] = 'Ange anvandarnamn och e-post for administratoren.';
        }
        if ($data['adminPass'] === '' || strlen($data['adminPass']) < 8) {
            $this->errors[] = 'Adminlosenordet maste vara minst 8 tecken.';
        }
        if ($data['adminPass'] !== $data['adminPass2']) {
            $this->errors[] = 'Adminlosenorden matchar inte.';
        }

        return count($this->errors) === 0;
    }

    private function httpGet(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: MelwinCMS installer']);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $status < 200 || $status >= 300) {
                throw new RuntimeException('Kunde inte hamta MelwinCMS fran GitHub. HTTP ' . $status . ' ' . $error);
            }

            return (string)$body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: MelwinCMS installer\r\n",
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';

        if ($body === false || (preg_match('/\s(\d{3})\s/', $statusLine, $match) && ((int)$match[1] < 200 || (int)$match[1] >= 300))) {
            throw new RuntimeException('Kunde inte hamta MelwinCMS fran GitHub.');
        }

        return (string)$body;
    }

    private function extractCoreZip(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Kunde inte oppna zip-filen fran GitHub.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!is_string($entry) || $entry === '') {
                continue;
            }

            $parts = explode('/', str_replace('\\', '/', $entry));
            array_shift($parts);
            $relative = implode('/', $parts);
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }
            if ($relative === 'install.php' || $relative === 'system/configs/localconfig.inc.php') {
                continue;
            }

            $target = ROOTPATH . $relative;
            if (substr($entry, -1) === '/') {
                if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
                    throw new RuntimeException('Kunde inte skapa katalog: ' . $relative);
                }
                continue;
            }

            $dir = dirname($target);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Kunde inte skapa katalog: ' . dirname($relative));
            }

            $stream = $zip->getStream($entry);
            if (!$stream) {
                throw new RuntimeException('Kunde inte lasa zip-entry: ' . $relative);
            }
            $content = stream_get_contents($stream);
            fclose($stream);

            if (file_put_contents($target, $content, LOCK_EX) === false) {
                throw new RuntimeException('Kunde inte skriva fil: ' . $relative);
            }
        }

        $zip->close();
    }

    private function buildCompanies(array $data): array
    {
        $prefix = $data['multiCompany'] ? $data['companyPrefix'] : $data['dbPrefix'];

        return [
            [
                'id' => 1,
                'name' => $data['companyName'],
                'dbprefix' => $prefix,
                'prefix' => $data['multiCompany'] ? strtoupper(trim($prefix, '_')) : '',
            ],
        ];
    }

    private function buildConfig(array $data): array
    {
        $config = $GLOBALS['CONFIG'];
        foreach ([
            'www-root', 'title', 'language', 'timezone', 'template',
            'dbHost', 'dbUser', 'dbPass', 'dbName', 'dbPrefix',
        ] as $key) {
            $config[$key] = $data[$key];
        }

        $config['salt'] = $config['salt'] ?: bin2hex(random_bytes(32));
        $config['install_password'] = hash('sha256', bin2hex(random_bytes(16)));
        $config['allow_install'] = 0;
        $config['allow_company'] = $data['multiCompany'] ? 1 : 0;
        $config['TheCompanyPage'] = 5;

        return $config;
    }

    private function installSchema(mysqli $db, string $publicPrefix, array $companies): int
    {
        $publicTables = array_filter(array_map('trim', explode(',', (string)($GLOBALS['CONFIG']['public_tables'] ?? ''))));
        $companyPrefixes = array_values(array_unique(array_map(static fn($row) => $row['dbprefix'], $companies)));
        $sqlFiles = $this->findSqlFiles();
        $executed = 0;

        foreach ($sqlFiles as $file) {
            foreach ($this->extractCreateStatements(file_get_contents($file) ?: '') as $statement) {
                $table = $this->getCreateTableName($statement);
                if ($table === '') {
                    continue;
                }

                $base = $this->stripKnownPrefix($table);
                $targets = in_array($base, $publicTables, true) ? [$publicPrefix] : $companyPrefixes;

                foreach ($targets as $targetPrefix) {
                    $targetTable = $targetPrefix . $base;
                    $sql = $this->retargetCreateStatement($statement, $table, $targetTable);
                    $db->query($sql);
                    $executed++;
                }
            }
        }

        return $executed;
    }

    private function seedBaseData(mysqli $db, array $data, array $companies): void
    {
        $prefix = $data['dbPrefix'];
        $pages = [
            [1, 0, '/', 'Start', 1, 1, 1, 0],
            [2, 0, '/settings', 'System', 10, 0, 1, 0],
            [3, 2, '/settings/pages', 'Sidor', 10, 0, 1, 0],
            [4, 2, '/settings/database', 'Databas', 20, 0, 1, 0],
            [5, 2, '/settings/company', 'Organisationer', 30, 0, 1, 0],
            [6, 2, '/settings/users', 'Anvandare', 40, 0, 1, 0],
            [7, 2, '/settings/groups', 'Grupper', 50, 0, 1, 0],
            [8, 2, '/settings/update', 'Uppdatering', 60, 0, 1, 0],
        ];

        foreach ($pages as $page) {
            $this->query(
                $db,
                "REPLACE INTO `{$prefix}page` (`id`,`pid`,`src`,`label`,`ordning`,`isstartpage`,`visible`,`jumpTo`,`companyid`,`deleted`) VALUES (?,?,?,?,?,?,?,?,0,0)",
                'iissiiii',
                $page
            );
        }

        $content = [
            [1, 0, 'navigation', 'navigation', 1, 10, null, 0],
            [2, 0, 'header', 'userbar', 1, 20, null, 0],
            [3, 3, 'main', 'menumaker', 1, 10, null, 0],
            [4, 4, 'main', 'database', 1, 10, null, 0],
            [5, 5, 'main', 'company', 1, 10, null, 0],
            [6, 6, 'main', 'listandedit', 1, 10, serialize(['table' => 'user', 'form' => ['palette' => 'id,user,namn,email,groups,superadmin,active,companyid']]), 0],
            [7, 7, 'main', 'groups', 1, 10, null, 0],
            [8, 8, 'main', 'system_update_tool', 1, 10, null, 0],
        ];

        foreach ($content as $row) {
            $this->query(
                $db,
                "REPLACE INTO `{$prefix}page_content` (`id`,`pid`,`target`,`module`,`visible`,`ordning`,`settings`,`companyid`,`deleted`) VALUES (?,?,?,?,?,?,?,?,0)",
                'iissiisi',
                $row
            );
        }

        foreach ($companies as $company) {
            $this->query(
                $db,
                "REPLACE INTO `{$prefix}company` (`id`,`namn`,`ordning`,`companyid`,`deleted`,`dbprefix`,`prefix`,`settings`) VALUES (?,?,99,0,0,?,?,?)",
                'issss',
                [$company['id'], $company['name'], $company['dbprefix'], $company['prefix'], serialize([])]
            );
        }

        $access = [];
        foreach ($pages as $page) {
            $access[$page[0]] = ['allow' => 1, 'edit' => 1];
        }

        $this->query(
            $db,
            "REPLACE INTO `{$prefix}groups` (`id`,`namn`,`description`,`settings`,`companyid`,`deleted`,`ordning`) VALUES (1,'Administrators','Full system access',?,1,0,1)",
            's',
            [serialize($access)]
        );

        $securePass = hash('sha512', $data['adminPass'] . ($GLOBALS['CONFIG']['salt'] ?? ''));
        $this->query(
            $db,
            "REPLACE INTO `{$prefix}user` (`id`,`user`,`pass`,`namn`,`groups`,`superadmin`,`securepass`,`active`,`deleted`,`email`,`companyid`,`settings`) VALUES (1,?,'',?,?,1,?,1,0,?,1,?)",
            'ssssss',
            [
                $data['adminUser'],
                $data['adminName'] ?: $data['adminUser'],
                serialize([1 => 1]),
                $securePass,
                $data['adminEmail'],
                serialize([]),
            ]
        );

        $this->query(
            $db,
            "REPLACE INTO `{$prefix}options` (`id`,`option_key`,`option_value`,`ordning`,`companyid`,`deleted`) VALUES (1,'installed_at',?,1,0,0)",
            's',
            [date('Y-m-d H:i:s')]
        );
    }

    private function registerAjaxEndpoint(mysqli $db, string $prefix, string $name, string $script): void
    {
        if (!$this->isValidPrefix($prefix)) {
            throw new InvalidArgumentException('Ogiltigt databasprefix.');
        }

        $stmt = $db->prepare("SELECT id FROM `{$prefix}ajax` WHERE name=? LIMIT 1");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $this->query($db,
                "UPDATE `{$prefix}ajax` SET auth=1, script=?, deleted=0 WHERE name=?",
                'ss', [$script, $name]);
        } else {
            $this->query($db,
                "INSERT INTO `{$prefix}ajax` (auth,name,script,ordning,companyid,deleted) VALUES (1,?,?,99,0,0)",
                'ss', [$name, $script]);
        }
    }

    private function writeConfig(array $config): void
    {
        $output = "<?php\n";
        $output .= "// Local MelwinCMS configuration. Generated by install.php on " . date('Y-m-d H:i:s') . "\n";

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $output .= "\$GLOBALS['CONFIG']['" . $this->escapeConfigKey($key) . "'] = " . var_export($value, true) . ";\n";
            } else {
                $output .= "\$GLOBALS['CONFIG']['" . $this->escapeConfigKey($key) . "'] = " . var_export((string)$value, true) . ";\n";
            }
        }

        if (!is_dir(dirname(CFGFILE))) {
            throw new RuntimeException('Configmappen saknas: ' . dirname(CFGFILE));
        }

        if (file_put_contents(CFGFILE, $output, LOCK_EX) === false) {
            throw new RuntimeException('Kunde inte skriva localconfig.inc.php. Kontrollera filrattigheter.');
        }
    }

    private function ensureWritableFolders(): void
    {
        foreach (['upload', 'system/logs'] as $folder) {
            $path = ROOTPATH . $folder;
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            if (is_dir($path) && !is_file($path . '/index.php')) {
                @file_put_contents($path . '/index.php', "<?php\n// Silence is golden.\n");
            }
        }
    }

    private function findSqlFiles(): array
    {
        $files = [];
        if (!is_dir(MODPATH)) {
            return $files;
        }

        foreach (scandir(MODPATH) ?: [] as $module) {
            if ($module === '.' || $module === '..' || str_starts_with($module, '_')) {
                continue;
            }
            $modulePath = MODPATH . $module;
            if (!is_dir($modulePath)) {
                continue;
            }
            $file = $modulePath . '/config/database.sql';
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function extractCreateStatements(string $sql): array
    {
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? '';
        $lines = [];
        foreach (preg_split('/\R/', $sql) ?: [] as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
                continue;
            }
            $lines[] = $line;
        }

        $statements = [];
        foreach (explode(';', implode("\n", $lines)) as $statement) {
            $statement = trim($statement);
            if (preg_match('/^CREATE\s+TABLE\s+`[^`]+`/i', $statement)) {
                $statements[] = $statement . ';';
            }
        }

        return $statements;
    }

    private function getCreateTableName(string $statement): string
    {
        if (preg_match('/^CREATE\s+TABLE\s+`([^`]+)`/i', $statement, $match)) {
            return $match[1];
        }

        return '';
    }

    private function retargetCreateStatement(string $statement, string $sourceTable, string $targetTable): string
    {
        $statement = preg_replace('/^CREATE\s+TABLE\s+`' . preg_quote($sourceTable, '/') . '`/i', 'CREATE TABLE IF NOT EXISTS `' . $targetTable . '`', $statement, 1) ?? $statement;
        $statement = preg_replace('/(`[^`]+`\s+(?:var)?char\([^)]+\)\s+)NOT\s+NULL\s+default\s+NULL/i', '$1NOT NULL default \'\'', $statement) ?? $statement;
        return str_replace('`' . $sourceTable . '`', '`' . $targetTable . '`', $statement);
    }

    private function stripKnownPrefix(string $table): string
    {
        foreach (['hbook_', 'm_', 'mt_'] as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return substr($table, strlen($prefix));
            }
        }

        return $table;
    }

    private function query(mysqli $db, string $sql, string $types, array $values): void
    {
        $stmt = $db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        $stmt->close();
    }

    private function isValidPrefix(string $prefix): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9]+[A-Za-z0-9_]*_$/', $prefix);
    }

    private function guessBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    private function token(): string
    {
        if (empty($_SESSION['melwin_install_token'])) {
            $_SESSION['melwin_install_token'] = bin2hex(random_bytes(16));
        }

        return $_SESSION['melwin_install_token'];
    }

    private function checkToken(string $token): bool
    {
        return isset($_SESSION['melwin_install_token']) && hash_equals($_SESSION['melwin_install_token'], $token);
    }

    private function escapeConfigKey(string $key): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $key);
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function old(string $key, string $default = ''): string
    {
        return $this->h((string)($_POST[$key] ?? $default));
    }

    private function renderLocked(): void
    {
        $this->pageStart('MelwinCMS installer locked');
        echo '<main class="box"><h1>Installeraren ar stangd</h1>';
        echo '<p>Installationen ar redan kord och <code>allow_install</code> ar avstangd i <code>system/configs/localconfig.inc.php</code>.</p>';
        echo '<p>Vill du kora om den, satt <code>$GLOBALS[\'CONFIG\'][\'allow_install\'] = 1;</code> tillfalligt och ladda om denna sida.</p>';
        echo '<p><a class="button" href="index.php">Ga till webbplatsen</a></p></main>';
        $this->pageEnd();
    }

    private function render(): void
    {
        $this->pageStart('Installera MelwinCMS');
        echo '<main class="box">';
        echo '<h1>Installera MelwinCMS</h1>';
        echo '<p class="lead">En fil i rooten som skapar config, databasstruktur, grundsidor, organisation, grupper och superadmin.</p>';

        foreach ($this->errors as $error) {
            echo '<div class="notice error">' . $this->h($error) . '</div>';
        }
        foreach ($this->messages as $message) {
            echo '<div class="notice success">' . $this->h($message) . '</div>';
        }

        if ($this->installed) {
            echo '<section class="done"><h2>Klart</h2>';
            echo '<p>Ta bort eller lat installeraren vara stangd via config. Du kan nu logga in och fortsatta bygga systemet via grundsidorna.</p>';
            echo '<p><a class="button primary" href="index.php">Oppna MelwinCMS</a></p></section></main>';
            $this->pageEnd();
            return;
        }

        $this->renderForm();
        echo '</main>';
        $this->pageEnd();
    }

    private function renderBootstrap(): void
    {
        $this->pageStart('Hamta MelwinCMS');
        echo '<main class="box"><h1>Hamta MelwinCMS</h1>';
        echo '<p class="lead">Den har rooten saknar MelwinCMS-karnan. Installeraren kan hamta aktuell kod fran GitHub och sedan fortsatta installationen.</p>';

        foreach ($this->errors as $error) {
            echo '<div class="notice error">' . $this->h($error) . '</div>';
        }
        foreach ($this->messages as $message) {
            echo '<div class="notice success">' . $this->h($message) . '</div>';
        }

        if ($this->coreAvailable()) {
            echo '<p><a class="button primary" href="install.php">Fortsatt installationen</a></p></main>';
            $this->pageEnd();
            return;
        }

        echo '<form method="post">';
        echo '<input type="hidden" name="csrf" value="' . $this->h($this->token()) . '">';
        echo '<input type="hidden" name="install_action" value="download_core">';
        $this->field('repo_url', 'GitHub-repo', $this->old('repo_url', 'https://github.com/toneknet/MelwinCMS'));
        echo '<p class="hint">Krav pa servern: utgaende HTTPS och PHP ZipArchive.</p>';
        echo '<button class="button primary" type="submit">Hamta systemfiler</button>';
        echo '</form></main>';
        $this->pageEnd();
    }

    private function renderForm(): void
    {
        $defaultPrefix = (string)($GLOBALS['CONFIG']['dbPrefix'] ?? 'mt_');
        $defaultTitle = (string)($GLOBALS['CONFIG']['title'] ?? 'My Melwin Site');
        $defaultLanguage = (string)($GLOBALS['CONFIG']['language'] ?? 'sv');
        $defaultTimezone = (string)($GLOBALS['CONFIG']['timezone'] ?? 'Europe/Stockholm');
        $defaultTemplate = (string)($GLOBALS['CONFIG']['template'] ?? 'default');

        echo '<form method="post">';
        echo '<input type="hidden" name="csrf" value="' . $this->h($this->token()) . '">';

        echo '<section><h2>Webbplats</h2><div class="grid">';
        $this->field('title', 'Namn', $this->old('title', $defaultTitle));
        $this->field('www_root', 'Webbadress', $this->old('www_root', $this->guessBaseUrl()));
        $this->field('language', 'Sprak', $this->old('language', $defaultLanguage));
        $this->field('timezone', 'Tidszon', $this->old('timezone', $defaultTimezone));
        $selectedTemplate = (string)($_POST['template'] ?? $defaultTemplate);
        echo '<label><span>Template</span><select name="template" required>';
        echo '<option value="">Valj template</option>';
        foreach ($this->templateNames() as $template) {
            echo '<option value="' . $this->h($template) . '"' . ($template === $selectedTemplate ? ' selected' : '') . '>' . $this->h($template) . '</option>';
        }
        echo '</select></label>';
        echo '</div></section>';

        echo '<section><h2>Databas</h2><div class="grid">';
        $this->field('db_host', 'Host', $this->old('db_host', (string)($GLOBALS['CONFIG']['dbHost'] ?? 'localhost')));
        $this->field('db_name', 'Databas', $this->old('db_name', (string)($GLOBALS['CONFIG']['dbName'] ?? '')));
        $this->field('db_user', 'Anvandare', $this->old('db_user', (string)($GLOBALS['CONFIG']['dbUser'] ?? '')));
        $this->field('db_pass', 'Losenord', $this->old('db_pass', ''), 'password');
        $this->field('db_prefix', 'Publikt tabellprefix', $this->old('db_prefix', $defaultPrefix));
        echo '</div></section>';

        echo '<section><h2>Organisation</h2>';
        echo '<label class="check"><input type="checkbox" name="multi_company" value="1" ' . (((string)($_POST['multi_company'] ?? '') === '1') ? 'checked' : '') . '> Systemet ska kunna anvandas av fler an en organisation</label>';
        echo '<div class="grid">';
        $this->field('company_name', 'Forsta organisation', $this->old('company_name', 'Default organisation'));
        $this->field('company_prefix', 'Privat tabellprefix', $this->old('company_prefix', 'org1_'));
        echo '</div><p class="hint">Vid enkel organisation anvands det publika prefixet aven for privata tabeller. Vid flerorganisation skapas publika tabeller med systemprefixet och organisationsdata med eget prefix.</p></section>';

        echo '<section><h2>Superadmin</h2><div class="grid">';
        $this->field('admin_user', 'Anvandarnamn', $this->old('admin_user', ''));
        $this->field('admin_name', 'Namn', $this->old('admin_name', ''));
        $this->field('admin_email', 'E-post', $this->old('admin_email', ''));
        $this->field('admin_pass', 'Losenord', '', 'password');
        $this->field('admin_pass2', 'Bekrafta losenord', '', 'password');
        echo '</div></section>';

        echo '<button class="button primary" type="submit">Installera systemet</button>';
        echo '</form>';
    }

    private function templateNames(): array
    {
        $path = ROOTPATH . 'tpl/';
        if (!is_dir($path)) {
            return [];
        }
        $names = [];
        foreach (scandir($path) ?: [] as $name) {
            if ($name !== '.' && $name !== '..' && is_dir($path . $name)) {
                $names[] = $name;
            }
        }
        natcasesort($names);
        return array_values($names);
    }

    private function field(string $name, string $label, string $value, string $type = 'text'): void
    {
        echo '<label><span>' . $this->h($label) . '</span><input type="' . $type . '" name="' . $this->h($name) . '" value="' . $value . '"></label>';
    }

    private function pageStart(string $title): void
    {
        echo '<!doctype html><html lang="sv"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . $this->h($title) . '</title>';
        echo '<style>
            body{margin:0;background:#f4f6f8;color:#172033;font:16px/1.45 system-ui,-apple-system,Segoe UI,sans-serif}
            .box{max-width:980px;margin:32px auto;padding:28px;background:#fff;border:1px solid #d8dee8;border-radius:8px;box-shadow:0 10px 35px rgba(23,32,51,.08)}
            h1{margin:0 0 8px;font-size:30px} h2{margin:28px 0 14px;font-size:18px}
            .lead,.hint{color:#5d6878}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}
            label span{display:block;margin-bottom:5px;font-weight:650} input{width:100%;box-sizing:border-box;border:1px solid #c8d0dc;border-radius:6px;padding:10px 11px;font:inherit}
            input:focus{outline:2px solid #7aa7ff;border-color:#7aa7ff}.check{display:flex;gap:10px;align-items:center;margin-bottom:14px}.check input{width:auto}
            .button{display:inline-block;border:0;border-radius:6px;background:#26364f;color:#fff;padding:11px 16px;text-decoration:none;font-weight:700;cursor:pointer}.button.primary{background:#0b6bcb}
            .notice{padding:12px 14px;border-radius:6px;margin:14px 0}.error{background:#ffe9e9;border:1px solid #ffb7b7}.success{background:#e9f8ee;border:1px solid #abd8b7}
            code{background:#eef1f5;padding:2px 4px;border-radius:4px}.done{border-top:1px solid #e3e8f0;margin-top:24px}
            @media (max-width:720px){.box{margin:0;border:0;border-radius:0}.grid{grid-template-columns:1fr}}
        </style></head><body>';
    }

    private function pageEnd(): void
    {
        echo '</body></html>';
    }
}

(new MelwinInstaller())->run();
