<?php

/*
 *  settingsfilen
 *
 * Module: options
 */

$GLOBALS['MODULE']['editonly']=array (
    'table' => true,
    'template'  => false,
    'form'  =>  array (
      'palette' =>  true,
    ),
    'fields' => array (
/*        'order' => array(
		       'type'     => 'varchar',
           'field'     => 'list[order]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['order'],
           'value'    => '',
		    ),
        'width' => array(
		       'type'     => 'varchar',
           'field'     => 'list[width]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['width'],
           'value'    => '',
		    ),
        'edit' => array(
		       'type'     => 'checkbox',
           'field'     => 'list[edit]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['edit'],
           'value'    => '',
        ),*/
        'id' => array(
            'type'      => 'varchar',
            'field'     => 'list[id]',
            'caption'   => $GLOBALS['LANG']['EDITONLY']['id'],
            'value'     => '',
		),
	)
);
