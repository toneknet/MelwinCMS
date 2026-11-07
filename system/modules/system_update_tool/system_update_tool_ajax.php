<?php
header('Content-Type: application/json; charset=utf-8');

require_once(MODPATH . 'system_update_tool/system_update_tool.php');

try {
    $tool      = new system_update_tool(['ajax' => 1]);
    $subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';
    $type      = isset($_GET['type']) && $_GET['type'] === 'addons' ? 'addons' : 'core';
    $slug      = isset($_GET['slug']) ? (string)$_GET['slug'] : '';

    switch ($subaction) {
        case 'check_update':
            $result = $tool->ajax_check_update_needed($type);
            break;

        case 'debug_diff':
            $result = $tool->ajax_debug_diff($type);
            break;

        case 'backup_init':
            $result = $tool->ajax_backup_init($type);
            break;

        case 'backup_process':
            $result = $tool->ajax_backup_process($type);
            break;

        case 'install_init':
            $result = $tool->ajax_install_init($type);
            break;

        case 'install_process':
            $result = $tool->ajax_install_process($type);
            break;

        // ---- Addon module management ----

        case 'addon_uninstall':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['sut_uninstall_token'])
                || !hash_equals($_SESSION['sut_uninstall_token'], (string)($_POST['token'] ?? ''))) {
                throw new Exception('Ogiltig avinstallationsbegäran. Ladda om sidan och försök igen.');
            }
            $result = $tool->ajax_addon_uninstall((string)($_POST['slug'] ?? ''));
            break;

        case 'list_addon_modules':
            $result = $tool->ajax_list_addon_modules();
            break;

        case 'addon_diff':
            $result = $tool->ajax_addon_diff($slug);
            break;

        case 'addon_install_init':
            $depSlugs = [];
            if (!empty($_GET['deps'])) {
                foreach (explode(',', $_GET['deps']) as $dep) {
                    $dep = trim($dep);
                    if ($dep !== '') {
                        $depSlugs[] = $dep;
                    }
                }
            }
            $result = $tool->ajax_addon_install_init($slug, $depSlugs);
            break;

        case 'addon_install_process':
            $result = $tool->ajax_addon_install_process($slug);
            break;

        case 'addon_install_all_init':
            $result = $tool->ajax_addon_install_all_init();
            break;

        case 'addon_install_all_process':
            $result = $tool->ajax_addon_install_all_process();
            break;

        default:
            $result = ['success' => false, 'error' => 'Okänd subaction: ' . $subaction];
            break;
    }
} catch (Exception $e) {
    $result = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($result);
exit;
