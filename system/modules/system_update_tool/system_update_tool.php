<?php
/*
Name: System update tool
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Verktyg för systemuppdatering via GitHub-repo
Version: 0.4
Type: Module
*/

class system_update_tool
{
    private $repoUrls = [];
    private $repoAuth = '';
    private $showDebug = false;

    public function __construct($arrModuleSettings = [])
    {
        $this->repoUrls = [
            'core'   => trim((string)($GLOBALS['CONFIG']['repoUrl'] ?? '')),
            'addons' => trim((string)($GLOBALS['CONFIG']['repoUrlAddons'] ?? '')),
        ];
        $this->repoAuth = trim((string)($GLOBALS['CONFIG']['repoUrlAuth'] ?? ''));

        if (isset($arrModuleSettings['ajax']) && $arrModuleSettings['ajax']) {
            return;
        }

        $this->render_ui();
    }

    // -------------------------------------------------------------------------
    // UI
    // -------------------------------------------------------------------------

    private function l(string $key): string
    {
        return (string)($GLOBALS['LANG']['system_update_tool'][$key] ?? $key);
    }

    private function render_ui()
    {
        print '<h2>' . $this->l('title') . '</h2>';

        foreach (['core' => 'Core', 'addons' => 'Addons'] as $type => $label) {
            $url    = $this->repoUrls[$type];
            $cfgKey = $type === 'core' ? 'repoUrl' : 'repoUrlAddons';

            print "<div style='margin-bottom:24px;border:1px solid #ccc;padding:16px;border-radius:4px;'>";
            print "<h3 style='margin-top:0;'>{$label}</h3>";

            if ($url === '') {
                print '<p><em><code>' . $cfgKey . '</code> ' . $this->l('config_missing') . '</em></p>';
                print "</div>";
                continue;
            }

            print '<p>' . $this->l('repo') . ' <code>' . htmlspecialchars($url) . '</code></p>';

            createBtn(['title' => $this->l('btn_check'),   'class' => "sut-check-{$type}"]);
            if ($this->showDebug) createBtn(['title' => $this->l('btn_diff'),    'class' => "sut-diff-{$type}"]);
            createBtn(['title' => $this->l('btn_backup'),  'class' => "sut-backup-{$type}"]);
            createBtn(['title' => $this->l('btn_install'), 'class' => "sut-install-{$type}"]);

            print "<div id='sut_msg_{$type}' style='margin-top:10px;color:#333;'></div>";
            print "<div id='sut_bar_wrap_{$type}' style='height:16px;background:#eee;border-radius:4px;overflow:hidden;max-width:500px;margin-top:6px;display:none;'>";
            print "<div id='sut_bar_{$type}' style='height:16px;background:#4CAF50;width:0%;transition:width .2s;'></div>";
            print "</div>";
            print "<div id='sut_done_{$type}' style='margin-top:8px;'></div>";
            print "<div id='sut_diff_{$type}' style='margin-top:12px;display:none;'></div>";

            print "</div>";
        }

        $this->render_js_lang();
        $this->render_js();
    }

    private function render_js_lang(): void
    {
        $all  = $GLOBALS['LANG']['system_update_tool'] ?? [];
        $jsLang = [];
        foreach ($all as $key => $val) {
            if (strpos($key, 'js_') === 0) {
                $jsLang[substr($key, 3)] = $val;
            }
        }
        print '<script>var SUT_L = ' . json_encode($jsLang, JSON_UNESCAPED_UNICODE) . ';</script>';
    }

