<?PHP
$GLOBALS['TABLE']['options']=array (
	'table' => 'options',
	'list'  => array(
		'order' => '',
		'width' => '',
		'edit' => false
	),
	'allowAjax' => 1,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['options']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		'palette'   =>  'id,option_key,option_value,ordning,companyid,deleted'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['options']['id']
		),
		'option_key' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['options']['option_key']
		),
		'option_value' => array(
			'type'     => 'text',
			'caption'  => $GLOBALS['LANG']['options']['option_value']
		),
		'ordning' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['options']['ordning']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid']
		),
		'deleted' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['options']['deleted']
		)
	)
);
