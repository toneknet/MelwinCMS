<?php
/*
Name: Navigation module
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Add navigation to your site. Added support for subtemplates in /tpl/.../templates/nav_?
Version: 0.2
Type: Module
*/
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

/**
 * Description of navigation
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class navigation {

    function __construct($arrModuleSettings) {
      //showErrors();
//       a:2:{i:1;s:1:"1";i:3;s:1:"1";}
        // Bygger access här
        $access = $arrModuleSettings['own_access_pages'];
//         print serialize(array('template'=>'footer'));
        if (is_array($access)) {
            foreach ($access as $key => $value) {
                if ($value['allow']) {
                    $arrWhere[] = "`id` = '$key'";
                }
            }
            if (is_array($arrWhere)) {
                $strWhere = implode(' OR ', $arrWhere);
            }

    //        $strWhere = "1=1";
            if (!empty($arrModuleSettings['template'])) {
              $tpl_file = $arrModuleSettings['template'];
            } else {
              $tpl_file = MODPATH . __CLASS__ ."/tpl/nav_4.tpl";
              /*$tpl_file = "nav_3";*/
              //$tpl_file = "nav_4";
              /*if (isSuperUser()) {
                $tpl_file = "nav_4";
              }*/
            }
//$tpl_file = "nav_4";
            //print $tpl_file."<br />";
            /*print "<pre>";
            print_r($arrModuleSettings);
            print "</pre>";*/
            if (isset($arrModuleSettings['list']['root'])) {
              $pid = $arrModuleSettings['list']['root'];
            } else {
              $pid = 0;
            }

            global $k;
            //print $arrModuleSettings['template'];
            //$tpl = new template(MODPATH . __CLASS__ ."/tpl/" . $tpl_file . ".tpl");
            $tpl = new template($tpl_file);
            $db = db::getInstance();
            $dbr = $db->o_get_all('page',"SELECT * FROM [table] WHERE visible=1 AND ( " . $strWhere . " ) AND deleted=0 ORDER BY pid, ordning");
            // Fix for other language here
            if ($GLOBALS['CONFIG']['language']!="en" || $GLOBALS['CONFIG']['language']!="") {
                $strLangLabel = "label_" . $GLOBALS['CONFIG']['language'];
                foreach ($dbr as $key => $value) {
                    // Kolla först att fältet finns i tabellen
                    if (!isset($value[$strLangLabel]) || !is_string($value[$strLangLabel])) {break;}
                    // Om ej tomt
                    if ($value[$strLangLabel]!="") {
                        $dbr[$key]['label'] = $value[$strLangLabel];
                    }
                }

            }
            // Build tree
            $data = $this->buildTree($dbr, $pid,0,2);
            /*print "<pre>";
            print_r($data);
            print "</pre>";*/


            $tpl->navigation = $data;
            $tpl->publish();
        } else {
            print "ERROR:Navigation";
        }
//            print "<pre>";
//            print_r($dbr);
//            print "TREEE!!\n";
//            print_r($data);
//            print "</pre>";
    }

    /*
     * $array =
     */

    function buildTree($arrData, $pid = 0, $level = 0, $depth = 99) {
        $arrMenu = array();
        if ($level <= $depth) {
            $has_children = false;
            if (is_array($arrData)) {
                foreach ($arrData as $key => $value) {
                    $class = "";
                    if ($value['pid'] == $pid) {
                       if ($has_children === false) {
                           $has_children = true;
                           $level++;
    //                       print "Has Children: " . $value['id'] . "<br>";
                       }
                    $arrMenu[] = array (
                        'href' => URLBASE . $value['src'],
                        'title' => $value['label'],
                        'sub'   => $this->buildTree($arrData, $value['id'], $level, $depth),
                        'class' => trim($class)
                        );
                    }
                }
                return (array) $arrMenu;
            }
        }
    }



