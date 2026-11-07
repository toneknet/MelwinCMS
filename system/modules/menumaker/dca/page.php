<?php

// Keep an existing site-specific page definition if one has already been loaded.
if (isset($GLOBALS['TABLE']['page'])) {
    return;
}

$GLOBALS['TABLE']['page'] = [
    'table' => 'page',
    'form' => [
        'excl_CID' => true,
        'palette' => 'id,pid,label,src,isstartpage,visible,jumpTo,ordning,companyid,deleted',
    ],
    'fields' => [
        'id' => ['type' => 'hidden'],
        'pid' => [
            'type' => 'select',
            'foreignKey' => 'page.label',
            'foreignKeyisPage' => true,
            'value' => 0,
        ],
        'label' => ['type' => 'varchar', 'eval' => ['mandatory' => true]],
        'src' => ['type' => 'varchar'],
        'isstartpage' => ['type' => 'checkbox', 'value' => 0],
        'visible' => ['type' => 'checkbox', 'value' => 1],
        'jumpTo' => [
            'type' => 'select',
            'foreignKey' => 'page.label',
            'foreignKeyisPage' => true,
            'value' => 0,
        ],
        'ordning' => ['type' => 'hidden', 'value' => 0],
        'companyid' => ['type' => 'hidden', 'value' => 0],
        'deleted' => ['type' => 'hidden', 'value' => 0],
    ],
];
