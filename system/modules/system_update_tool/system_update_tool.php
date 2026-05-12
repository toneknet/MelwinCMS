<?php
/*
Name: System update tool
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Verktyg för systembackup inför uppdateringar
Version: 0.2
Type: Module
*/

class system_update_tool
{
    private $repoUrl = '';

    public function __construct($arrModuleSettings = array())
    {
        if (isset($arrModuleSettings['ajax']) && $arrModuleSettings['ajax']) {
            if (isset($GLOBALS['CONFIG']['repoUrl'])) {
                $this->repoUrl = trim((string) $GLOBALS['CONFIG']['repoUrl']);
            }
            return;
        }

        if (isset($GLOBALS['CONFIG']['repoUrl'])) {
            $this->repoUrl = trim((string) $GLOBALS['CONFIG']['repoUrl']);
        }
        $action = input::_get('action');
        $step = intval(input::_get('step'));

        print "<h2>System Update Tool</h2>";
        print "<p>Repo: <code>" . htmlspecialchars($this->repoUrl) . "</code></p>";

        if ($this->repoUrl === '') {
            print "<p><strong>repoUrl saknas i CONFIG.</strong></p>";
            return;
        }
        createBtn(array('title' => 'Starta install (step 0: backup)', 'url' => array('action' => 'install', 'step' => 0)));
        createBtn(array('title' => 'Kör install (step 1)', 'url' => array('action' => 'install', 'step' => 1)));

        if ($action === 'install' && $step === 0) {
            $this->run_install_step_backup(input::_get('mode'));
        } elseif ($action === 'install' && $step === 1) {
            $this->run_install_step_install();
        }
    }

