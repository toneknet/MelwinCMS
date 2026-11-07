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
        if (isset($GLOBALS['CONFIG']['displayDebug']) && $GLOBALS['CONFIG']['displayDebug'] && isSuperUser()) $this->showDebug = true;
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
        $this->render_core_section();
        $this->render_addons_section();
        $this->render_js_lang();
        $this->render_js();
    }

    private function render_core_section(): void
    {
        $url  = $this->repoUrls['core'];
        $type = 'core';

        print "<div style='margin-bottom:24px;border:1px solid #ccc;padding:16px;border-radius:4px;'>";
        print "<h3 style='margin-top:0;'>Core</h3>";

        if ($url === '') {
            print '<p><em><code>repoUrl</code> ' . $this->l('config_missing') . '</em></p>';
            print "</div>";
            return;
        }

        print '<p>' . $this->l('repo') . ' <code>' . htmlspecialchars($url) . '</code></p>';

        createBtn(['title' => $this->l('btn_check'),   'class' => "sut-check-{$type}"]);
        if ($this->showDebug) createBtn(['title' => $this->l('btn_diff'),   'class' => "sut-diff-{$type}"]);
        if ($this->showDebug) createBtn(['title' => $this->l('btn_backup'), 'class' => "sut-backup-{$type}"]);
        createBtn(['title' => $this->l('btn_install'), 'class' => "sut-install-{$type} sut-install-disabled"]);

        print "<div id='sut_msg_{$type}' style='margin-top:10px;color:#333;'></div>";
        print "<div id='sut_bar_wrap_{$type}' style='height:16px;background:#eee;border-radius:4px;overflow:hidden;max-width:500px;margin-top:6px;display:none;'>";
        print "<div id='sut_bar_{$type}' style='height:16px;background:#4CAF50;width:0%;transition:width .2s;'></div>";
        print "</div>";
        print "<div id='sut_done_{$type}' style='margin-top:8px;'></div>";
        print "<div id='sut_diff_{$type}' style='margin-top:12px;display:none;'></div>";

        print "</div>";
    }

    private function render_addons_section(): void
    {
        $url = $this->repoUrls['addons'];

        print "<div style='margin-bottom:24px;border:1px solid #ccc;padding:16px;border-radius:4px;'>";
        print "<h3 style='margin-top:0;'>Addons</h3>";

        if ($url === '') {
            print '<p><em><code>repoUrlAddons</code> ' . $this->l('config_missing') . '</em></p>';
            print "</div>";
            return;
        }

        print '<p>' . $this->l('repo') . ' <code>' . htmlspecialchars($url) . '</code></p>';

        print "<div style='display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px;'>";
        createBtn(['title' => $this->l('btn_check_addons'),      'class' => 'sut-addon-list-btn']);
        createBtn(['title' => $this->l('btn_update_all_addons'), 'class' => 'sut-addon-update-all sut-install-disabled']);
        print "</div>";

        print "<div id='sut_msg_addons' style='margin-top:4px;color:#333;'></div>";
        print "<div id='sut_bar_wrap_addons' style='height:16px;background:#eee;border-radius:4px;overflow:hidden;max-width:500px;margin-top:6px;display:none;'>";
        print "<div id='sut_bar_addons' style='height:16px;background:#4CAF50;width:0%;transition:width .2s;'></div>";
        print "</div>";
        print "<div id='sut_done_addons' style='margin-top:8px;'></div>";
        print "<div id='sut_addon_list' style='margin-top:12px;'></div>";

        print "</div>";
    }

    private function render_js_lang(): void
    {
        if (empty($_SESSION['sut_uninstall_token'])) {
            $_SESSION['sut_uninstall_token'] = bin2hex(random_bytes(32));
        }
        print '<script>var SUT_TOKEN = ' . json_encode($_SESSION['sut_uninstall_token']) . ';</script>';
        $all  = $GLOBALS['LANG']['system_update_tool'] ?? [];
        $jsLang = [];
        foreach ($all as $key => $val) {
            if (strpos($key, 'js_') === 0) {
                $jsLang[substr($key, 3)] = $val;
            }
        }
        print '<script>var SUT_L = ' . json_encode($jsLang, JSON_UNESCAPED_UNICODE) . ';</script>';
        print '<style>.sut-install-disabled{opacity:.35;pointer-events:none;cursor:default;}</style>';
    }

    private function render_js()
    {
        print "<script>var showDebug = " . ($this->showDebug ? 'true' : 'false') . ";</script>";
        print <<<'JS'
<script>
(function(){
    var ep           = URLroot + 'system/ajax/ajaxRequest.php?action=system_update_tool';
    var addonModules = {}; // slug -> module data, populated after list load

    // ---- Shared helpers ----

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escAttr(s) {
        return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    }
    function errMsg(res) { return SUT_L.error + ' ' + (res && res.error ? res.error : SUT_L.unknown); }

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
    function setInstallEnabled(type, enabled) {
        var btn = document.querySelector('.sut-install-' + type);
        if (!btn) return;
        if (enabled) btn.classList.remove('sut-install-disabled');
        else         btn.classList.add('sut-install-disabled');
    }

    // ---- Shared diff renderer ----

    function renderDiffInElement(diffEl, res) {
        if (!diffEl) return;
        var mod   = res.different   || [];
        var add   = res.new_in_repo || [];
        var del   = res.local_only  || [];
        var total = mod.length + add.length + del.length;

        var html = '<div style="font-family:monospace;font-size:13px;">';
        html += '<div style="margin-bottom:10px;padding:8px 12px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;">';
        html += '<strong>' + SUT_L.diff_summary + '</strong> &nbsp;&nbsp;';
        html += '<span style="color:#e67e00;">&#9679; ' + mod.length + ' ' + SUT_L.diff_modified + '</span> &nbsp;&nbsp;';
        html += '<span style="color:#2a7a2a;">&#9679; ' + add.length + ' ' + SUT_L.diff_new + '</span> &nbsp;&nbsp;';
        if (showDebug) html += '<span style="color:#888;">&#9679; ' + del.length + ' ' + SUT_L.diff_local + '</span>';
        if (total === 0) html += ' &nbsp;&nbsp;<strong style="color:#555;">&#10003; ' + SUT_L.diff_clean + '</strong>';
        html += '</div>';

        if (total === 0) {
            html += '<div style="color:#555;padding:8px;">' + SUT_L.diff_no_diff + '</div></div>';
            diffEl.innerHTML = html;
            diffEl.style.display = '';
            return;
        }

        html += '<div style="border:1px solid #ddd;border-radius:4px;overflow:hidden;">';
        html += '<div style="max-height:480px;overflow-y:auto;padding:8px 0;">';

        function row(prefix, color, bg, path) {
            return '<div style="padding:2px 12px;background:' + bg + ';white-space:nowrap;">'
                 + '<span style="color:' + color + ';font-weight:bold;display:inline-block;width:22px;">' + prefix + '</span>'
                 + '<span style="color:#333;">' + escHtml(path) + '</span></div>';
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

    function renderDiff(type, res) {
        var diffEl = document.getElementById('sut_diff_' + type);
        renderDiffInElement(diffEl, res);
    }

    // ---- Core functions ----

    function doCheck(type) {
        msg(type, SUT_L.checking, 0, true);
        done(type, '');
        setInstallEnabled(type, false);
        var diffEl = document.getElementById('sut_diff_' + type);
        if (diffEl) { diffEl.style.display = 'none'; diffEl.innerHTML = ''; }
        $.getJSON(ep + '&subaction=check_update&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            msg(type, res.message, 100, true);
            done(type, '<strong>' + SUT_L.differences + '</strong> ' + res.mismatch_count);
            renderDiff(type, res);
            setInstallEnabled(type, res.needs_update === true);
        }).fail(function(){ msg(type, SUT_L.ajax_err_check, 100, true); });
    }

    function doBackup(type) {
        msg(type, SUT_L.backup_init, 0, true);
        done(type, '');
        $.getJSON(ep + '&subaction=backup_init&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) { done(type, '<strong>' + SUT_L.no_backup_files + '</strong>'); } else { processBackup(type); }
        }).fail(function(){ msg(type, SUT_L.ajax_err_bk_init, 100, true); });
    }

    function processBackup(type) {
        $.getJSON(ep + '&subaction=backup_process&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) { done(type, '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.zip_path + '</code>'); } else { processBackup(type); }
        }).fail(function(){ msg(type, SUT_L.ajax_err_backup, 100, true); });
    }

    function doInstall(type) {
        if (!confirm(SUT_L.install_confirm.replace('{type}', type))) return;
        msg(type, SUT_L.install_preparing, 0, true);
        done(type, '');
        $.getJSON(ep + '&subaction=install_init&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            if (res.done) { msg(type, res.message, 100, true); return; }
            msg(type, res.message, 5, true);
            if (res.backup_path) { done(type, '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code>'); }
            processInstall(type);
        }).fail(function(){ msg(type, SUT_L.ajax_err_in_init, 100, true); });
    }

    function processInstall(type) {
        $.getJSON(ep + '&subaction=install_process&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            msg(type, res.message, res.progress || 0, true);
            if (res.done) {
                var doneHtml = (res.backup_path ? '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code><br>' : '')
                             + '<strong style="color:#2a7a2a;">' + SUT_L.install_done + ' ' + res.installed + ' ' + SUT_L.files_updated + '</strong>';
                if (res.errors && res.errors.length) doneHtml += '<br><span style="color:#c0392b;">' + SUT_L.error + ' ' + res.errors.join(', ') + '</span>';
                done(type, doneHtml);
            } else { processInstall(type); }
        }).fail(function(){ msg(type, SUT_L.ajax_err_install, 100, true); });
    }

    function doDiff(type) {
        msg(type, SUT_L.diff_loading, 0, true);
        done(type, '');
        var diffEl = document.getElementById('sut_diff_' + type);
        if (diffEl) { diffEl.style.display = 'none'; diffEl.innerHTML = ''; }
        $.getJSON(ep + '&subaction=debug_diff&type=' + type, function(res){
            if (!res || !res.success) { msg(type, errMsg(res), 100, true); return; }
            msg(type, SUT_L.diff_done, 100, true);
            renderDiff(type, res);
        }).fail(function(){ msg(type, SUT_L.ajax_err_diff, 100, true); });
    }

    // ---- Addon module helpers ----

    function modMsg(slug, text, pct, showBar) {
        var extra = document.getElementById('sut_mod_' + slug + '_extra');
        if (extra) extra.style.display = '';
        var m = document.getElementById('sut_mod_msg_' + slug);
        var b = document.getElementById('sut_mod_bar_' + slug);
        var w = document.getElementById('sut_mod_bar_wrap_' + slug);
        if (m) m.innerHTML = text;
        if (b) b.style.width = (pct || 0) + '%';
        if (w) w.style.display = showBar ? '' : 'none';
    }
    function modDone(slug, html) {
        var el = document.getElementById('sut_mod_done_' + slug);
        if (el) el.innerHTML = html || '';
    }
    function setAddonUpdateAllEnabled(enabled) {
        var btn = document.querySelector('.sut-addon-update-all');
        if (!btn) return;
        if (enabled) btn.classList.remove('sut-install-disabled');
        else         btn.classList.add('sut-install-disabled');
    }

    // ---- Addon module list ----

    function doListAddons() {
        msg('addons', SUT_L.addon_loading, 0, true);
        done('addons', '');
        setAddonUpdateAllEnabled(false);
        document.getElementById('sut_addon_list').innerHTML = '';
        $.getJSON(ep + '&subaction=list_addon_modules', function(res){
            if (!res || !res.success) { msg('addons', errMsg(res), 100, true); return; }
            addonModules = {};
            (res.modules || []).forEach(function(m) { addonModules[m.slug] = m; });
            var total = (res.modules || []).filter(function(m){ return m.installed; }).length;
            msg('addons', total + ' ' + SUT_L.addon_modules_found, 100, true);
            renderAddonList(res);
            setAddonUpdateAllEnabled((res.total_updates || 0) > 0);
        }).fail(function(){ msg('addons', SUT_L.ajax_err_check, 100, true); });
    }

    function renderModuleRows(modules) {
        var html = '';
        modules.forEach(function(mod) {
            var slug = mod.slug;
            var statusBadge = '', actionBtns = '';

            if (!mod.installed) {
                statusBadge = '<span style="background:#2a7a2a;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">' + SUT_L.addon_status_new + '</span>';
                actionBtns  = '<button class="btn sut-mod-btn" data-slug="' + escHtml(slug) + '" data-action="install">' + SUT_L.addon_btn_install + '</button> ';
            } else if (mod.needs_update) {
                statusBadge = '<span style="background:#e67e00;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">' + SUT_L.addon_status_update + ' (' + mod.mismatch_count + ')</span>';
                actionBtns  = '<button class="btn sut-mod-btn" data-slug="' + escHtml(slug) + '" data-action="install">' + SUT_L.addon_btn_update + '</button> ';
            } else {
                statusBadge = '<span style="background:#888;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">' + SUT_L.addon_status_ok + '</span>';
            }
            actionBtns += '<button class="btn sut-mod-btn" data-slug="' + escHtml(slug) + '" data-action="diff">' + SUT_L.addon_btn_diff + '</button>';
            if (mod.installed) actionBtns += ' <button class="btn sut-mod-btn" data-slug="' + escHtml(slug) + '" data-action="uninstall">' + SUT_L.addon_btn_uninstall + '</button>';

            html += '<tr>';
            html += '<td style="padding:8px 12px;border-bottom:1px solid #eee;"><strong>' + escHtml(mod.name) + '</strong>';
            if (mod.description) html += '<br><small style="color:#888;">' + escHtml(mod.description) + '</small>';
            if (mod.requires) {
                var reqSlugs = mod.requires.split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                var reqHtml  = reqSlugs.map(function(rs) {
                    var dep   = addonModules[rs];
                    var color = (!dep || !dep.installed) ? '#c0392b' : '#27ae60';
                    return '<span style="color:' + color + ';">' + escHtml(rs) + '</span>';
                }).join(', ');
                html += '<br><small>' + SUT_L.addon_requires + ' ' + reqHtml + '</small>';
            }
            html += '</td>';
            html += '<td style="padding:8px;border-bottom:1px solid #eee;white-space:nowrap;">' + escHtml(mod.version || '–') + '</td>';
            html += '<td style="padding:8px;border-bottom:1px solid #eee;">' + statusBadge + '</td>';
            html += '<td style="padding:8px;border-bottom:1px solid #eee;white-space:nowrap;">' + actionBtns + '</td>';
            html += '</tr>';

            html += '<tr id="sut_mod_' + escHtml(slug) + '_extra" style="display:none;">';
            html += '<td colspan="4" style="padding:4px 12px 10px;border-bottom:1px solid #ddd;background:#fafafa;">';
            html += '<div id="sut_mod_msg_' + escHtml(slug) + '" style="font-size:13px;"></div>';
            html += '<div id="sut_mod_bar_wrap_' + escHtml(slug) + '" style="height:10px;background:#eee;border-radius:4px;overflow:hidden;max-width:400px;margin-top:4px;display:none;">';
            html += '<div id="sut_mod_bar_' + escHtml(slug) + '" style="height:10px;background:#4CAF50;width:0%;transition:width .2s;"></div></div>';
            html += '<div id="sut_mod_done_' + escHtml(slug) + '" style="font-size:13px;margin-top:4px;"></div>';
            html += '<div id="sut_mod_diff_' + escHtml(slug) + '" style="margin-top:8px;display:none;"></div>';
            html += '</td></tr>';
        });
        return html;
    }

    function renderAddonList(res) {
        var modules   = res.modules || [];
        var listEl    = document.getElementById('sut_addon_list');
        if (!modules.length) { listEl.innerHTML = '<p><em>' + SUT_L.addon_no_modules + '</em></p>'; return; }

        var installed = modules.filter(function(m){ return  m.installed; });
        var newMods   = modules.filter(function(m){ return !m.installed; });

        var thStyle = 'text-align:left;padding:6px 12px;border-bottom:2px solid #ddd;background:#f5f5f5;font-size:12px;color:#555;';
        var tableOpen = '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;border-radius:4px;overflow:hidden;margin-bottom:12px;">'
                      + '<thead><tr>'
                      + '<th style="' + thStyle + '">' + SUT_L.addon_col_name    + '</th>'
                      + '<th style="' + thStyle + '">' + SUT_L.addon_col_version + '</th>'
                      + '<th style="' + thStyle + '">' + SUT_L.addon_col_status  + '</th>'
                      + '<th style="' + thStyle + '">' + SUT_L.addon_col_actions + '</th>'
                      + '</tr></thead><tbody>';
        var tableClose = '</tbody></table>';

        var html = '';
        if (installed.length) {
            html += '<div style="font-weight:bold;color:#444;margin-bottom:4px;">' + SUT_L.addon_separator_installed + ' (' + installed.length + ')</div>';
            html += tableOpen + renderModuleRows(installed) + tableClose;
        } else {
            html += '<p>' + SUT_L.addon_no_installed + '</p>';
        }
        html += '<button type="button" class="btn sut-store-btn" aria-expanded="false" aria-controls="sut_store">' + SUT_L.addon_store + ' (' + newMods.length + ')</button>';
        html += '<div id="sut_store" hidden style="margin-top:12px;">';
        if (newMods.length) {
            html += '<div style="font-weight:bold;color:#444;margin-bottom:4px;margin-top:4px;">' + SUT_L.addon_separator_new + ' (' + newMods.length + ')</div>';
            html += tableOpen + renderModuleRows(newMods) + tableClose;
        } else { html += '<p>' + SUT_L.addon_no_available + '</p>'; }
        html += '</div>';
        listEl.innerHTML = html;
    }

    function doAddonUninstall(slug, btn) {
        if (!confirm(SUT_L.addon_uninstall_confirm.replace('{slug}', slug))) return;
        btn.disabled = true;
        modMsg(slug, SUT_L.addon_uninstalling, 0, true);
        $.ajax({url: ep + '&subaction=addon_uninstall', method: 'POST', dataType: 'json',
            data: {slug: slug, token: SUT_TOKEN}
        }).done(function(res){
            if (!res || !res.success) {
                modMsg(slug, escHtml(errMsg(res)), 100, false);
                if (res && res.backup_path) modDone(slug, '<strong>' + SUT_L.backup_label + '</strong> <code>' + escHtml(res.backup_path) + '</code>');
                return;
            }
            addonModules[slug].installed = false;
            addonModules[slug].needs_update = false;
            renderAddonList({modules: Object.keys(addonModules).map(function(key){ return addonModules[key]; })});
            setAddonUpdateAllEnabled(Object.keys(addonModules).some(function(key){ return addonModules[key].needs_update; }));
            msg('addons', escHtml(res.message), 100, false);
            done('addons', '<strong>' + SUT_L.backup_label + '</strong> <code>' + escHtml(res.backup_path) + '</code>');
        }).fail(function(){ modMsg(slug, SUT_L.ajax_err_uninstall, 100, false); })
          .always(function(){ btn.disabled = false; });
    }

    // ---- Addon diff ----

    function doAddonDiff(slug) {
        modMsg(slug, SUT_L.diff_loading, 0, true);
        var diffEl = document.getElementById('sut_mod_diff_' + slug);
        if (diffEl) { diffEl.style.display = 'none'; diffEl.innerHTML = ''; }
        $.getJSON(ep + '&subaction=addon_diff&slug=' + encodeURIComponent(slug), function(res){
            if (!res || !res.success) { modMsg(slug, errMsg(res), 100, true); return; }
            modMsg(slug, SUT_L.diff_done, 100, true);
            renderDiffInElement(diffEl, res);
            if (diffEl) diffEl.style.display = '';
        }).fail(function(){ modMsg(slug, SUT_L.ajax_err_diff, 100, true); });
    }

    // ---- Addon install/update (single module) ----

    function doAddonInstall(slug) {
        var mod      = addonModules[slug] || {};
        var requires = mod.requires ? mod.requires.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : [];
        var deps     = requires.filter(function(depSlug) {
            var dep = addonModules[depSlug];
            return !dep || !dep.installed || dep.needs_update;
        });

        var confirmMsg = (SUT_L.addon_install_confirm || '').replace('{slug}', slug);
        if (deps.length) {
            confirmMsg += '\n\n' + SUT_L.addon_install_deps_also + '\n' + deps.join(', ');
        }
        if (!confirm(confirmMsg)) return;

        modMsg(slug, SUT_L.install_preparing, 0, true);
        modDone(slug, '');
        var url = ep + '&subaction=addon_install_init&slug=' + encodeURIComponent(slug);
        if (deps.length) url += '&deps=' + encodeURIComponent(deps.join(','));
        $.getJSON(url, function(res){
            if (!res || !res.success) { modMsg(slug, errMsg(res), 100, true); return; }
            if (res.done) { modMsg(slug, res.message, 100, true); return; }
            modMsg(slug, res.message, 5, true);
            if (res.backup_path) modDone(slug, '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code>');
            processAddonInstall(slug);
        }).fail(function(){ modMsg(slug, SUT_L.ajax_err_in_init, 100, true); });
    }

    function processAddonInstall(slug) {
        $.getJSON(ep + '&subaction=addon_install_process&slug=' + encodeURIComponent(slug), function(res){
            if (!res || !res.success) { modMsg(slug, errMsg(res), 100, true); return; }
            modMsg(slug, res.message, res.progress || 0, true);
            if (res.done) {
                var doneHtml = (res.backup_path ? '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code><br>' : '')
                             + '<strong style="color:#2a7a2a;">' + SUT_L.install_done + ' ' + res.installed + ' ' + SUT_L.files_updated + '</strong>';
                if (res.errors && res.errors.length) doneHtml += '<br><span style="color:#c0392b;">' + SUT_L.error + ' ' + res.errors.join(', ') + '</span>';
                modDone(slug, doneHtml);
            } else { processAddonInstall(slug); }
        }).fail(function(){ modMsg(slug, SUT_L.ajax_err_install, 100, true); });
    }

    // ---- Addon update all ----

    function doAddonInstallAll() {
        if (!confirm(SUT_L.addon_install_all_confirm)) return;
        msg('addons', SUT_L.install_preparing, 0, true);
        done('addons', '');
        $.getJSON(ep + '&subaction=addon_install_all_init', function(res){
            if (!res || !res.success) { msg('addons', errMsg(res), 100, true); return; }
            if (res.done) { msg('addons', res.message, 100, true); return; }
            msg('addons', res.message, 5, true);
            if (res.backup_path) done('addons', '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code>');
            processAddonInstallAll();
        }).fail(function(){ msg('addons', SUT_L.ajax_err_in_init, 100, true); });
    }

    function processAddonInstallAll() {
        $.getJSON(ep + '&subaction=addon_install_all_process', function(res){
            if (!res || !res.success) { msg('addons', errMsg(res), 100, true); return; }
            msg('addons', res.message, res.progress || 0, true);
            if (res.done) {
                var doneHtml = (res.backup_path ? '<strong>' + SUT_L.backup_label + '</strong> <code>' + res.backup_path + '</code><br>' : '')
                             + '<strong style="color:#2a7a2a;">' + SUT_L.install_done + ' ' + res.installed + ' ' + SUT_L.files_updated + '</strong>';
                if (res.errors && res.errors.length) doneHtml += '<br><span style="color:#c0392b;">' + SUT_L.error + ' ' + res.errors.join(', ') + '</span>';
                done('addons', doneHtml);
            } else { processAddonInstallAll(); }
        }).fail(function(){ msg('addons', SUT_L.ajax_err_install, 100, true); });
    }

    // ---- Event listeners ----

    ['core'].forEach(function(type) {
        var c = document.querySelector('.sut-check-'   + type);
        var d = document.querySelector('.sut-diff-'    + type);
        var b = document.querySelector('.sut-backup-'  + type);
        var i = document.querySelector('.sut-install-' + type);
        if (c) c.addEventListener('click', function(e){ e.preventDefault(); doCheck(type); });
        if (d) d.addEventListener('click', function(e){ e.preventDefault(); doDiff(type); });
        if (b) b.addEventListener('click', function(e){ e.preventDefault(); doBackup(type); });
        if (i) i.addEventListener('click', function(e){ e.preventDefault(); doInstall(type); });
    });

    var addonListBtn      = document.querySelector('.sut-addon-list-btn');
    var addonUpdateAllBtn = document.querySelector('.sut-addon-update-all');
    if (addonListBtn)      addonListBtn.addEventListener('click',      function(e){ e.preventDefault(); doListAddons(); });
    if (addonUpdateAllBtn) addonUpdateAllBtn.addEventListener('click', function(e){ e.preventDefault(); doAddonInstallAll(); });

    // Delegated listener for dynamically-rendered module buttons (install/update/diff)
    document.addEventListener('click', function(e) {
        var storeBtn = e.target.closest('.sut-store-btn');
        if (storeBtn) {
            e.preventDefault();
            var store = document.getElementById('sut_store');
            store.hidden = !store.hidden;
            storeBtn.setAttribute('aria-expanded', String(!store.hidden));
            return;
        }
        var btn = e.target.closest('.sut-mod-btn');
        if (!btn) return;
        e.preventDefault();
        var slug   = btn.getAttribute('data-slug');
        var action = btn.getAttribute('data-action');
        if (!slug) return;
        if (action === 'install') { doAddonInstall(slug); }
        else if (action === 'diff')    { doAddonDiff(slug); }
        else if (action === 'uninstall') { doAddonUninstall(slug, btn); }
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
        // Match the files that install_init will actually update (not directories).
        $relevant_statuses = ['hash_mismatch', 'size_mismatch', 'missing_local_file', 'local_hash_error'];
        $relevant = array_filter($mismatch, function($row) use ($relevant_statuses) {
            return in_array($row['status'] ?? '', $relevant_statuses, true);
        });
        $count = count($relevant);
        $different = [];
        $new_in_repo = [];
        foreach ($relevant as $path => $meta) {
            if ($meta['status'] === 'missing_local_file') {
                $new_in_repo[] = $path;
            } else {
                $different[] = $path;
            }
        }
        sort($different);
        sort($new_in_repo);
        return [
            'success'        => true,
            'mismatch_count' => $count,
            'different'     => $different,
            'new_in_repo'   => $new_in_repo,
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

    // -------------------------------------------------------------------------
    // Addon module management
    // -------------------------------------------------------------------------

    public function ajax_list_addon_modules(): array
    {
        $url = $this->repoUrls['addons'];
        if ($url === '') {
            return ['success' => false, 'error' => 'repoUrlAddons ' . $this->l('config_missing')];
        }

        $treeNodes = $this->fetch_repo_tree_nodes($url);
        [$owner, $repo, $branch] = $this->get_repo_info('addons');

        // Repo root dirs are module slugs: import_hockey/import_hockey.php
        // Local install path:             system/modules/import_hockey/import_hockey.php
        $repoIndex   = []; // repoPath (slug/file) => sha
        $moduleSlugs = [];

        foreach ($treeNodes as $node) {
            $repoPath = str_replace('\\', '/', $node['path'] ?? '');
            if ($repoPath === '') {
                continue;
            }
            $parts = explode('/', $repoPath);
            if (count($parts) < 2) {
                continue; // root-level file (e.g. README.md), not a module
            }
            $slug = $parts[0];
            if ($slug === '' || $slug[0] === '.') {
                continue;
            }
            $moduleSlugs[$slug] = true;
            if (($node['type'] ?? '') === 'blob') {
                $repoIndex[$repoPath] = $node['sha'] ?? '';
            }
        }

        $installed    = [];
        $notInstalled = [];

        foreach (array_keys($moduleSlugs) as $slug) {
            $localDir    = ROOTPATH . 'system/modules/' . $slug;
            $isInstalled = is_dir($localDir);
            $slugPrefix  = $slug . '/';

            $mismatchCount = 0;
            foreach ($repoIndex as $repoPath => $repoSha) {
                if (strpos($repoPath, $slugPrefix) !== 0) {
                    continue;
                }
                $localPath = ROOTPATH . 'system/modules/' . $repoPath;
                if (!is_file($localPath)) {
                    $mismatchCount++;
                    continue;
                }
                $localSha = $this->calculate_git_blob_sha($localPath);
                if ($repoSha !== '' && $localSha !== $repoSha) {
                    $mismatchCount++;
                }
            }

            // Parse header from local file if installed, else fetch from repo
            $mainRepoPath  = $slug . '/' . $slug . '.php';
            $mainLocalPath = ROOTPATH . 'system/modules/' . $mainRepoPath;
            $localHeader   = [];
            $repoHeader    = [];

            if ($isInstalled && is_file($mainLocalPath)) {
                $content = @file_get_contents($mainLocalPath);
                if ($content !== false) {
                    $localHeader = $this->parse_module_header($content);
                }
            } elseif (!$isInstalled && isset($repoIndex[$mainRepoPath])) {
                try {
                    $rawUrl = 'https://raw.githubusercontent.com/'
                        . rawurlencode($owner) . '/'
                        . rawurlencode($repo) . '/'
                        . rawurlencode($branch) . '/'
                        . $mainRepoPath;
                    $repoContent = $this->http_get_raw($rawUrl);
                    $repoHeader  = $this->parse_module_header($repoContent);
                } catch (Exception) {
                    // silent fail — show slug as name
                }
            }

            $header = array_merge($repoHeader, $localHeader);

            $entry = [
                'slug'           => $slug,
                'installed'      => $isInstalled,
                'needs_update'   => $isInstalled && $mismatchCount > 0,
                'mismatch_count' => $mismatchCount,
                'name'           => $header['name']        ?? $slug,
                'version'        => $header['version']     ?? '',
                'description'    => $header['description'] ?? '',
                'author'         => $header['author']      ?? '',
                'requires'       => $header['requires']    ?? '',
            ];

            if ($isInstalled) {
                $installed[] = $entry;
            } else {
                $notInstalled[] = $entry;
            }
        }

        usort($installed,    fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($notInstalled, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        $totalUpdates = count(array_filter($installed, fn($m) => $m['needs_update']));
        $_SESSION['sut_addon_slugs'] = array_keys($moduleSlugs);

        return [
            'success'       => true,
            'modules'       => array_merge($installed, $notInstalled),
            'total_updates' => $totalUpdates,
            'total_new'     => count($notInstalled),
        ];
    }

    public function ajax_addon_uninstall(string $slug): array
    {
        $this->validate_slug($slug);
        if (!in_array($slug, $_SESSION['sut_addon_slugs'] ?? [], true) || $slug === 'system_update_tool') {
            throw new Exception('Modulen finns inte i den kontrollerade addon-listan.');
        }
        if (!class_exists('ZipArchive')) {
            throw new Exception($this->l('err_no_ziparchive'));
        }
        $base = realpath(ROOTPATH . 'system/modules');
        $dir = realpath(ROOTPATH . 'system/modules/' . $slug);
        if ($base === false || $dir === false || dirname($dir) !== $base || basename($dir) !== $slug
            || is_link(ROOTPATH . 'system/modules/' . $slug) || !is_dir($dir)) {
            throw new Exception('Ogiltig eller saknad modulmapp.');
        }
        // Read dependencies from installed modules, including those outside the addon repo.
        foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $otherDir) {
            $other = basename($otherDir);
            if ($other === $slug) continue;
            $main = $otherDir . '/' . $other . '.php';
            if (!is_file($main)) continue;
            $content = file_get_contents($main);
            if ($content === false) throw new Exception('Kunde inte kontrollera modulberoenden.');
            $header = $this->parse_module_header($content);
            $requires = array_map('trim', explode(',', $header['requires'] ?? ''));
            if (in_array($slug, $requires, true)) {
                throw new Exception('Modulen krävs av den installerade modulen: ' . $other);
            }
        }
        $entries = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $path = $entry->getPathname();
            $resolved = realpath($path);
            if ($entry->isLink() || $resolved === false || strpos($resolved, $dir . DIRECTORY_SEPARATOR) !== 0) {
                throw new Exception('Modulmappen innehåller en länk eller ogiltig sökväg. Avinstallation avbruten.');
            }
            $entries[] = ['path' => $path, 'directory' => $entry->isDir(),
                'name' => 'system/modules/' . $slug . '/' . str_replace('\\', '/', substr($path, strlen($dir) + 1))];
        }
        $zipPath = $this->make_zip_path('addon_' . $slug . '_uninstall_' . bin2hex(random_bytes(6)));
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new Exception($this->l('err_zip_write'));
        }
        $ok = $zip->addEmptyDir('system/modules/' . $slug);
        foreach ($entries as $entry) {
            $added = $entry['directory'] ? $zip->addEmptyDir($entry['name']) : $zip->addFile($entry['path'], $entry['name']);
            $ok = $added && $ok;
        }
        $closed = $zip->close();
        if (!$ok || !$closed) throw new Exception('Backup misslyckades. Inga modulfiler har raderats.');
        // Verify the saved archive against every original file before removing anything.
        $verify = new ZipArchive();
        if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
            throw new Exception('Backup kunde inte verifieras. Inga modulfiler har raderats.');
        }
        try {
            foreach ($entries as $entry) {
                if ($entry['directory']) continue;
                $saved = $verify->getFromName($entry['name']);
                $hash = hash_file('sha256', $entry['path']);
                if ($saved === false || $hash === false || hash('sha256', $saved) !== $hash) {
                    throw new Exception('Backup kunde inte verifieras. Inga modulfiler har raderats.');
                }
            }
        } finally {
            $verify->close();
        }
        foreach ($entries as $entry) {
            $removed = $entry['directory'] ? @rmdir($entry['path']) : @unlink($entry['path']);
            if (!$removed) {
                return ['success' => false, 'error' => 'Avinstallationen avbröts vid: ' . $entry['name'] . '. Fullständig backup finns sparad.', 'backup_path' => $zipPath];
            }
        }
        if (!@rmdir($dir)) {
            return ['success' => false, 'error' => 'Kunde inte ta bort modulmappen. Fullständig backup finns sparad.', 'backup_path' => $zipPath];
        }
        unset($_SESSION['sut_install_addon_' . $slug]);
        return ['success' => true, 'message' => sprintf($this->l('msg_addon_uninstalled'), $slug), 'backup_path' => $zipPath];
    }

    public function ajax_addon_diff(string $slug): array
    {
        $this->validate_slug($slug);

        $treeNodes  = $this->fetch_repo_tree_nodes($this->repoUrls['addons']);
        $slugPrefix = $slug . '/';

        // repoPath (slug/file) => sha
        $repoIndex = [];
        foreach ($treeNodes as $node) {
            if (($node['type'] ?? '') !== 'blob') {
                continue;
            }
            $repoPath = str_replace('\\', '/', $node['path'] ?? '');
            if (strpos($repoPath, $slugPrefix) !== 0) {
                continue;
            }
            $repoIndex[$repoPath] = trim($node['sha'] ?? '');
        }

        $different   = [];
        $new_in_repo = [];

        foreach ($repoIndex as $repoPath => $repoSha) {
            $localPath = ROOTPATH . 'system/modules/' . $repoPath;
            if (!is_file($localPath)) {
                $new_in_repo[] = $repoPath;
                continue;
            }
            $localSha = $this->calculate_git_blob_sha($localPath);
            if ($repoSha !== '' && $localSha !== $repoSha) {
                $different[] = $repoPath;
            }
        }

        $local_only = [];
        $localDir   = ROOTPATH . 'system/modules/' . $slug . '/';
        if (is_dir($localDir)) {
            $localFiles = [];
            $this->scan_dir($localDir, $slugPrefix, $localFiles);
            foreach ($localFiles as $lf) {
                if (!isset($repoIndex[$lf])) {
                    $local_only[] = $lf;
                }
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

    public function ajax_addon_install_init(string $slug, array $depSlugs = []): array
    {
        $this->validate_slug($slug);
        foreach ($depSlugs as $dep) {
            $this->validate_slug($dep);
        }
        if (!class_exists('ZipArchive')) {
            throw new Exception($this->l('err_no_ziparchive'));
        }

        $treeNodes = $this->fetch_repo_tree_nodes($this->repoUrls['addons']);
        [$owner, $repo, $branch] = $this->get_repo_info('addons');

        $slugsToProcess = array_unique(array_merge([$slug], $depSlugs));
        $toInstall      = []; // local relative paths (system/modules/slug/file)

        foreach ($slugsToProcess as $currentSlug) {
            $slugPrefix = $currentSlug . '/';
            foreach ($treeNodes as $node) {
                if (($node['type'] ?? '') !== 'blob') {
                    continue;
                }
                $repoPath     = str_replace('\\', '/', $node['path'] ?? '');
                if (strpos($repoPath, $slugPrefix) !== 0) {
                    continue;
                }
                $localRelPath = 'system/modules/' . $repoPath;
                $localPath    = ROOTPATH . $localRelPath;
                $repoSha      = trim($node['sha'] ?? '');
                $needs        = !is_file($localPath);
                if (!$needs) {
                    $localSha = $this->calculate_git_blob_sha($localPath);
                    $needs    = ($repoSha !== '' && $localSha !== $repoSha);
                }
                if ($needs) {
                    $toInstall[] = $localRelPath;
                }
            }
        }

        if (empty($toInstall)) {
            return ['success' => true, 'done' => true, 'progress' => 100, 'message' => $this->l('msg_no_install')];
        }

        $toBackup = array_values(array_filter($toInstall, fn($p) => is_file(ROOTPATH . $p)));
        $zipPath  = null;
        if (!empty($toBackup)) {
            $zipPath = $this->make_zip_path('addon_' . $slug);
            $zip     = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Kunde inte skapa backup-zip: ' . $zipPath);
            }
            foreach ($toBackup as $p) {
                $zip->addFile(ROOTPATH . $p, $p);
            }
            $zip->close();
        }

        $_SESSION['sut_install_addon_' . $slug] = [
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

        return ['success' => true, 'done' => false, 'progress' => 5, 'message' => $message, 'backup_path' => $zipPath];
    }

    public function ajax_addon_install_process(string $slug): array
    {
        $this->validate_slug($slug);
        $key = 'sut_install_addon_' . $slug;
        if (!isset($_SESSION[$key])) {
            return ['success' => false, 'error' => $this->l('err_no_install_sess')];
        }

        $state     = $_SESSION[$key];
        $files     = $state['files']; // local relative paths
        $offset    = intval($state['offset']);
        $total     = intval($state['total']);
        $chunk     = intval($state['chunk']);
        $installed = intval($state['installed']);
        $errors    = [];

        $end = min($offset + $chunk, $total);
        for ($i = $offset; $i < $end; $i++) {
            try {
                $this->download_and_write_addon_file($state['owner'], $state['repo'], $state['branch'], $files[$i]);
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

    public function ajax_addon_install_all_init(): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception($this->l('err_no_ziparchive'));
        }

        $treeNodes = $this->fetch_repo_tree_nodes($this->repoUrls['addons']);
        [$owner, $repo, $branch] = $this->get_repo_info('addons');
        $toInstall = []; // local relative paths

        foreach ($treeNodes as $node) {
            if (($node['type'] ?? '') !== 'blob') {
                continue;
            }
            $repoPath = str_replace('\\', '/', $node['path'] ?? '');
            if ($repoPath === '') {
                continue;
            }
            $parts = explode('/', $repoPath);
            $slug  = $parts[0];
            if ($slug === '' || $slug[0] === '.' || count($parts) < 2) {
                continue;
            }
            $moduleDir = ROOTPATH . 'system/modules/' . $slug;
            if (!is_dir($moduleDir)) {
                continue; // only update already-installed modules
            }

            $localRelPath = 'system/modules/' . $repoPath;
            $localPath    = ROOTPATH . $localRelPath;
            $repoSha      = trim($node['sha'] ?? '');
            $needs        = !is_file($localPath);
            if (!$needs) {
                $localSha = $this->calculate_git_blob_sha($localPath);
                $needs    = ($repoSha !== '' && $localSha !== $repoSha);
            }
            if ($needs) {
                $toInstall[] = $localRelPath;
            }
        }

        if (empty($toInstall)) {
            return ['success' => true, 'done' => true, 'progress' => 100, 'message' => $this->l('msg_no_install')];
        }

        $toBackup = array_values(array_filter($toInstall, fn($p) => is_file(ROOTPATH . $p)));
        $zipPath  = null;
        if (!empty($toBackup)) {
            $zipPath = $this->make_zip_path('addons_update_all');
            $zip     = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Kunde inte skapa backup-zip: ' . $zipPath);
            }
            foreach ($toBackup as $p) {
                $zip->addFile(ROOTPATH . $p, $p);
            }
            $zip->close();
        }

        $_SESSION['sut_install_addons_all'] = [
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

        return ['success' => true, 'done' => false, 'progress' => 5, 'message' => $message, 'backup_path' => $zipPath];
    }

    public function ajax_addon_install_all_process(): array
    {
        $key = 'sut_install_addons_all';
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
                $this->download_and_write_addon_file($state['owner'], $state['repo'], $state['branch'], $files[$i]);
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

    private function parse_module_header(string $content): array
    {
        $header = [];
        if (!preg_match('/\/\*+\s*(.*?)\s*\*+\//s', $content, $m)) {
            return $header;
        }
        $block = $m[1];
        foreach (['Name', 'Author', 'Author URI', 'Description', 'Version', 'Type', 'Requires'] as $field) {
            if (preg_match('/^\s*' . preg_quote($field, '/') . '\s*:\s*(.+)$/im', $block, $fm)) {
                $key          = strtolower(str_replace(' ', '_', $field));
                $header[$key] = trim($fm[1]);
            }
        }
        return $header;
    }

    private function validate_slug(string $slug): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $slug)) {
            throw new Exception('Ogiltigt modulnamn: ' . htmlspecialchars($slug));
        }
    }

    private function download_and_write_addon_file(string $owner, string $repo, string $branch, string $localRelPath): void
    {
        // localRelPath = system/modules/{slug}/file.php
        // repo path    = {slug}/file.php  (no system/modules/ prefix in addons repo)
        $repoPath = substr($localRelPath, strlen('system/modules/'));
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $repoPath)));
        $rawUrl   = 'https://raw.githubusercontent.com/'
            . rawurlencode($owner) . '/'
            . rawurlencode($repo) . '/'
            . rawurlencode($branch) . '/'
            . $encodedPath;

        $content   = $this->http_get_raw($rawUrl);
        $localPath = ROOTPATH . $localRelPath;
        $dir       = dirname($localPath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception('Kunde inte skapa katalog: ' . $dir);
        }
        if (file_put_contents($localPath, $content) === false) {
            throw new Exception('Kunde inte skriva fil: ' . $localPath);
        }
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

        // core: resources/, tpl/, system/ (all, including system/modules/), root files
        foreach (['resources/' => ROOTPATH . 'resources/', 'tpl/' => ROOTPATH . 'tpl/', 'system/' => ROOTPATH . 'system/'] as $prefix => $absDir) {
            if (is_dir($absDir)) {
                $this->scan_dir($absDir, $prefix, $files);
            }
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

        // core: everything under these roots (including system/modules/ for built-in modules)
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
