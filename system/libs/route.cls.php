<?php

/**
Name: Route
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles routing of pages and modules.
Version: 0.1
Type: Library
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
 * Description of input
 *
 * @author Martin Tonek <martin@tonek.se>
 */

class route
{
    protected static $instance = null;
    private static $_instance = null;

    static $uri;
    static $query;
    static $pageid;
    static $db;

    protected function __clone()
    {
        // Me not like clones! Me smash clones!
    }

    public static function getInstance()
    {
        if (!is_object(self::$_instance)) { //or if( is_null(self::$_instance) ) or if( self::$_instance == null )
            self::$_instance = new route();
            // print "NOT " . __CLASS__ . "::" . __FUNCTION__."<br>";
        } else {
            // print "EXIST " . __CLASS__ . "::" . __FUNCTION__."<br>";
        }
        return self::$_instance;
    }

    function __construct()
    {

        if (isset($_SERVER['REQUEST_URI'])) {
            $url = $_SERVER['REQUEST_URI'];
        } else {
            throw new Exception('SYSTEM ERROR. Routing not possible.');
        }
        $purl = parse_url($url);
        $uri = $purl['path'];
        self::$uri = $uri;
        if (isset($purl['query'])) self::$query = $purl['query'];
        // Get Access for page
        $auth = auth::getInstance();
        $arrGroups = $auth->getAccess();
        // Get all pages
        $db = db::getInstance();
        $page_id = 0;
        $start_id = 0;
        $arrPage = $db->o_get_all('page', "SELECT * FROM [table] WHERE visible=1 AND deleted=0 ORDER BY isstartpage DESC, ordning ASC");
        // Check for page
        foreach ($arrPage as $page) {
            if ($page['isstartpage'] == 1) {
                $start_id = $page['id'];
            }
            if ($page['src'] == $uri) {
                $id = $page['id'];
                if ($page['jumpTo'] >= 1) {
                    $jumpTo = $page['jumpTo'];
                    header("Location: " . $arrPage[$jumpTo]['src']);
                    exit;
                }
                // Check for ACCESS, only assign page_id if allowed
                if (isset($arrGroups[$id]['allow']) && $arrGroups[$id]['allow'] == 1) {
                    self::$pageid = $id;
                    $page_id = $id;
                }
                break;
            }
        }

        // No page found
        // SOLVED: This gives user error if not allowed for page. Redirect to STARTPAGE if it is NOT?
        if ($page_id == 0 && $start_id == 0) {
            template::_page404();
            die();
        } elseif ($page_id == 0 && $start_id != 0) {
            $page_id = $start_id;
        }

        // Load General language files
        $this->loadLanguageFile(SYSPATH . "lang/");

        // Load Modules data
        foreach (getModulePathsOnly() as $modulelib) {
            // Remove underscore modules here
            if (substr($modulelib, 0, 1) == "_") {
                continue;
            }

            // Load module config
            $this->loadFile(SYSPATH . "modules/" . $modulelib . "/config/" . $modulelib . ".php", "CONFIG");
            // Load module language file
            $this->loadLanguageFile(SYSPATH . "modules/" . $modulelib . "/lang/");
            // Load module dca ( Data Container Array )
            $dir = SYSPATH . "modules/" . $modulelib . "/dca/";
            if (is_dir($dir)) {
                $arrTableDataFiles = array_diff(scandir($dir), array('..', '.', 'index.php'));
                if (!empty($arrTableDataFiles)) {
                    foreach ($arrTableDataFiles as $file) {
                        if (is_file($dir . $file)) {
                            $GLOBALS['DEBUG'][] = "LOADED DCA FILE : " . $dir . $file;
                            require_once($dir . $file);
                        }
                    }
                }
            }
        }
        $tpl = new template();
        // Load modules assigned to page
        $db2 = $db->o_get_all('page_content', "SELECT * FROM [table] WHERE (pid = $page_id OR pid = 0) AND visible=1 AND deleted=0 ORDER BY ordning ASC");
        foreach ($db2 as $modules) {
            $GLOBALS['DEBUG'][] = "MODULE (loading): " . $modules['module'] . "  [" . $modules['target'] . "]";
            // Add settings and access
            if ($modules['settings'] != "") {
                $arrModuleSettings = unserialize($modules['settings']);
            } else {
                $arrModuleSettings = array();
            }
            $arrModuleSettings['own_access_pages'] = $arrGroups;
            $arrModuleSettings['own_access'] = $arrGroups[$page_id];
            // Start collecting output
            ob_start();
            // Load module here
            $mod = new $modules['module']($arrModuleSettings);
            // Get the output
            $strOut = ob_get_clean(); // ob_get_contents, ob_get_flush
            // Clear output
            // ob_end_clean();
            // Add to template
            $tpl->add($modules['target'], $strOut);
        }
        $tpl->publish();
    }

