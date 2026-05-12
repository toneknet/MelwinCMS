<?php
header('Content-Type: application/json; charset=utf-8');

require_once(MODPATH . 'system_update_tool/system_update_tool.php');

try {
    $tool = new system_update_tool(array('ajax' => 1));
    $subaction = isset($_GET['subaction']) ? $_GET['subaction'] : '';

    switch ($subaction) {
        case 'backup_init':
            $result = $tool->ajax_backup_init();
            break;

        case 'backup_process':
            $result = $tool->ajax_backup_process();
            break;

        case 'install_step':
            $result = array(
                'success' => true,
                'message' => 'Install steg initierat. Lägg installationslogik här.',
            );
            break;

        case 'check_update':
            $result = $tool->ajax_check_update_needed();
            break;

        default:
            $result = array(
                'success' => false,
                'error' => 'Okänd subaction.',
            );
            break;
    }
} catch (Exception $e) {
    $result = array(
        'success' => false,
        'error' => $e->getMessage(),
    );
}

echo json_encode($result);
exit;
