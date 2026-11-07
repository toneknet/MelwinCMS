<?php
/** Filesystem access is based on discovered paths, never on request parameters. */
class logfiles_storage
{
    private $root;
    private $base;
    private $visited = array();

    public function __construct($root)
    {
        $this->root = realpath($root);
        $path = $this->root . '/system/logs';
        $this->base = !is_link($path) && !is_link($this->root . '/system') ? realpath($path) : false;
        if ($this->base && !$this->inside($this->base, $this->root)) $this->base = false;
    }

    private function inside($path, $parent)
    {
        return strpos($path, $parent . DIRECTORY_SEPARATOR) === 0;
    }

    public static function date($name)
    {
        if (!preg_match('/[_.](\d{8})\.log$/i', $name, $match)) return null;
        $date = DateTimeImmutable::createFromFormat('!Ymd', $match[1], new DateTimeZone('Europe/Stockholm'));
        return $date && $date->format('Ymd') === $match[1] ? $date : null;
    }

    public function catalog()
    {
        $folders = array();
        $this->visited = array();
        if ($this->base && is_dir($this->base)) $this->scan($this->base, '', $folders);
        $folders += array('' => array());
        $path = $this->root . '/error.log';
        if (!is_link($path) && is_file($path) && dirname(realpath($path)) === $this->root) {
            $folders['']['error.log (rot)'] = realpath($path);
        }
        ksort($folders, SORT_NATURAL);
        return $folders;
    }

    private function scan($directory, $relative, &$folders)
    {
        if (isset($this->visited[$directory])) return;
        $this->visited[$directory] = true;
        $folders[$relative] = array();
        foreach (@scandir($directory) ?: array() as $name) {
            if ($name === '.' || $name === '..') continue;
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $real = realpath($path);
            if (is_link($path) || !$real || !$this->inside($real, $this->base)) continue;
            $child = $relative === '' ? $name : $relative . '/' . $name;
            if (is_dir($real)) {
                $this->scan($real, $child, $folders);
            } elseif (is_file($real) && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'log') {
                $folders[$relative]['system/logs/' . $child] = $real;
            }
        }
        ksort($folders[$relative], SORT_NATURAL | SORT_FLAG_CASE);
    }

    /** Only dated files in the top-level logs directory are moved. */
    public function archive($today = null)
    {
        $result = array('moved' => 0, 'warnings' => array());
        if (!$this->base) return $result;
        $today = $today ?: new DateTimeImmutable('today', new DateTimeZone('Europe/Stockholm'));
        $cutoff = $today->setTimezone(new DateTimeZone('Europe/Stockholm'))->setTime(0, 0)->modify('-7 days');
        $lockPath = $this->base . '/.archive.lock';
        if (is_link($lockPath)) {
            $result['warnings'][] = 'Arkivering kunde inte låsas.';
            return $result;
        }
        $lock = @fopen($lockPath, 'c');
        if (!$lock) {
            $result['warnings'][] = 'Arkivering kunde inte startas. Kontrollera skrivrättigheterna för system/logs.';
            return $result;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return $result; }
        try {
            foreach (glob($this->base . '/*.log') ?: array() as $path) {
                $date = self::date(basename($path));
                if (!$date || $date >= $cutoff || is_link($path) || !is_file($path) || dirname(realpath($path)) !== $this->base) continue;
                $directory = $this->base . '/' . $date->format('Ym');
                if (is_link($directory) || (!is_dir($directory) && !@mkdir($directory, 0750))) {
                    $result['warnings'][] = 'Kunde inte skapa arkivmapp för ' . basename($path);
                    continue;
                }
                $realDirectory = realpath($directory);
                if (!$realDirectory || dirname($realDirectory) !== $this->base) {
                    $result['warnings'][] = 'Arkivmappen ligger utanför tillåtet område.';
                    continue;
                }
                $target = $realDirectory . DIRECTORY_SEPARATOR . basename($path);
                // Never overwrite an existing archive, including broken links.
                if (file_exists($target) || is_link($target)) {
                    $result['warnings'][] = basename($path) . ' finns redan i arkivet; originalet lämnades kvar.';
                } elseif (@rename($path, $target)) {
                    $result['moved']++;
                } else {
                    $result['warnings'][] = 'Kunde inte arkivera ' . basename($path) . '.';
                }
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        return $result;
    }

    public function select($catalog, $scope, $folder, $from, $to)
    {
        if ($scope === 'root') return $catalog[''] ?? array();
        if ($scope === 'archive') return $folder !== '' ? ($catalog[$folder] ?? array()) : array();
        if ($scope !== 'period') return array();
        $files = array();
        foreach ($catalog as $entries) {
            foreach ($entries as $label => $path) {
                $date = self::date(basename($path));
                if (!$date) continue;
                $day = $date->format('Y-m-d');
                if (($from === '' || $day >= $from) && ($to === '' || $day <= $to)) $files[$label] = $path;
            }
        }
        uksort($files, function ($a, $b) {
            $comparison = self::date(basename($b)) <=> self::date(basename($a));
            return $comparison ?: strnatcasecmp($a, $b);
        });
        return $files;
    }
}
