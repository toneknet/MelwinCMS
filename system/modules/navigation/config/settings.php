<?php

/*
 *  settingsfilen
 *
 * Module: listandedit
 */

$GLOBALS['MODULE']['navigation']=array (
    'table' => false,
    'template'  => true,
    'template_prefix'  => 'nav_',
    'form'  =>  array (
      'palette' =>  false,
    ),
    'fields' => array (
      'root' => array(
        'type'     => 'select',
        'field'    => 'list[root]',
        'caption'  => $GLOBALS['LANG']['navigation']['root_dir'],
        'value'    => '',
        'options'  => getPageStructure()
      )
	)
);
