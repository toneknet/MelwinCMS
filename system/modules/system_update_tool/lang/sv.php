<?php
// --- UI ---
$GLOBALS['LANG']['system_update_tool']['title']          = 'System Update Tool';
$GLOBALS['LANG']['system_update_tool']['repo']           = 'Repo:';
$GLOBALS['LANG']['system_update_tool']['config_missing'] = 'saknas i CONFIG.';
$GLOBALS['LANG']['system_update_tool']['btn_check']      = 'Kontrollera';
$GLOBALS['LANG']['system_update_tool']['btn_diff']       = 'Diff';
$GLOBALS['LANG']['system_update_tool']['btn_backup']     = 'Backup';
$GLOBALS['LANG']['system_update_tool']['btn_install']    = 'Installera';

// --- JS (prefixed js_, stripped when output to browser) ---
$GLOBALS['LANG']['system_update_tool']['js_checking']          = 'Kontrollerar repo...';
$GLOBALS['LANG']['system_update_tool']['js_error']             = 'Fel:';
$GLOBALS['LANG']['system_update_tool']['js_unknown']           = 'okänt';
$GLOBALS['LANG']['system_update_tool']['js_differences']       = 'Skillnader:';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_check']    = 'Ajax-fel vid kontroll';
$GLOBALS['LANG']['system_update_tool']['js_backup_init']       = 'Initierar backup...';
$GLOBALS['LANG']['system_update_tool']['js_no_backup_files']   = 'Inga filer att backa upp.';
$GLOBALS['LANG']['system_update_tool']['js_backup_label']      = 'Backup:';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_bk_init']  = 'Ajax-fel vid backup init';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_backup']   = 'Ajax-fel vid backup';
$GLOBALS['LANG']['system_update_tool']['js_install_confirm']   = "Installera uppdateringar för {type}?\n\nBerörda lokala filer säkerhetskopieras automatiskt innan installation.";
$GLOBALS['LANG']['system_update_tool']['js_install_preparing'] = 'Förbereder installation...';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_in_init']  = 'Ajax-fel vid install init';
$GLOBALS['LANG']['system_update_tool']['js_install_done']      = '&#10003; Installation klar &mdash;';
$GLOBALS['LANG']['system_update_tool']['js_files_updated']     = 'filer uppdaterade.';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_install']  = 'Ajax-fel vid install';
$GLOBALS['LANG']['system_update_tool']['js_diff_loading']      = 'Hämtar diff från repo...';
$GLOBALS['LANG']['system_update_tool']['js_diff_done']         = 'Diff klar.';
$GLOBALS['LANG']['system_update_tool']['js_ajax_err_diff']     = 'Ajax-fel vid diff';
$GLOBALS['LANG']['system_update_tool']['js_diff_summary']      = 'Diff summary';
$GLOBALS['LANG']['system_update_tool']['js_diff_modified']     = 'modified';
$GLOBALS['LANG']['system_update_tool']['js_diff_new']          = 'new (in repo)';
$GLOBALS['LANG']['system_update_tool']['js_diff_local']        = 'local only (ej i repo)';
$GLOBALS['LANG']['system_update_tool']['js_diff_clean']        = 'Clean';
$GLOBALS['LANG']['system_update_tool']['js_diff_no_diff']      = 'Ingen skillnad hittades.';
$GLOBALS['LANG']['system_update_tool']['js_diff_sec_modified'] = 'Modified';
$GLOBALS['LANG']['system_update_tool']['js_diff_sec_new']      = 'New in repo – will be added';
$GLOBALS['LANG']['system_update_tool']['js_diff_sec_local']    = 'Local only – ej i repo, berörs ej';

// --- PHP / AJAX messages (%d = sprintf placeholder) ---
$GLOBALS['LANG']['system_update_tool']['err_no_ziparchive']    = 'ZipArchive saknas på servern.';
$GLOBALS['LANG']['system_update_tool']['err_no_backup_sess']   = 'Ingen backup-session hittades.';
$GLOBALS['LANG']['system_update_tool']['err_no_install_sess']  = 'Ingen install-session hittades.';
$GLOBALS['LANG']['system_update_tool']['err_zip_write']        = 'Kunde inte öppna zip-fil för skrivning.';
$GLOBALS['LANG']['system_update_tool']['msg_backup_init']      = 'Backup initierad. Filer att backa upp: %d';
$GLOBALS['LANG']['system_update_tool']['msg_backup_done']      = 'Backup klar.';
$GLOBALS['LANG']['system_update_tool']['msg_backup_progress']  = 'Backat %d av %d filer.';
$GLOBALS['LANG']['system_update_tool']['msg_update_needed']    = 'Uppdatering rekommenderas. %d skillnad(er) hittades.';
$GLOBALS['LANG']['system_update_tool']['msg_no_update']        = 'Inga skillnader. Ingen uppdatering behövs.';
$GLOBALS['LANG']['system_update_tool']['msg_no_install']       = 'Inga filer att installera.';
$GLOBALS['LANG']['system_update_tool']['msg_install_init']     = '%d filer att installera. Backup av %d befintliga filer skapad.';
$GLOBALS['LANG']['system_update_tool']['msg_install_init_nb']  = '%d filer att installera.';
$GLOBALS['LANG']['system_update_tool']['msg_install_progress'] = 'Installerat %d av %d filer.';
$GLOBALS['LANG']['system_update_tool']['msg_install_done']     = 'Install klar.';
