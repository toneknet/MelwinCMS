<?php
require_once __DIR__ . '/manifest.php';

/** Central installation engine; all SQL runs through the site's existing connection. */
class sut_installer
{
    private $db;
    private $registry;
    private $scope;

    public function __construct($db = null)
    {
        $this->db = $db ?? db::getInstance();
        $this->registry = $this->name(($GLOBALS['CONFIG']['dbPrefix'] ?? '') . 'module_installations');
        $this->scope = (string)($GLOBALS['CONFIG']['dbPrefix_private'] ?? $GLOBALS['CONFIG']['dbPrefix'] ?? '');
    }

    private function name(string $name): string { return '`' . sut_manifest::identifier($name) . '`'; }
    private function value(string $value): string { return $value === '' ? "''" : '0x' . bin2hex($value); }
    private function table(string $name): string { return $this->name($this->db->o_prefix($name)); }
    private function rows(string $sql): array
    {
        $rows = $this->db->o_get_all_ai('', $sql);
        if ($rows === false) throw new Exception('Kunde inte läsa installationsdatabasen.');
        return $rows;
    }
    private function execute(string $sql): void
    {
        if (!$this->db->o_query($sql)) throw new Exception('Databassteget misslyckades. Installationen har stoppats.');
    }

    private function registry(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS ' . $this->registry . ' (
            scope varchar(100) NOT NULL, module varchar(100) NOT NULL, step varchar(100) NOT NULL,
            checksum char(64) NOT NULL, status varchar(20) NOT NULL,
            details longtext NOT NULL, updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(scope,module,step)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private function state(string $slug, string $step): ?array
    {
        $rows = $this->rows('SELECT * FROM ' . $this->registry . ' WHERE scope=' . $this->value($this->scope)
            . ' AND module=' . $this->value($slug) . ' AND step=' . $this->value($step));
        return $rows[0] ?? null;
    }

    private function record(string $slug, string $step, string $checksum, string $status, array $details): void
    {
        $values = array_map([$this, 'value'], [$this->scope, $slug, $step, $checksum, $status, json_encode($details, JSON_THROW_ON_ERROR)]);
        $this->execute('INSERT INTO ' . $this->registry . ' (scope,module,step,checksum,status,details) VALUES (' . implode(',', $values)
            . ') ON DUPLICATE KEY UPDATE checksum=VALUES(checksum),status=VALUES(status),details=VALUES(details)');
    }

    private function columns(string $table): array
    {
        $physical = $this->db->o_prefix($table);
        $found = $this->rows('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=' . $this->value($physical));
        if (!$found) return [];
        $columns = [];
        foreach ($this->rows('SHOW COLUMNS FROM ' . $this->name($physical)) as $row) $columns[$row['Field']] = $row;
        return $columns;
    }

    private function compatible(array $actual, array $expected): bool
    {
        if (!$actual) return false;
        foreach ($expected as $column => $type) {
            if (!isset($actual[$column])) return false;
            // MySQL 8 may omit integer display widths; these do not affect storage.
            $normalize = function ($value) { return preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', strtolower($value)); };
            if ($normalize($actual[$column]['Type']) !== $normalize($type)) return false;
        }
        return !isset($expected['id']) || (($actual['id']['Key'] ?? '') === 'PRI'
            && strpos($actual['id']['Extra'] ?? '', 'auto_increment') !== false);
    }

    public function installedVersion(string $slug): ?string
    {
        $name = trim($this->registry, '`');
        if (!$this->rows('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=' . $this->value($name))) return null;
        $state = $this->state($slug, 'module');
        if (!$state || $state['status'] !== 'installed') return null;
        return json_decode($state['details'], true)['version'] ?? null;
    }

    /** A protected ZIP containing CREATE statements and binary-safe INSERT statements. */
    public function backup(array $tables, string $zipPath): void
    {
        if (!class_exists('ZipArchive')) throw new Exception('ZipArchive saknas.');
        $tables = array_values(array_unique($tables));
        $existing = array_filter($tables, function ($table) { return (bool)$this->columns($table); });
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) throw new Exception('Kunde inte öppna databasbackup.');
        $locked = false;
        $hashes = [];
        try {
            if ($existing) {
                $this->execute('LOCK TABLES ' . implode(',', array_map(function ($table) { return $this->table($table) . ' READ'; }, $existing)));
                $locked = true;
            }
            foreach ($existing as $table) {
                $physical = $this->table($table);
                $create = $this->rows('SHOW CREATE TABLE ' . $physical);
                $sql = "SET NAMES utf8mb4;\n" . ($create[0]['Create Table'] ?? '') . ";\n";
                if (empty($create[0]['Create Table'])) throw new Exception('Kunde inte säkerhetskopiera tabellstruktur.');
                foreach ($this->rows('SELECT * FROM ' . $physical) as $row) {
                    $values = [];
                    foreach ($row as $value) $values[] = $value === null ? 'NULL' : $this->value((string)$value);
                    $sql .= 'INSERT INTO ' . $physical . ' (' . implode(',', array_map([$this, 'name'], array_keys($row))) . ') VALUES (' . implode(',', $values) . ");\n";
                }
                if (!$zip->addFromString('database/' . $table . '.sql', $sql)) throw new Exception('Kunde inte skriva databasbackup.');
                $hashes[$table] = hash('sha256', $sql);
            }
            if (!$zip->addFromString('database/README.txt', "Backup before module changes. Restore manually into a separate database first. Tables are not dropped automatically.\n")) throw new Exception('Backup misslyckades.');
        } finally {
            if ($locked) $this->execute('UNLOCK TABLES');
            $closed = $zip->close();
        }
        if (!$closed) throw new Exception('Kunde inte slutföra databasbackup.');
        $verify = new ZipArchive();
        if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) throw new Exception('Databasbackup kunde inte verifieras.');
        try {
            foreach ($hashes as $table => $hash) {
                $saved = $verify->getFromName('database/' . $table . '.sql');
                if ($saved === false || hash('sha256', $saved) !== $hash) throw new Exception('Ofullständig databasbackup.');
            }
        } finally { $verify->close(); }
    }

    public function install(string $slug, string $dir, string $backupPath): array
    {
        $manifest = sut_manifest::read($dir, $slug);
        if ($manifest === null) return ['managed' => false];
        $lock = $this->value('sut:' . substr(hash('sha256', $this->registry), 0, 60));
        $result = $this->rows('SELECT GET_LOCK(' . $lock . ',0) AS acquired');
        if ((int)($result[0]['acquired'] ?? 0) !== 1) throw new Exception('En annan modulinstallation pågår.');
        try {
            return $this->apply($manifest, $dir, $backupPath);
        } finally { $this->rows('SELECT RELEASE_LOCK(' . $lock . ')'); }
    }

    private function apply(array $manifest, string $dir, string $backupPath): array
    {
        $this->registry();
        $slug = $manifest['module'];
        $previous = $this->state($slug, 'module');
        $details = $previous ? json_decode($previous['details'], true, 64, JSON_THROW_ON_ERROR) : [];
        if (isset($details['version']) && version_compare($manifest['version'], $details['version'], '<')) throw new Exception('Nedgradering stöds inte.');
        foreach ($manifest['requires'] ?? [] as $dependency => $constraint) {
            $state = $this->state($dependency, 'module');
            $version = $state ? (json_decode($state['details'], true)['version'] ?? '0.0.0') : '0.0.0';
            preg_match('/^(>=|=)?(.*)$/', $constraint, $match);
            if (!$state || $state['status'] !== 'installed' || !version_compare($version, $match[2], ($match[1] ?? '') === '=' ? '==' : '>=')) {
                throw new Exception('Installera först beroendet ' . $dependency . ' ' . $constraint);
            }
        }
        foreach ($manifest['required_files'] ?? [] as $file) sut_manifest::file(ROOTPATH, $file);
        foreach ($manifest['database']['required_tables'] ?? [] as $table => $required) {
            if (array_diff($required, array_keys($this->columns($table)))) throw new Exception('Tabell eller kolumner saknas: ' . $table);
        }
        $steps = [];
        $targets = [];
        foreach ($manifest['database']['migrations'] ?? [] as $migration) {
            $targets[$migration['table']] = $migration['columns'];
            $sql = file_get_contents(sut_manifest::file($dir, $migration['file']));
            // One statement per migration; the DB driver never runs multi_query.
            $sql = str_replace('{{table}}', $this->table($migration['table']), $sql);
            if (!preg_match('/^\s*(?:CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?|ALTER\s+TABLE\s+)'
                . preg_quote($this->table($migration['table']), '/') . '\s/i', $sql)) {
                throw new Exception('Migreringen måste börja med CREATE TABLE eller ALTER TABLE för deklarerad tabell.');
            }
            $checksum = hash('sha256', json_encode($migration) . $sql);
            $state = $this->state($slug, $migration['id']);
            if ($state && $state['checksum'] !== $checksum) throw new Exception('En registrerad migrering har ändrats: ' . $migration['id']);
            $columns = $this->columns($migration['table']);
            $matches = $this->compatible($columns, $migration['columns']);
            if ($state && $state['status'] === 'done') {
                continue;
            }
            if ($columns && !$matches && !empty($migration['adopt_existing'])) throw new Exception('Befintlig tabell avviker. Ingen data har ändrats: ' . $migration['table']);
            if ($state && !$matches) throw new Exception('Avbruten migrering kräver manuell kontroll: ' . $migration['id']);
            $steps[] = [$migration, $sql, $checksum, $matches];
        }
        foreach ($manifest['ajax'] ?? [] as $ajax) {
            sut_manifest::file(dirname($dir), $ajax['script']);
            foreach ($this->rows('SELECT * FROM ' . $this->table('ajax') . ' WHERE name=' . $this->value($ajax['name'])) as $row) {
                if ($row['script'] !== $ajax['script'] || (int)$row['auth'] !== $ajax['auth']) throw new Exception('AJAX-namnet används redan: ' . $ajax['name']);
            }
        }
        $tables = array_merge(['ajax', 'page_content'], array_column($manifest['database']['migrations'] ?? [], 'table'), array_keys($manifest['database']['required_tables'] ?? []));
        $this->backup($tables, $backupPath);
        $details = array_merge($details, ['version' => $manifest['version'], 'manifest' => $manifest, 'backup_path' => $backupPath]);
        $this->record($slug, 'module', hash('sha256', json_encode($manifest)), 'installing', $details);
        foreach ($steps as [$migration, $sql, $checksum, $matches]) {
            $this->record($slug, $migration['id'], $checksum, 'running', ['backup_path' => $backupPath]);
            if (!$matches) $this->execute($sql);
            if (!$this->compatible($this->columns($migration['table']), $migration['columns'])) throw new Exception('Kontroll efter migrering misslyckades: ' . $migration['id']);
            $this->record($slug, $migration['id'], $checksum, 'done', ['adopted' => $matches]);
        }
        foreach ($targets as $table => $columns) {
            if (!$this->compatible($this->columns($table), $columns)) throw new Exception('Databasstrukturen avviker: ' . $table);
        }
        foreach ($manifest['ajax'] ?? [] as $ajax) {
            $rows = $this->rows('SELECT * FROM ' . $this->table('ajax') . ' WHERE name=' . $this->value($ajax['name']));
            if (!$rows) $this->execute('INSERT INTO ' . $this->table('ajax') . ' (name,script,auth,deleted) VALUES (' . $this->value($ajax['name']) . ',' . $this->value($ajax['script']) . ',1,0)');
            else $this->execute('UPDATE ' . $this->table('ajax') . ' SET deleted=0 WHERE name=' . $this->value($ajax['name']));
        }
        $this->record($slug, 'module', hash('sha256', json_encode($manifest)), 'installed', $details);
        return ['managed' => true, 'version' => $manifest['version'], 'backup_path' => $backupPath];
    }

    public function uninstall(string $slug, string $dir, string $backupPath): void
    {
        $manifest = sut_manifest::read($dir, $slug);
        if ($manifest === null) return;
        $lock = $this->value('sut:' . substr(hash('sha256', $this->registry), 0, 60));
        $result = $this->rows('SELECT GET_LOCK(' . $lock . ',0) AS acquired');
        if ((int)($result[0]['acquired'] ?? 0) !== 1) throw new Exception('En annan modulinstallation pågår.');
        try {
            $this->removeRegistrations($slug, $manifest, $backupPath);
        } finally { $this->rows('SELECT RELEASE_LOCK(' . $lock . ')'); }
    }

    private function removeRegistrations(string $slug, array $manifest, string $backupPath): void
    {
        $this->registry();
        foreach ($this->rows('SELECT * FROM ' . $this->registry . " WHERE step='module' AND status='installed'") as $row) {
            if ($row['module'] === $slug && $row['scope'] !== $this->scope) throw new Exception('Modulen används även med ett annat databas-prefix.');
            $data = json_decode($row['details'], true);
            if (isset($data['manifest']['requires'][$slug])) throw new Exception('Modulen krävs av ' . $row['module']);
        }
        $this->backup(array_merge(['ajax', 'page_content'], array_column($manifest['database']['migrations'] ?? [], 'table'), array_keys($manifest['database']['required_tables'] ?? [])), $backupPath);
        foreach ($manifest['ajax'] ?? [] as $ajax) {
            $this->execute('UPDATE ' . $this->table('ajax') . ' SET deleted=1 WHERE name=' . $this->value($ajax['name']) . ' AND script=' . $this->value($ajax['script']));
        }
        $this->execute('UPDATE ' . $this->table('page_content') . ' SET visible=0 WHERE module=' . $this->value($slug));
        $this->record($slug, 'module', hash('sha256', json_encode($manifest)), 'uninstalled', ['manifest' => $manifest, 'version' => $manifest['version'], 'backup_path' => $backupPath]);
    }
}