    /**
     * Skapar en zip-backup av filer som tillhör repo och ligger i resources/, system/, tpl/, index.php, .htaccess.
     *
     * @return string Sökväg till skapad zip-fil.
     * @throws Exception
     */
    public function create_system_backup()
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive saknas på servern.');
        }

        if (!isset($GLOBALS['CONFIG']['uploadDir']) || trim($GLOBALS['CONFIG']['uploadDir']) === '') {
            throw new Exception('CONFIG uploadDir saknas.');
        }

        $uploadDir = trim($GLOBALS['CONFIG']['uploadDir']);
        $uploadDir = str_replace('\\', '/', $uploadDir);
        $uploadDir = trim($uploadDir, '/');

        $backupDir = ROOTPATH . $uploadDir . '/.backup/';
        $this->ensure_backup_directory($backupDir);

        $filename = date('Y-m-d_His') . '.zip';
        $zipPath = $backupDir . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Kunde inte skapa zip-filen: ' . $zipPath);
        }

        $backupFiles = $this->get_different_local_files();
        $this->render_status('Hittade ' . count($backupFiles) . ' lokala filer som skiljer sig från repo.', 20);

        $total = max(count($backupFiles), 1);
        $current = 0;
        foreach ($backupFiles as $relativePath) {
            $current++;
            $absolutePath = ROOTPATH . $relativePath;
            if (!is_file($absolutePath)) {
                continue;
            }
            $zip->addFile($absolutePath, $relativePath);
            $progress = 20 + intval(($current / $total) * 75);
            $this->render_status('Backupar: ' . $relativePath, $progress);
        }

        $zip->close();
        $this->render_status('Backup klar: ' . $zipPath, 100);

        return $zipPath;
    }

    private function run_install_step_backup($mode = '')
    {
        print "<h3>Steg 0/1: Backup</h3>";
        createBtn(array('title' => 'Kontrollera om uppdatering behövs', 'url' => array('action' => 'install', 'step' => 0, 'mode' => 'check_update')));
        createBtn(array('title' => 'Starta backup (endast filer som skiljer sig)', 'url' => array('action' => 'install', 'step' => 0, 'mode' => 'backup')));

        if ($mode !== 'check_update' && $mode !== 'backup') {
            print "<p>Välj först om du vill kontrollera uppdateringsbehov eller starta backup.</p>";
            return;
        }

        print "<div id='sut_status_text'>Initierar...</div>";
        print "<div style='height:16px;background:#eee;border-radius:4px;overflow:hidden;max-width:500px;'>";
        print "<div id='sut_bar' style='height:16px;background:#4CAF50;width:0%;'></div>";
        print "</div>";
        print "<div id='sut_done' style='margin-top:8px;'></div>";
        print "<script>
          (function(){
            var endpoint = URLroot + 'system/ajax/ajaxRequest.php?action=system_update_tool';
            function setStatus(msg, pct) {
              document.getElementById('sut_status_text').innerHTML = msg;
              document.getElementById('sut_bar').style.width = pct + '%';
            }
            if ('" . $mode . "' === 'check_update') {
              $.getJSON(endpoint + '&subaction=check_update', function(res){
                if (!res || !res.success) {
                  setStatus('Fel: ' + (res && res.error ? res.error : 'okänt fel'), 100);
                  return;
                }
                setStatus(res.message, 100);
                document.getElementById('sut_done').innerHTML = '<strong>Antal skillnader:</strong> ' + res.mismatch_count;
              }).fail(function(){
                setStatus('Ajax-fel vid check_update', 100);
              });
              return;
            }
            function processChunk() {
              $.getJSON(endpoint + '&subaction=backup_process', function(res){
                if (!res || !res.success) {
                  setStatus('Fel: ' + (res && res.error ? res.error : 'okänt fel'), 100);
                  return;
                }
                setStatus(res.message, res.progress);
                if (res.done) {
                  document.getElementById('sut_done').innerHTML = '<strong>Backup skapad:</strong> <code>' + res.zip_path + '</code>';
                } else {
                  processChunk();
                }
              }).fail(function(xhr){
                setStatus('Ajax-fel vid backup_process', 100);
              });
            }
            $.getJSON(endpoint + '&subaction=backup_init', function(res){
              if (!res || !res.success) {
                setStatus('Fel: ' + (res && res.error ? res.error : 'okänt fel'), 100);
                return;
              }
              setStatus(res.message, res.progress);
              processChunk();
            }).fail(function(){
              setStatus('Ajax-fel vid backup_init', 100);
            });
          })();
        </script>";
        createBtn(array('title' => 'Fortsätt till install (step 1)', 'url' => array('action' => 'install', 'step' => 1)));
    }

    private function run_install_step_install()
    {
        print "<h3>Steg 1/1: Install</h3>";
        print "<div id='sut_install_status'>Initierar install...</div>";
        print "<script>
          (function(){
            var endpoint = URLroot + 'system/ajax/ajaxRequest.php?action=system_update_tool';
            $.getJSON(endpoint + '&subaction=install_step', function(res){
              if (!res || !res.success) {
                document.getElementById('sut_install_status').innerHTML = 'Fel: ' + (res && res.error ? res.error : 'okänt fel');
                return;
              }
              document.getElementById('sut_install_status').innerHTML = res.message;
            }).fail(function(){
              document.getElementById('sut_install_status').innerHTML = 'Ajax-fel vid install_step';
            });
          })();
        </script>";
    }

    public function ajax_backup_init($chunkSize = 25)
    {
        $files = $this->get_different_local_files();
        $uploadDir = trim($GLOBALS['CONFIG']['uploadDir']);
        $uploadDir = str_replace('\\', '/', $uploadDir);
        $uploadDir = trim($uploadDir, '/');
        $backupDir = ROOTPATH . $uploadDir . '/.backup/';
        $this->ensure_backup_directory($backupDir);

        $zipPath = $backupDir . date('Y-m-d_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Kunde inte skapa zip-filen: ' . $zipPath);
        }
        $zip->close();

        $_SESSION['system_update_tool_backup'] = array(
            'files' => array_values($files),
            'offset' => 0,
            'total' => count($files),
            'chunk' => max(1, intval($chunkSize)),
            'zipPath' => $zipPath,
        );

        return array(
            'success' => true,
            'done' => count($files) === 0,
            'progress' => 0,
            'message' => 'Backup initierad. Filer att backa upp: ' . count($files),
            'zip_path' => $zipPath,
        );
    }

    public function ajax_backup_process()
    {
        if (!isset($_SESSION['system_update_tool_backup'])) {
            return array('success' => false, 'error' => 'Ingen backup-session hittades.');
        }

        $state = $_SESSION['system_update_tool_backup'];
        $files = $state['files'];
        $offset = intval($state['offset']);
        $total = max(0, intval($state['total']));
        $chunk = max(1, intval($state['chunk']));
        $zipPath = $state['zipPath'];

        if ($offset >= $total) {
            unset($_SESSION['system_update_tool_backup']);
            return array(
                'success' => true,
                'done' => true,
                'progress' => 100,
                'message' => 'Backup klar.',
                'zip_path' => $zipPath,
            );
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return array('success' => false, 'error' => 'Kunde inte öppna zip-fil för skrivning.');
        }

        $end = min($offset + $chunk, $total);
        for ($i = $offset; $i < $end; $i++) {
            $relativePath = $files[$i];
            $absolutePath = ROOTPATH . $relativePath;
            if (is_file($absolutePath)) {
                $zip->addFile($absolutePath, $relativePath);
            }
        }
        $zip->close();

        $state['offset'] = $end;
        $_SESSION['system_update_tool_backup'] = $state;

        $progress = $total > 0 ? intval(($end / $total) * 100) : 100;
        $done = $end >= $total;
        if ($done) {
            unset($_SESSION['system_update_tool_backup']);
        }

        return array(
            'success' => true,
            'done' => $done,
            'progress' => $progress,
            'message' => 'Backat upp ' . $end . ' av ' . $total . ' filer.',
            'zip_path' => $zipPath,
        );
    }

    public function ajax_check_update_needed()
    {
        $mismatch = $this->compare_repo_file_hashes_with_local(false);
        $relevant = array();

        foreach ($mismatch as $path => $row) {
            $status = isset($row['status']) ? $row['status'] : '';
            if (
                $status === 'hash_mismatch' ||
                $status === 'size_mismatch' ||
                $status === 'missing_local_file' ||
                $status === 'missing_local_directory' ||
                $status === 'local_hash_error' ||
                $status === 'local_size_error'
            ) {
                $relevant[$path] = $row;
            }
        }

        $count = count($relevant);
        return array(
            'success' => true,
            'mismatch_count' => $count,
            'needs_update' => $count > 0,
            'message' => $count > 0
                ? 'Uppdatering rekommenderas. Skillnader hittades.'
                : 'Inga skillnader hittades. Ingen uppdatering behövs just nu.',
        );
    }

    /**
     * Hämtar fil-lista från GitHub repo via API.
     *
     * @return array Lista av filvägar relativt repo root.
     * @throws Exception
     */
    public function fetch_repo_file_list()
    {
        $treeNodes = $this->fetch_repo_tree_nodes();

        $files = array();
        foreach ($treeNodes as $node) {
            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }
            $files[] = str_replace('\\', '/', $node['path']);
        }

        return $files;
    }

    /**
     * Hämtar fil-lista från GitHub repo där nyckel är filnamn inkl sökväg och värde är filstorlek i bytes.
     *
     * @return array Assoc-array ["path/to/file.php" => 1234]
     * @throws Exception
     */
    public function fetch_repo_file_sizes()
    {
        $treeNodes = $this->fetch_repo_tree_nodes();

        $filesWithSizes = array();
        foreach ($treeNodes as $node) {
            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }

            $path = str_replace('\\', '/', $node['path']);
            $size = isset($node['size']) ? intval($node['size']) : 0;
            $filesWithSizes[$path] = $size;
        }

        return $filesWithSizes;
    }

    /**
     * Hämtar fil-hashar (Git blob SHA) från repo.
     *
     * @return array Assoc-array ["path/to/file.php" => "sha1"]
     * @throws Exception
     */
    public function fetch_repo_file_hashes()
    {
        $treeNodes = $this->fetch_repo_tree_nodes();
        $filesWithHashes = array();

        foreach ($treeNodes as $node) {
            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }

            $path = str_replace('\\', '/', $node['path']);
            $sha = isset($node['sha']) ? trim($node['sha']) : '';
            $filesWithHashes[$path] = $sha;
        }

        return $filesWithHashes;
    }

    /**
     * Jämför filstorlek i GitHub-repo mot motsvarande fil lokalt på servern.
     *
     * @param bool $showOutput
     * @return array
     * @throws Exception
     */
    public function compare_repo_file_sizes_with_local($showOutput = true)
    {
        $treeNodes = $this->fetch_repo_tree_nodes();
        $arrMismatch = array();

        foreach ($treeNodes as $node) {
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }

            $relativePath = str_replace('\\', '/', $node['path']);
            $localPath = ROOTPATH . $relativePath;

            if (isset($node['type']) && $node['type'] === 'tree') {
                if (!is_dir($localPath)) {
                    $arrMismatch[$relativePath] = array(
                        'status' => 'missing_local_directory',
                        'error_code' => 'ERR_MISSING_LOCAL_DIRECTORY',
                    );
                }
                continue;
            }

            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }

            $repoSize = isset($node['size']) ? intval($node['size']) : 0;

            if (!is_file($localPath)) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'missing_local_file',
                    'error_code' => 'ERR_MISSING_LOCAL_FILE',
                    'repo_size' => $repoSize,
                    'local_size' => null,
                );
                continue;
            }

            $localSize = filesize($localPath);
            if ($localSize === false) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'local_size_error',
                    'error_code' => 'ERR_LOCAL_FILESIZE_FAILED',
                    'repo_size' => $repoSize,
                    'local_size' => null,
                );
                continue;
            }

            if (intval($localSize) !== $repoSize) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'size_mismatch',
                    'error_code' => 'ERR_SIZE_MISMATCH',
                    'repo_size' => $repoSize,
                    'local_size' => intval($localSize),
                );
            }
        }

        if ($showOutput) {
            pre($arrMismatch);
        }

        return $arrMismatch;
    }

    /**
     * Jämför Git blob SHA från repo med lokal fil på servern.
     *
     * @param bool $showOutput
     * @return array
     * @throws Exception
     */
    public function compare_repo_file_hashes_with_local($showOutput = true)
    {
        $treeNodes = $this->fetch_repo_tree_nodes();
        $arrMismatch = array();

        foreach ($treeNodes as $node) {
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }

            $relativePath = str_replace('\\', '/', $node['path']);
            $localPath = ROOTPATH . $relativePath;

            if (isset($node['type']) && $node['type'] === 'tree') {
                if (!is_dir($localPath)) {
                    $arrMismatch[$relativePath] = array(
                        'status' => 'missing_local_directory',
                        'error_code' => 'ERR_MISSING_LOCAL_DIRECTORY',
                    );
                }
                continue;
            }

            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }

            $repoSha = isset($node['sha']) ? trim($node['sha']) : '';
            if (!is_file($localPath)) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'missing_local_file',
                    'error_code' => 'ERR_MISSING_LOCAL_FILE',
                    'repo_sha' => $repoSha,
                    'local_sha' => null,
                );
                continue;
            }

            $localSha = $this->calculate_git_blob_sha_for_local_file($localPath);
            if ($localSha === null) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'local_hash_error',
                    'error_code' => 'ERR_LOCAL_HASH_FAILED',
                    'repo_sha' => $repoSha,
                    'local_sha' => null,
                );
                continue;
            }

            if ($repoSha !== '' && $localSha !== $repoSha) {
                $arrMismatch[$relativePath] = array(
                    'status' => 'hash_mismatch',
                    'error_code' => 'ERR_HASH_MISMATCH',
                    'repo_sha' => $repoSha,
                    'local_sha' => $localSha,
                );
            }
        }

        if ($showOutput) {
            pre($arrMismatch);
        }

        return $arrMismatch;
    }

    private function calculate_git_blob_sha_for_local_file($filePath)
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        return sha1('blob ' . strlen($content) . "\0" . $content);
    }

    /**
     * Returnerar ENDAST lokala filer som finns och skiljer sig från repo (size/hash) eller saknas lokalt.
     * För backup används bara befintliga lokala filer.
     */
    private function get_different_local_files()
    {
        $mismatch = $this->compare_repo_file_hashes_with_local(false);
        $files = array();

        foreach ($mismatch as $relativePath => $meta) {
            if (!isset($meta['status'])) {
                continue;
            }
            if ($meta['status'] !== 'hash_mismatch' && $meta['status'] !== 'size_mismatch' && $meta['status'] !== 'local_hash_error') {
                continue;
            }

            $absolutePath = ROOTPATH . $relativePath;
            if (is_file($absolutePath)) {
                $files[] = $relativePath;
            }
        }

        return array_values(array_unique($this->filter_backup_files($files)));
    }

    private function render_status($message, $percent = 0)
    {
        $percent = max(0, min(100, intval($percent)));
        print '<div style="margin:8px 0;padding:8px;border:1px solid #ddd;">';
        print '<div style="font-weight:bold;margin-bottom:4px;">' . htmlspecialchars($message) . '</div>';
        print '<div style="height:14px;background:#eee;border-radius:4px;overflow:hidden;">';
        print '<div style="height:14px;background:#4CAF50;width:' . $percent . '%;"></div>';
        print '</div>';
        print '<div style="font-size:11px;color:#666;">' . $percent . '%</div>';
        print '</div>';

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }

    private function filter_backup_files(array $repoFiles)
    {
        $allowedRoots = array('resources/', 'system/', 'tpl/');
        $allowedFiles = array('index.php', '.htaccess');

        $backupFiles = array();
        foreach ($repoFiles as $path) {
            if (in_array($path, $allowedFiles, true)) {
                $backupFiles[] = $path;
                continue;
            }

            foreach ($allowedRoots as $root) {
                if (strpos($path, $root) === 0) {
                    $backupFiles[] = $path;
                    break;
                }
            }
        }

        return array_values(array_unique($backupFiles));
    }

    private function fetch_repo_tree_nodes()
    {
        list($owner, $repo) = $this->parse_github_repo_url($this->repoUrl);

        $repoMetaUrl = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo);
        $repoMeta = $this->http_get_json($repoMetaUrl);
        if (!isset($repoMeta['default_branch']) || $repoMeta['default_branch'] === '') {
            throw new Exception('Kunde inte läsa default branch från GitHub.');
        }

        $branch = $repoMeta['default_branch'];
        $treeUrl = 'https://api.github.com/repos/'
            . rawurlencode($owner) . '/'
            . rawurlencode($repo)
            . '/git/trees/' . rawurlencode($branch) . '?recursive=1';

        $treeData = $this->http_get_json($treeUrl);
        if (!isset($treeData['tree']) || !is_array($treeData['tree'])) {
            throw new Exception('Kunde inte läsa träd-data från GitHub.');
        }

        return $treeData['tree'];
    }

    private function parse_github_repo_url($repoUrl)
    {
        $parsed = parse_url(trim($repoUrl));
        if (!is_array($parsed) || !isset($parsed['host'], $parsed['path'])) {
            throw new Exception('Ogiltig repo-url: ' . $repoUrl);
        }

        $host = strtolower($parsed['host']);
        if ($host !== 'github.com' && $host !== 'www.github.com') {
            throw new Exception('Endast GitHub-repon stöds: ' . $repoUrl);
        }

        $parts = array_values(array_filter(explode('/', trim($parsed['path'], '/'))));
        if (count($parts) < 2) {
            throw new Exception('Repo-url måste vara i format owner/repo.');
        }

        return array($parts[0], $parts[1]);
    }

    private function http_get_json($url)
    {
        $headers = array(
            'User-Agent: hbook-system-update-tool',
            'Accept: application/vnd.github+json',
        );
        if (isset($GLOBALS['CONFIG']['repoUrlAuth']) && trim($GLOBALS['CONFIG']['repoUrlAuth']) !== '') {
            $headers[] = 'Authorization: Bearer ' . trim($GLOBALS['CONFIG']['repoUrlAuth']);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $statusCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('cURL-fel vid anrop till: ' . $url . ' (' . $curlErr . ')');
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception('GitHub API svarade med HTTP ' . $statusCode . ' för: ' . $url);
            }
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => 20,
                    'ignore_errors' => true,
                ),
            ));

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new Exception('HTTP-fel vid anrop till: ' . $url);
            }

            if (!isset($http_response_header) || !is_array($http_response_header) || count($http_response_header) === 0) {
                throw new Exception('Saknar HTTP-svar vid anrop till: ' . $url);
            }

            $statusLine = $http_response_header[0];
            if (!preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
                throw new Exception('Kunde inte tolka HTTP-status: ' . $statusLine);
            }

            $statusCode = intval($matches[1]);
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception('GitHub API svarade med HTTP ' . $statusCode . ' för: ' . $url);
            }
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new Exception('Ogiltigt JSON-svar från: ' . $url);
        }

        return $decoded;
    }

    private function ensure_backup_directory($backupDir)
    {
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0770, true) && !is_dir($backupDir)) {
                throw new Exception('Kunde inte skapa backupmapp: ' . $backupDir);
            }
        }

        $htaccessPath = $backupDir . '.htaccess';
        $htaccessContent = "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\n"
            . "  Require all denied\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "  Deny from all\n"
            . "</IfModule>\n";

        file_put_contents($htaccessPath, $htaccessContent);

        $indexPath = $backupDir . 'index.php';
        if (!is_file($indexPath)) {
            file_put_contents($indexPath, "<?php\n// Silence is golden.\n");
        }
    }
}