    function loadFile(string $file, string $type = '')
    {
        if (is_file($file)) {
            $GLOBALS['DEBUG'][] = "LOADED {$type} FILE (route::loadFile) : " . $file;
            require_once($file);
        }
    }

    function loadLanguageFile(string $path)
    {
        $file = $path . "en.php";
        if (is_file($file)) {
            $GLOBALS['DEBUG'][] = "LOADED LANGUAGE FILE (route::loadLanguageFile) : " . $file;
            require_once($file);
        }
        // If not english as standard
        if (isset($GLOBALS['CONFIG']['language']) && $GLOBALS['CONFIG']['language'] != "en") {
            $file = $path . $GLOBALS['CONFIG']['language'] . ".php";
            if (is_file($file)) {
                $GLOBALS['DEBUG'][] = "LOADED LANGUAGE FILE (route::loadLanguageFile) : " . $file;
                require_once($file);
            }
        }
    }
    public static function getURLbyID(int $id): string
    {
        $db = db::getInstance();
        $page = $db->o_get_row('page', "SELECT id,src FROM [table] WHERE id=" . $id . " AND deleted=0");
        // pre($page);
        if (empty($page['src'])) return '';
        // pre($GLOBALS['CONFIG']['www-root'] . $page['src']);
        return $GLOBALS['CONFIG']['www-root'] . $page['src'];
        // return 'TEST';
    }

    public static function getPageStructureIDOnly()
    {
        $db = db::getInstance();
        $arrPage = $db->o_get_all('page', "SELECT * FROM [table] WHERE visible=1 AND deleted=0 ORDER BY isstartpage DESC, ordning ASC");
        // Check for page
        // foreach ($arrPage as $page) {
        // pre($arrPage);
        return (array) self::buildTree($arrPage);
        // }
    }

    public static function isPageChildOf($mother = null)
    {
        // $db = db::getInstance();
        // $arrPage = $db->o_get_all('page', "SELECT * FROM [table] WHERE visible=1 AND deleted=0 ORDER BY isstartpage DESC, ordning ASC");
        $tree = self::getPageStructureIDOnly();
        return self::isChildOf($tree, self::$pageid, $mother);
        // 1 mother, 2 page
        // pre($tree);
        // print "X";
        // var_dump(self::isChildOf($tree, 2, 1));
        // print "X";
        // need mother and children
        // pre(getPageStructure());
        // $arrMeny = [
        //     0 => [
        //         17 => NULL,
        //         10 => NULL,
        //         11 => NULL,
        //         13 => NULL,
        //         16 => NULL,
        //         1 => [
        //             2 => NULL,
        //             4 => NULL,
        //             3 => NULL,
        //             5 => NULL,
        //             15 => NULL,
        //             6 => NULL,
        //             7 => NULL,
        //             8 => NULL,
        //             9 => NULL,
        //             14 => NULL,
        //         ],

        //     ],
        // ];
        // pre($arrMeny);
    }





    public static function isChildOf($tree, $childId, $parentId)
    {
        foreach ($tree as $nodeId => $node) {
            // Kontrollera om den aktuella noden är föräldern vi letar efter
            if ($nodeId == $parentId) {
                // Kontrollera om den aktuella noden har barn och om något av dessa barn är det vi letar efter
                if (isset($node) && array_key_exists($childId, $node)) {
                    return true;
                }
                // Om inte, fortsätt att söka rekursivt i barnen
                foreach ($node as $childNode) {
                    if (self::isChildOf($childNode, $childId, $parentId)) {
                        return true;
                    }
                }
            } else {
                // Om den aktuella noden inte är föräldern, fortsätt att söka rekursivt i dess barn
                if (isset($node)) {
                    if (self::isChildOf($node, $childId, $parentId)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function buildTree(array $elements, $parentId = 0)
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['pid'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $branch[$element['id']] = $children;
                } else {
                    $branch[$element['id']] = null;
                }
            }
        }

        return $branch;
    }
    // From drupal 7.x version
    //https://stackoverflow.com/questions/24726223/serverrequest-uri-is-it-secure
    // function request_uri() {
    //     if (isset($_SERVER['REQUEST_URI'])) {
    //         $uri = $_SERVER['REQUEST_URI'];
    //     }
    //     else {
    //         if (isset($_SERVER['argv'])) {
    //         $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
    //         }
    //         elseif (isset($_SERVER['QUERY_STRING'])) {
    //         $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
    //         }
    //         else {
    //         $uri = $_SERVER['SCRIPT_NAME'];
    //         }
    //     }

    //     // Prevent multiple slashes to avoid cross site requests via the Form API.
    //     $uri = '/' . ltrim($uri, '/');
    //     return $uri;
    // }

}
