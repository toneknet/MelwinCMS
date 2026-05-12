<?php
$GLOBALS['TABLE']['service']=array (
	'table' => 'service',
	'list'  => array(
		'order' => 'datum,regnrtext,mil,atgard,signatur',
		'width' => '',
    'edit'  => false,
    'pdf'   => array (
      'filename'  =>  'Serviceprotokoll_%s_%s',
      'title'     =>  'Serviceprotokoll',
      'keyfield'  =>  'regnrtext'
    )
	),
    'search'    => array (
				'field'					=> 'q',
        'searchfields'  =>  'regnr', //regnrtext
				'searchexact'		=> true,
        'searchcaption' =>  $GLOBALS['LANG']['service']['searchcaption'],
        'type'          =>  'select',
        'order'         =>  'datum DESC',
        'foreignKey'    =>  'vehicle.regnr',
    ),
    'form'      =>  array (
        'palette'   =>  'id,regnr,regnrtext,mil,datum,atgard,tid,signatur'
    ),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['service']['id']
		),
		'atgard' => array(
			'type'     => 'textarea',
			'caption'  => $GLOBALS['LANG']['service']['atgard']
		),
		'regnr' => array(
			'type'          => 'select',
			'caption'       => $GLOBALS['LANG']['service']['regnr'],
      'foreignKey'    => 'vehicle.regnr',
      'eval'          =>  array('onClick' => 'document.getElementById("db_regnrtext").value = this.options[this.selectedIndex].innerHTML;')
		),
		'regnrtext' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['service']['regnrtext'],
            'class_list'    => 'nowrap'
		),
		'mil' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['service']['mil'],
            'class_list'    => 'nowrap'
		),
		'datum' => array(
			'type'     => 'date',
			'caption'  => $GLOBALS['LANG']['service']['datum'],
            'class_list'    => 'nowrap',
            'value' => getTodayDate()
		),
		'tid' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['service']['tid']
		),
		'signatur' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['service']['signatur']
		),
        'deleted' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['service']['deleted']
		),
                'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid'],
                        'value' => getCompanyID()
		)
	)
);