//    function generate_navigation($array, $parent = 0, $level = 0, $deep = 99) {
//        if ($level <= $deep) {
//            $has_children = false;
//            foreach ($array as $key => $value) {
//                $class = "";
//                if ($value['pid'] == $parent) {
//                    if ($has_children === false) {
//                        $has_children = true;
//                        $level++;
//                    }
//                    // Om denna sida!
//                    if ($value['input'] == $this->input->get('pages')) {
//                        $class.=" current";
//                    }
//                    if ($value['isAdmin'] == 1) {
//                        $class.=" admin";
//                    }// else {$class="";}
//                    $arrMenu[] = array(
//                        'href' => fixUrl("index.php?pages=" . $value['input']),
////                        'href' => addUrl(array('pages'=>$value['input'])),
//                        'title' => $value['label'],
//                        'sub' => $this->generate_navigation($array, $key, $level, $deep),
//                        'class' => trim($class),
//                        'aclass' => trim($class)
//                    );
//                }
//            }
//            return (array) $arrMenu;
//        }
//    }
//
//
}



//
////
///**
// * Skapar navigation
// *
// * Be careful the source has not been tested yet, it's probably very buggy.
// * Any help and comments are welcome...
// *
// * @author		Martin Tonek <martin@tonek.se>
// * @version	$Id: navigation.cls.php,v 1.1 20120712 uw Exp $
// */
//class navigation extends modules { #kernel {
//
//    /**
//     * Template
//     * @var string
//     */
//    public $strTemplate = 'nav_standard';
//
//    /**
//     * Runs the naviagtion process.
//     * Prints the output.
//     */
//    function run() {
//        // S�kerheten redan h�r!
//        $getAccessForNavigation=$this->user->getNavSQL();
//        #print "#  " . $getAccessForNavigation . "  #<br>";
//        // Kör dbanrop här
//
//        $nav_data = $this->db->dbQueryAndFetchAll("SELECT * FROM " . $GLOBALS['e_CONFIG']['dbPrefix'] . "page WHERE visible=1 AND ({$getAccessForNavigation}) AND deleted=0 ORDER BY pid, ordning");
//
////        $this->db->dbQuery("SELECT * FROM " . $GLOBALS['e_CONFIG']['dbPrefix'] . "page WHERE visible=1 AND ({$getAccessForNavigation}) AND deleted=0 ORDER BY pid, ordning");
////        if ($this->db->dbNumRows() != 0) {
////            $nav_data = array();
////            while ($row = $this->db->dbRetRow()) {
////                $nav_data[$row['id']] = array(
////                    'id' => $row['id'],
////                    'input' => $row['input'],
////                    'label' => $row['label'],
////                    'pid' => $row['pid'],
////                    'isAdmin' => $row['isAdmin']
////                );
////            }
////        }
//
//        $tpl = new template();
//        $tpl->load($this->strTemplate);
//        $arrMenu = $this->generate_navigation($nav_data, $this->arrModuleSettings['parent'], 0, $this->arrModuleSettings['deep']);
//        $tpl->items = $arrMenu;
//        print $tpl->publish();
//    }
//
//    /**
//     * Generates navigation. This is a subfunction to NAVIGATION::run
//     * @param array $array The array with the pages in
//     * @param integer $parent The parent of the actual listning
//     * @param inteher $level The level the listning is on
//     * @param integer $deep The allowed depth of the listning
//     * @return array $arrMenu Returns the navigation as an array to process
//     */
//    function generate_navigation($array, $parent = 0, $level = 0, $deep = 99) {
//        if ($level <= $deep) {
//            $has_children = false;
//            foreach ($array as $key => $value) {
//                $class = "";
//                if ($value['pid'] == $parent) {
//                    if ($has_children === false) {
//                        $has_children = true;
//                        $level++;
//                    }
//                    // Om denna sida!
//                    if ($value['input'] == $this->input->get('pages')) {
//                        $class.=" current";
//                    }
//                    if ($value['isAdmin'] == 1) {
//                        $class.=" admin";
//                    }// else {$class="";}
//                    $arrMenu[] = array(
//                        'href' => fixUrl("index.php?pages=" . $value['input']),
////                        'href' => addUrl(array('pages'=>$value['input'])),
//                        'title' => $value['label'],
//                        'sub' => $this->generate_navigation($array, $key, $level, $deep),
//                        'class' => trim($class),
//                        'aclass' => trim($class)
//                    );
//                }
//            }
//            return (array) $arrMenu;
//        }
//    }
//
//}
//