    private function render_js()
    {
        print "<script> var showDebug = ". ($this->showDebug ? 'true' : 'false') . ";</script>";
        print <<<'JS'
<script>
(function(){
    var ep = URLroot + 'system/ajax/ajaxRequest.php?action=system_update_tool';
    function msg(type, text, pct, showBar) {
        var m = document.getElementById('sut_msg_' + type);
        var b = document.getElementById('sut_bar_' + type);
        var w = document.getElementById('sut_bar_wrap_' + type);
        if (m) m.innerHTML = text;
        if (b) b.style.width = (pct || 0) + '%';
        if (w) w.style.display = showBar ? '' : 'none';
    }

    function done(type, html) {
        var el = document.getElementById('sut_done_' + type);
        if (el) el.innerHTML = html || '';
    }

    function err(res) { return SUT_L.error + ' ' + (res && res.error ? res.error : SUT_L.unknown); }

    function doCheck(type) {
        msg(type, SUT_L.checking, 0, true);
        done(type, '');
        $.getJSON(ep + '&subaction=check_update&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            msg(type, res.message, 100, true);
            done(type, '<strong>' + SUT_L.differences + '</strong> ' + res.mismatch_count);
        }).fail(function(){ msg(type, SUT_L.ajax_err_check, 100, true); });
    }

    function doBackup(type) {
        msg(type, SUT_L.backup_init, 0, true);
        done(type, '');
        $.getJSON(ep + '&subaction=backup_init&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) { done(type, '<strong>' + SUT_L.no_backup_files + '</strong>'); } else { processBackup(type); }
        }).fail(function(){ msg(type, SUT_L.ajax_err_bk_init, 100, true); });
    }

    function processBackup(type) {
        $.getJSON(ep + '&subaction=backup_process&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) { done(type, '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.zip_path + '</code>'); } else { processBackup(type); }
        }).fail(function(){ msg(type, SUT_L.ajax_err_backup, 100, true); });
    }

    function doInstall(type) {
        if (!confirm(SUT_L.install_confirm.replace('{type}', type))) return;
        msg(type, SUT_L.install_preparing, 0, true);
        done(type, '');
        $.getJSON(ep + '&subaction=install_init&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            if (res.done) { msg(type, res.message, 100, true); return; }
            msg(type, res.message, 5, true);
            if (res.backup_path) { done(type, '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code>'); }
            processInstall(type);
        }).fail(function(){ msg(type, SUT_L.ajax_err_in_init, 100, true); });
    }

    function processInstall(type) {
        $.getJSON(ep + '&subaction=install_process&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) {
                var doneHtml = (res.backup_path ? '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code><br>' : '')
                             + '<strong style="color:#2a7a2a;">' + SUT_L.install_done + ' ' + res.installed + ' ' + SUT_L.files_updated + '</strong>';
                if (res.errors && res.errors.length) {
                    doneHtml += '<br><span style="color:#c0392b;">' + SUT_L.error + ' ' + res.errors.join(', ') + '</span>';
                }
                done(type, doneHtml);
            } else {
                processInstall(type);
            }
        }).fail(function(){ msg(type, SUT_L.ajax_err_install, 100, true); });
    }

    function doDiff(type) {
        msg(type, SUT_L.diff_loading, 0, true);
        done(type, '');
        var diffEl = document.getElementById('sut_diff_' + type);
        if (diffEl) { diffEl.style.display = 'none'; diffEl.innerHTML = ''; }

        $.getJSON(ep + '&subaction=debug_diff&type=' + type, function(res){
            if (!res || !res.success) { msg(type, err(res), 100, true); return; }
            msg(type, SUT_L.diff_done, 100, true);
            renderDiff(type, res);
        }).fail(function(){ msg(type, SUT_L.ajax_err_diff, 100, true); });
    }

    function renderDiff(type, res) {
        var diffEl = document.getElementById('sut_diff_' + type);
        if (!diffEl) return;

        var mod  = res.different   || [];
        var add  = res.new_in_repo || [];
        var del  = res.local_only  || [];
        var total = mod.length + add.length + del.length;

        var html = '<div style="font-family:monospace;font-size:13px;">';

        html += '<div style="margin-bottom:10px;padding:8px 12px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;">';
        html += '<strong>' + SUT_L.diff_summary + '</strong> &nbsp;&nbsp;';
        html += '<span style="color:#e67e00;">&#9679; ' + mod.length + ' ' + SUT_L.diff_modified + '</span> &nbsp;&nbsp;';
        html += '<span style="color:#2a7a2a;">&#9679; ' + add.length + ' ' + SUT_L.diff_new + '</span> &nbsp;&nbsp;';
        if (showDebug) html += '<span style="color:#888;">&#9679; ' + del.length + ' ' + SUT_L.diff_local + '</span>';
        if (total === 0) { html += ' &nbsp;&nbsp;<strong style="color:#555;">&#10003; ' + SUT_L.diff_clean + '</strong>'; }
        html += '</div>';

        if (total === 0) {
            html += '<div style="color:#555;padding:8px;">' + SUT_L.diff_no_diff + '</div>';
            html += '</div>';
            diffEl.innerHTML = html;
            diffEl.style.display = '';
            return;
        }

        html += '<div style="border:1px solid #ddd;border-radius:4px;overflow:hidden;">';
        html += '<div style="max-height:480px;overflow-y:auto;padding:8px 0;">';

        function row(prefix, color, bg, path) {
            return '<div style="padding:2px 12px;background:' + bg + ';white-space:nowrap;">'
                 + '<span style="color:' + color + ';font-weight:bold;display:inline-block;width:22px;">' + prefix + '</span>'
                 + '<span style="color:#333;">' + escHtml(path) + '</span>'
                 + '</div>';
        }

        if (mod.length) {
            html += '<div style="padding:4px 12px 2px;background:#fff8e1;font-size:11px;color:#999;border-bottom:1px solid #eee;">' + SUT_L.diff_sec_modified + ' (' + mod.length + ')</div>';
            mod.sort().forEach(function(p){ html += row('M', '#e67e00', '#fffde7', p); });
        }
        if (add.length) {
            html += '<div style="padding:4px 12px 2px;background:#e8f5e9;font-size:11px;color:#999;border-bottom:1px solid #eee;">' + SUT_L.diff_sec_new + ' (' + add.length + ')</div>';
            add.sort().forEach(function(p){ html += row('A', '#2a7a2a', '#f1f9f1', p); });
        }
        if (del.length && showDebug) {
            html += '<div style="padding:4px 12px 2px;background:#f5f5f5;font-size:11px;color:#999;border-bottom:1px solid #eee;">' + SUT_L.diff_sec_local + ' (' + del.length + ')</div>';
            del.sort().forEach(function(p){ html += row('?', '#888', '#fafafa', p); });
        }

        html += '</div></div></div>';

        diffEl.innerHTML = html;
        diffEl.style.display = '';
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    ['core', 'addons'].forEach(function(type) {
        var c = document.querySelector('.sut-check-'   + type);
        var d = document.querySelector('.sut-diff-'    + type);
        var b = document.querySelector('.sut-backup-'  + type);
        var i = document.querySelector('.sut-install-' + type);
        if (c) c.addEventListener('click', function(e){ e.preventDefault(); doCheck(type); });
        if (d) d.addEventListener('click', function(e){ e.preventDefault(); doDiff(type); });
        if (b) b.addEventListener('click', function(e){ e.preventDefault(); doBackup(type); });
        if (i) i.addEventListener('click', function(e){ e.preventDefault(); doInstall(type); });
    });
})();
</script>
JS;
    }

    // -------------------------------------------------------------------------
    // AJAX entry points
    // -------------------------------------------------------------------------

    public function ajax_check_update_needed($type)
    {
        $mismatch = $this->compare_repo_file_hashes_with_local(false, $type);
        $relevant_statuses = ['hash_mismatch', 'size_mismatch', 'missing_local_file', 'missing_local_directory', 'local_hash_error', 'local_size_error'];
        $relevant = array_filter($mismatch, function($row) use ($relevant_statuses) {
            return in_array($row['status'] ?? '', $relevant_statuses, true);
        });
        $count = count($relevant);
        return [
            'success'        => true,
            'mismatch_count' => $count,
            'needs_update'   => $count > 0,
            'message'        => $count > 0
                ? sprintf($this->l('msg_update_needed'), $count)
                : $this->l('msg_no_update'),
        ];
    }

    public function ajax_debug_diff(string $type): array
    {
        $treeNodes = $this->fetch_repo_tree_nodes($this->repoUrls[$type]);

        // Index repo files by relative path → sha
        $repoIndex = [];
        foreach ($treeNodes as $node) {
            if (($node['type'] ?? '') !== 'blob') {
                continue;
            }
            $path = str_replace('\\', '/', $node['path'] ?? '');
            if ($path === '' || !$this->path_belongs_to_type($path, $type)) {
                continue;
            }
            $repoIndex[$path] = trim($node['sha'] ?? '');
        }

        $different   = [];
        $new_in_repo = [];

        foreach ($repoIndex as $path => $repoSha) {
            $localPath = ROOTPATH . $path;
            if (!is_file($localPath)) {
                $new_in_repo[] = $path;
                continue;
            }
            $localSha = $this->calculate_git_blob_sha($localPath);
            if ($repoSha !== '' && $localSha !== $repoSha) {
                $different[] = $path;
            }
        }

        // Files that exist locally but are absent from the repo
        $localFiles = $this->scan_local_files($type);
        $local_only = [];
        foreach ($localFiles as $localPath) {
            if (!isset($repoIndex[$localPath])) {
                $local_only[] = $localPath;
            }
        }

        sort($different);
        sort($new_in_repo);
        sort($local_only);

        return [
            'success'     => true,
            'different'   => $different,
            'new_in_repo' => $new_in_repo,
            'local_only'  => $local_only,
        ];
    }

    public function ajax_backup_init($type, $chunkSize = 25)
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception($this->l('err_no_ziparchive'));
        }

        $files   = $this->get_different_local_files($type);
        $zipPath = $this->make_zip_path($type);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Kunde inte skapa zip-filen: ' . $zipPath);
        }
        $zip->close();

        $_SESSION['sut_backup_' . $type] = [
            'files'   => array_values($files),
            'offset'  => 0,
            'total'   => count($files),
            'chunk'   => max(1, intval($chunkSize)),
            'zipPath' => $zipPath,
        ];

        return [
            'success'  => true,
            'done'     => count($files) === 0,
            'progress' => 0,
            'message'  => sprintf($this->l('msg_backup_init'), count($files)),
            'zip_path' => $zipPath,
        ];
    }

    public function ajax_backup_process($type)
    {
        $key = 'sut_backup_' . $type;
        if (!isset($_SESSION[$key])) {
            return ['success' => false, 'error' => $this->l('err_no_backup_sess')];
        }

        $state   = $_SESSION[$key];
        $files   = $state['files'];
        $offset  = intval($state['offset']);
        $total   = max(0, intval($state['total']));
        $chunk   = max(1, intval($state['chunk']));
        $zipPath = $state['zipPath'];

        if ($offset >= $total) {
            unset($_SESSION[$key]);
            return ['success' => true, 'done' => true, 'progress' => 100, 'message' => $this->l('msg_backup_done'), 'zip_path' => $zipPath];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return ['success' => false, 'error' => $this->l('err_zip_write')];
        }

        $end = min($offset + $chunk, $total);
        for ($i = $offset; $i < $end; $i++) {
            $abs = ROOTPATH . $files[$i];
            if (is_file($abs)) {
                $zip->addFile($abs, $files[$i]);
            }
        }
        $zip->close();

        $state['offset'] = $end;
        $_SESSION[$key]  = $state;

        $progress = $total > 0 ? intval(($end / $total) * 100) : 100;
        $done     = $end >= $total;
        if ($done) {
            unset($_SESSION[$key]);
        }

        return [
            'success'  => true,
            'done'     => $done,
            'progress' => $progress,
            'message'  => sprintf($this->l('msg_backup_progress'), $end, $total),
            'zip_path' => $zipPath,
        ];
    }

    // -------------------------------------------------------------------------
    // Install
    // -------------------------------------------------------------------------

    public function ajax_install_init(string $type): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception($this->l('err_no_ziparchive'));
        }

        $mismatch  = $this->compare_repo_file_hashes_with_local(false, $type);
        $toInstall = [];
        $updateStatuses = ['hash_mismatch', 'size_mismatch', 'local_hash_error', 'missing_local_file'];

        foreach ($mismatch as $path => $meta) {
            if (in_array($meta['status'] ?? '', $updateStatuses, true)) {
                $toInstall[] = $path;
            }
        }

        if (empty($toInstall)) {
            return ['success' => true, 'done' => true, 'progress' => 100, 'message' => $this->l('msg_no_install')];
        }

        // Backup only files that already exist locally (will be overwritten)
        $toBackup = array_filter($toInstall, function (string $p): bool {
            return is_file(ROOTPATH . $p);
        });

        $zipPath = null;
        if (!empty($toBackup)) {
            $zipPath = $this->make_zip_path($type . '_pre_install');
            $zip     = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Kunde inte skapa backup-zip: ' . $zipPath);
            }
            foreach ($toBackup as $path) {
                $zip->addFile(ROOTPATH . $path, $path);
            }
            $zip->close();
        }

        [$owner, $repo, $branch] = $this->get_repo_info($type);

        $_SESSION['sut_install_' . $type] = [
            'files'     => array_values($toInstall),
            'offset'    => 0,
            'total'     => count($toInstall),
            'chunk'     => 5,
            'zipPath'   => $zipPath,
            'owner'     => $owner,
            'repo'      => $repo,
            'branch'    => $branch,
            'installed' => 0,
        ];

        $message = $zipPath
            ? sprintf($this->l('msg_install_init'), count($toInstall), count($toBackup))
            : sprintf($this->l('msg_install_init_nb'), count($toInstall));

        return [
            'success'     => true,
            'done'        => false,
            'progress'    => 5,
            'message'     => $message,
            'backup_path' => $zipPath,
        ];
    }

    public function ajax_install_process(string $type): array
    {
        $key = 'sut_install_' . $type;
        if (!isset($_SESSION[$key])) {
            return ['success' => false, 'error' => $this->l('err_no_install_sess')];
        }

        $state     = $_SESSION[$key];
        $files     = $state['files'];
        $offset    = intval($state['offset']);
        $total     = intval($state['total']);
        $chunk     = intval($state['chunk']);
        $installed = intval($state['installed']);
        $errors    = [];

        $end = min($offset + $chunk, $total);
        for ($i = $offset; $i < $end; $i++) {
            try {
                $this->download_and_write_file($state['owner'], $state['repo'], $state['branch'], $files[$i]);
                $installed++;
            } catch (Exception $e) {
                $errors[] = $files[$i] . ': ' . $e->getMessage();
            }
        }

        $state['offset']    = $end;
        $state['installed'] = $installed;
        $_SESSION[$key]     = $state;

        $progress = intval(5 + ($end / $total) * 95);
        $done     = $end >= $total;
        if ($done) {
            unset($_SESSION[$key]);
        }

        return [
            'success'     => true,
            'done'        => $done,
            'progress'    => $done ? 100 : $progress,
            'message'     => sprintf($this->l('msg_install_progress'), $end, $total),
            'installed'   => $installed,
            'backup_path' => $state['zipPath'],
            'errors'      => $errors,
        ];
    }

    private function download_and_write_file(string $owner, string $repo, string $branch, string $path): void
    {
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));
        $rawUrl      = 'https://raw.githubusercontent.com/'
            . rawurlencode($owner) . '/'
            . rawurlencode($repo) . '/'
            . rawurlencode($branch) . '/'
            . $encodedPath;

        $content   = $this->http_get_raw($rawUrl);
        $localPath = ROOTPATH . $path;
        $dir       = dirname($localPath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception('Kunde inte skapa katalog: ' . $dir);
        }

        if (file_put_contents($localPath, $content) === false) {
            throw new Exception('Kunde inte skriva fil: ' . $localPath);
        }
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    private function compare_repo_file_hashes_with_local($showOutput, $type)
    {
        $treeNodes   = $this->fetch_repo_tree_nodes($this->repoUrls[$type]);
        $arrMismatch = [];

        foreach ($treeNodes as $node) {
            if (!isset($node['path']) || trim($node['path']) === '') {
                continue;
            }

            $relativePath = str_replace('\\', '/', $node['path']);
            if (!$this->path_belongs_to_type($relativePath, $type)) {
                continue;
            }

            $localPath = ROOTPATH . $relativePath;

            if (isset($node['type']) && $node['type'] === 'tree') {
                if (!is_dir($localPath)) {
                    $arrMismatch[$relativePath] = ['status' => 'missing_local_directory', 'error_code' => 'ERR_MISSING_LOCAL_DIRECTORY'];
                }
                continue;
            }

            if (!isset($node['type']) || $node['type'] !== 'blob') {
                continue;
            }

            $repoSha = isset($node['sha']) ? trim($node['sha']) : '';

            if (!is_file($localPath)) {
                $arrMismatch[$relativePath] = ['status' => 'missing_local_file', 'error_code' => 'ERR_MISSING_LOCAL_FILE', 'repo_sha' => $repoSha, 'local_sha' => null];
                continue;
            }

            $localSha = $this->calculate_git_blob_sha($localPath);
            if ($localSha === null) {
                $arrMismatch[$relativePath] = ['status' => 'local_hash_error', 'error_code' => 'ERR_LOCAL_HASH_FAILED', 'repo_sha' => $repoSha, 'local_sha' => null];
                continue;
            }

            if ($repoSha !== '' && $localSha !== $repoSha) {
                $arrMismatch[$relativePath] = ['status' => 'hash_mismatch', 'error_code' => 'ERR_HASH_MISMATCH', 'repo_sha' => $repoSha, 'local_sha' => $localSha];
            }
        }

        if ($showOutput) {
            pre($arrMismatch);
        }

        return $arrMismatch;
    }

    private function get_different_local_files($type)
    {
        $mismatch = $this->compare_repo_file_hashes_with_local(false, $type);
        $files = [];
        foreach ($mismatch as $relativePath => $meta) {
            $status = $meta['status'] ?? '';
            if (!in_array($status, ['hash_mismatch', 'size_mismatch', 'local_hash_error'], true)) {
                continue;
            }
            if (is_file(ROOTPATH . $relativePath)) {
                $files[] = $relativePath;
            }
        }
        return array_values(array_unique($files));
    }

    // -------------------------------------------------------------------------
    // Local filesystem scan (for debug diff)
    // -------------------------------------------------------------------------

    private function scan_local_files(string $type): array
    {
        $files = [];

        if ($type === 'addons') {
            $this->scan_dir(ROOTPATH . 'system/modules/', 'system/modules/', $files);
            return $files;
        }

        // core: resources/, tpl/, system/ (excluding system/modules/), root files
        foreach (['resources/' => ROOTPATH . 'resources/', 'tpl/' => ROOTPATH . 'tpl/'] as $prefix => $absDir) {
            if (is_dir($absDir)) {
                $this->scan_dir($absDir, $prefix, $files);
            }
        }

        if (is_dir(ROOTPATH . 'system/')) {
            $this->scan_dir_filtered(ROOTPATH . 'system/', 'system/', ['system/modules/'], $files);
        }

        foreach (['index.php', '.htaccess'] as $f) {
            if (is_file(ROOTPATH . $f)) {
                $files[] = $f;
            }
        }

        return $files;
    }

    private function scan_dir(string $absDir, string $relPrefix, array &$files): void
    {
        if (!is_dir($absDir)) {
            return;
        }
        $base = rtrim(str_replace('\\', '/', $absDir), '/') . '/';
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $abs     = str_replace('\\', '/', $fileInfo->getPathname());
            $files[] = $relPrefix . substr($abs, strlen($base));
        }
    }

    private function scan_dir_filtered(string $absDir, string $relPrefix, array $excludePrefixes, array &$files): void
    {
        if (!is_dir($absDir)) {
            return;
        }
        $base = rtrim(str_replace('\\', '/', $absDir), '/') . '/';
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $abs = str_replace('\\', '/', $fileInfo->getPathname());
            $rel = $relPrefix . substr($abs, strlen($base));
            foreach ($excludePrefixes as $excl) {
                if (strpos($rel, $excl) === 0) {
                    continue 2;
                }
            }
            $files[] = $rel;
        }
    }

    // -------------------------------------------------------------------------
    // Path filtering
    // -------------------------------------------------------------------------

    private function path_belongs_to_type(string $path, string $type): bool
    {
        if ($type === 'addons') {
            return strpos($path, 'system/modules/') === 0;
        }

        if (strpos($path, 'system/modules/') === 0) {
            return false;
        }

        $coreRoots = ['resources/', 'system/', 'tpl/'];
        $coreFiles = ['index.php', '.htaccess'];

        if (in_array($path, $coreFiles, true)) {
            return true;
        }

        foreach ($coreRoots as $root) {
            if (strpos($path, $root) === 0) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // GitHub API
    // -------------------------------------------------------------------------

    private function fetch_repo_tree_nodes($repoUrl)
    {
        [$owner, $repo] = $this->parse_github_repo_url($repoUrl);

        $metaUrl  = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo);
        $repoMeta = $this->http_get_json($metaUrl);

        if (!isset($repoMeta['default_branch']) || $repoMeta['default_branch'] === '') {
            throw new Exception('Kunde inte läsa default branch från GitHub.');
        }

        $branch  = $repoMeta['default_branch'];
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

        return [$parts[0], $parts[1]];
    }

    private function http_get_json($url)
    {
        $headers = [
            'User-Agent: hbook-system-update-tool',
            'Accept: application/vnd.github+json',
        ];
        if ($this->repoAuth !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->repoAuth;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response   = curl_exec($ch);
            $statusCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            $curlErr    = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('cURL-fel: ' . $url . ' (' . $curlErr . ')');
            }
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception('GitHub API svarade med HTTP ' . $statusCode . ' för: ' . $url);
            }
        } else {
            $context  = stream_context_create(['http' => ['method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 20, 'ignore_errors' => true]]);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new Exception('HTTP-fel: ' . $url);
            }

            $statusLine = $http_response_header[0] ?? '';
            if (!preg_match('/\s(\d{3})\s/', $statusLine, $m)) {
                throw new Exception('Kunde inte tolka HTTP-status: ' . $statusLine);
            }
            $statusCode = intval($m[1]);
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

    private function get_repo_info(string $type): array
    {
        [$owner, $repo] = $this->parse_github_repo_url($this->repoUrls[$type]);
        $meta = $this->http_get_json('https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo));
        if (!isset($meta['default_branch']) || $meta['default_branch'] === '') {
            throw new Exception('Kunde inte läsa default branch från GitHub.');
        }
        return [$owner, $repo, $meta['default_branch']];
    }

    private function http_get_raw(string $url): string
    {
        $headers = ['User-Agent: hbook-system-update-tool'];
        if ($this->repoAuth !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->repoAuth;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response   = curl_exec($ch);
            $statusCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            $curlErr    = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('cURL-fel: ' . $curlErr);
            }
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new Exception('HTTP ' . $statusCode . ' vid nedladdning av: ' . $url);
            }
        } else {
            $context  = stream_context_create(['http' => ['method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 30, 'ignore_errors' => true]]);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new Exception('HTTP-fel vid nedladdning av: ' . $url);
            }

            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/\s(\d{3})\s/', $statusLine, $m) && (intval($m[1]) < 200 || intval($m[1]) >= 300)) {
                throw new Exception('HTTP ' . $m[1] . ' vid nedladdning av: ' . $url);
            }
        }

        return (string)$response;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function calculate_git_blob_sha(string $filePath): ?string
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        // Normalize CRLF → LF for text files, matching what Git stores on GitHub
        // and what FTP ASCII mode does on upload. Binary files are left untouched.
        if ($this->is_text_file($filePath) && strpos($content, "\r\n") !== false) {
            $content = str_replace("\r\n", "\n", $content);
        }

        return sha1('blob ' . strlen($content) . "\0" . $content);
    }

    private function is_text_file(string $filePath): bool
    {
        static $textExtensions = [
            'php', 'html', 'htm', 'css', 'js', 'ts', 'json', 'xml', 'svg',
            'txt', 'md', 'tpl', 'sql', 'ini', 'htaccess', 'sh', 'yaml', 'yml',
        ];
        $ext = strtolower(ltrim(strrchr($filePath, '.'), '.'));
        return in_array($ext, $textExtensions, true);
    }

    private function make_zip_path($type)
    {
        $uploadDir = trim($GLOBALS['CONFIG']['uploadDir'] ?? '');
        $uploadDir = trim(str_replace('\\', '/', $uploadDir), '/');
        $backupDir = ROOTPATH . $uploadDir . '/.backup/';
        $this->ensure_backup_directory($backupDir);
        return $backupDir . date('Y-m-d_His') . '_' . $type . '.zip';
    }

    private function ensure_backup_directory($backupDir)
    {
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0770, true) && !is_dir($backupDir)) {
                throw new Exception('Kunde inte skapa backupmapp: ' . $backupDir);
            }
        }

        file_put_contents($backupDir . '.htaccess',
            "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n"
        );

        if (!is_file($backupDir . 'index.php')) {
            file_put_contents($backupDir . 'index.php', "<?php\n// Silence is golden.\n");
        }
    }
}
