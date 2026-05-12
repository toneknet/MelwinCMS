<?php
$GLOBALS['TABLE']['vehicle'] = array (
    'table' => 'vehicle',
    'list'  => array (
        'order' => 'regnr,b_name,b_color,b_visible',
        'width' => '',
        'edit'  => true
    ),
    'allowAjax' => true,
    'form'      =>  array (
		  'palette'   =>  'id,regnr,kategori,b_name,b_id,b_color,b_visible'
	  ),
    'fields' => array (
        'id' => array (
            'type' => 'hidden',
            'caption' => $GLOBALS['LANG']['vehicle']['id']
        ),
        'regnr' => array (
            'caption' => $GLOBALS['LANG']['vehicle']['regnr'],
            'class_list' => 'nowrap',

        ),
        'kategori' => array (
            'type'    =>  'radio',
            'caption' => $GLOBALS['LANG']['vehicle']['kategori'],
            'db'      => array (
                'db'      =>  'categories',
                'id'      =>  'id',
                'value'   =>  'name',
                'where'   =>  'groups=1',
                'order'   =>  'ordning'
            )
        ),
        'ordning' => array (
            'type'=>'hidden',
            'caption' => $GLOBALS['LANG']['vehicle']['ordning']
        ),
        'active' => array (
            'type' => 'hidden',
            'caption' => $GLOBALS['LANG']['vehicle']['active']
        ),
        'b_name' => array (
            'caption' => $GLOBALS['LANG']['vehicle']['b_name'],
        ),
        'b_color' => array (
            'type'    =>  'color',
            'caption' => $GLOBALS['LANG']['vehicle']['b_color'],
            'class_list' => 'nowrap',
        ),
        'b_visible' => array (
            'caption' => $GLOBALS['LANG']['vehicle']['b_visible'],
            'class_list' => 'nowrap',
        ),
        'b_exist' => array (
            'caption' => $GLOBALS['LANG']['vehicle']['b_exist'],
            'class_list' => 'nowrap',
        ),
        'b_id' => array (
            'type' => 'hidden',
            'caption' => $GLOBALS['LANG']['vehicle']['b_id'],
            'value' => 0
        ),
        'deleted' => array (
            'type' => 'hidden',
            'caption' => $GLOBALS['LANG']['vehicle']['deleted'],
        ),
        'companyid' => array(
            'type'     => 'hidden',
            'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid'],
            'value' => getCompanyID()
        )
    ),
);

//$GLOBALS['table']['test'] = array();

//			'name' => array
//		(
//			'label'                   => &$GLOBALS['TL_LANG']['tl_Deltagare']['username'],
//			'exclude'                 => false,
//			'search'                  => true,
//			'inputType'               => 'text',
//			'eval'                    => array('mandatory'=>true, 'maxlength'=>255)
//		),
//			'label'                   => &$GLOBALS['TL_LANG']['tl_Deltagare']['pay'],
//			'exclude'                 => true,
//			'search'                  => true,
//			'inputType'               => 'checkbox',
//			'value'					  => '1',
//			'eval'                    => array('submitOnChange'=>true),
//			'save_callback' => array
//			(
//				array('tl_Deltagare', 'generateAlias')
//			)
//(
//	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['Lan_categories'],
//	'exclude'                 => true,
//	'inputType'               => 'select',
//	'foreignKey'              => 'tl_Lan.name',
//	'eval'                    => array('multiple'=>false, 'mandatory'=>true, 'tl_class'=>'w100')
//);
// Fixar språkfilen
//foreach ($GLOBALS['table']['vehicle']['fields'] as $key => $value) {
//    //print $value['caption']."<br>";
//    //print "\$GLOBALS['table']['vehicle']['fields']['']";
//    print "\$GLOBALS['LANG']['vehicle']['{$key}'] = array('" . $value['caption'] . "','');<br>";
//}
addJS("system/modules/system/js/vehicle.php");
