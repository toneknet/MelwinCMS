<?php

/*
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

$GLOBALS['TABLE']['groups'] = array(
	'table' => 'groups',
	'list'  => array(
		'order' => 'namn,description',
		'width' => '',
		'edit' => true,
		'where' => 'deleted=0'
	),
	'allowAjax' => 0,
	//	'search'    => array (
	//		'searchfields'  =>  'regnrtext',
	//		'searchcaption' =>  $GLOBALS['LANG']['service']['searchcaption'],
	//		'type'          =>  'select',
	//		'order'         =>  'datum DESC',
	//		'foreignKey'    =>  'vehicle.regnr',
	//	),
	//	'form'      =>  array (
	//		'palette'   =>  'id,namn,description'
	//	),
	'fields' => array(
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'namn' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['groups']['namn']
		),
		'description' => array(
			'type'     => 'text',
			'caption'  => $GLOBALS['LANG']['groups']['description']
		),
		'settings' => array(
			'type'     => 'blob',
			'caption'  => $GLOBALS['LANG']['groups']['settings']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid'],
			'value' => getCompanyID()
		),
		'deleted' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['deleted']
		),
		'ordning' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['ordning']
		)
	)
);
