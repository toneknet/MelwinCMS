<?php
header('Content-Type: application/json; charset=utf-8');

require_once(MODPATH . 'system_update_tool/system_update_tool.php');

try {
    $tool      = new system_update_tool(['ajax' => 1]);
    $subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';
    $type      = isset($_GET['type']) && $_GET['type'] === 'addons' ? 'addons' : 'core';

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

        default:
            $result = ['success' => false, 'error' => 'Okänd subaction: ' . $subaction];
            break;
    }
} catch (Exception $e) {
    $result = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($result);
exit;
