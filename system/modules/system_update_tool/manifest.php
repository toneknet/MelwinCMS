<?php

/** Declarative module metadata. Reading a manifest never executes module PHP. */
class sut_manifest
{
    public static function read(string $dir, string $slug): ?array
    {
        $file = $dir . '/config/install.json';
        if (!is_file($file)) return null;
        $data = json_decode(file_get_contents($file), true, 64, JSON_THROW_ON_ERROR);
        return self::validate($data, $slug);
    }

    public static function validate(array $data, string $slug): array
    {
        self::identifier($slug);
        if (($data['schema_version'] ?? null) !== 1 || ($data['module'] ?? '') !== $slug
            || !is_string($data['version'] ?? null) || !preg_match('/^\d+\.\d+\.\d+$/D', $data['version'])) {
            throw new Exception('Ogiltigt installationsmanifest för ' . $slug);
        }
        foreach (array_keys($data['requires'] ?? []) as $dependency) {
            self::identifier($dependency);
            if (!preg_match('/^(>=|=)?\d+\.\d+\.\d+$/D', $data['requires'][$dependency])) {
                throw new Exception('Ogiltigt versionskrav för ' . $dependency);
            }
        }
        $ids = [];
        foreach ($data['database']['migrations'] ?? [] as $migration) {
            self::identifier($migration['id'] ?? '');
            self::identifier($migration['table'] ?? '');
            self::relativePath($migration['file'] ?? '');
            if (isset($ids[$migration['id']]) || empty($migration['columns'])) throw new Exception('Ogiltig migrering.');
            $ids[$migration['id']] = true;
            foreach ($migration['columns'] as $column => $type) {
                self::identifier($column);
                if (!is_string($type) || !preg_match('/^[a-z]+(?:\(\d+(?:,\d+)?\))?(?: unsigned)?$/D', $type)) {
                    throw new Exception('Ogiltig kolumntyp: ' . $column);
                }
            }
        }
        foreach ($data['database']['required_tables'] ?? [] as $table => $columns) {
            self::identifier($table);
            foreach ($columns as $column) self::identifier($column);
        }
        foreach ($data['ajax'] ?? [] as $ajax) {
            self::identifier($ajax['name'] ?? '');
            self::relativePath($ajax['script'] ?? '');
            if (strpos($ajax['script'], $slug . '/') !== 0 || ($ajax['auth'] ?? null) !== 1) {
                throw new Exception('AJAX måste tillhöra modulen och kräva inloggning.');
            }
        }
        foreach ($data['required_files'] ?? [] as $file) self::relativePath($file);
        if (($data['uninstall']['preserve_data'] ?? true) !== true) {
            throw new Exception('Automatisk radering av verksamhetsdata stöds inte.');
        }
        return $data;
    }

    public static function identifier(string $value): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $value)) throw new Exception('Ogiltigt installationsnamn: ' . $value);
        return $value;
    }

    public static function relativePath(string $value): void
    {
        if ($value === '' || strpos($value, '\\') !== false || $value[0] === '/'
            || preg_match('~(^|/)\.\.?(/|$)|:|\x00~', $value)) throw new Exception('Ogiltig installationssökväg.');
    }

    public static function file(string $dir, string $relative): string
    {
        self::relativePath($relative);
        $base = realpath($dir);
        $file = realpath($dir . '/' . $relative);
        if ($base === false || $file === false || !is_file($file)
            || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0) throw new Exception('Installationsfil saknas: ' . $relative);
        return $file;
    }
}
