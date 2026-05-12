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

 $GLOBALS['TABLE']['company']=array (
            'table' => 'company',
            'list'  => array(
                'order' => 'id,namn',
                'width' => '',
                'edit' => true,
                'buttons' => array (
            			array('action' =>'action=settings','icon' => 'settings', 'label' => 'Set company specific settings')
            		)
            ),
            'form'      =>  array (
                'palette'   =>  'id,namn'
            ),
            'fields' => array (
                'id' => array(
                    'type'     => 'hidden',
                    'caption'  => $GLOBALS['LANG']['company']['id']
                ),
                'namn' => array(
                    'type'     => 'varchar',
                    'caption'  => $GLOBALS['LANG']['company']['namn']
                )
            )
        );
