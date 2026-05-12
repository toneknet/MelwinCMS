<?php
/*
Name: Updater
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Lets you update your system and let it be up to date
Version: 0.1
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
 * Redovisa arbetstid
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class updater {

    private $dbFormSettings = array();
    private $arrRemote = array();
    private $arrLocal = array();
    private $localVersion = "";
    private $remoteVersion = "";

    function __construct() {
      /*
          Make it as you can select to
          install plugins
          show/update installed plugins
          Always check ajax and system
          and Always ask to make backup of everything if failure occure
       */
      /*ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);*/
      //print __CLASS__ . " - " . __FUNCTION__ . "<br />";
      $this->checkAjax();
      $this->getRemoteVersions();
      $this->getLocalVersions();
      $this->compareVersions();
      // Buttons??
      // Installed Plugins, Install Plugin
      $this->listPlugins();
      print "<style>
      .install_button,
      .install_button_red {
        background-color: #4CAF50; /* Green */
        border: none;
        border-radius: 5px;
        color: white;
        padding: 1px 4px;
        text-align: center;
        text-decoration: none;
        /*display: inline-block;*/
        font-size: inherit;
        float:right;
      }
      .install_button:hover {
        background-color: white;
        color: #4CAF50;
      }
      .install_button_red {
        background-color: #b02a2a;
        border-color: #b02a2a;
      }
      .install_button_red:hover {
        background-color: white;
        border-color: #b02a2a;
        color: #b02a2a;
      }

      </style>";

    }

    function checkAjax() {
      //showErrors();
      $db = db::getInstance();
      $input = new input();
      $arrResult = $db->o_get_all('ajax',"SELECT id,auth,name,script FROM [table] WHERE deleted=0","name");
      $paths = getModulePathsOnly();
      foreach ($paths as $path) {
        $path = MODPATH.$path;
        $file = $path."/config/ajax.php";
        if (is_file($file)) {
          include_once($file);
        }
      }

      /*print "<pre>";
      print_r($arrResult);
      print_r($arrAjax);
      print "</pre>";*/


      // status:
      // 1 = update
      // 2 = delete
      // 0 = none

      // Compare file with db first = add/change
      $arrChanged = 0;
      $arrModules = [];
      foreach ($arrAjax as $k => $v) {
        if(!isset($arrResult[$k])) {
          $arrAjax[$k]['name'] = $k;
          $arrAjax[$k]['status'] = 1;
          $arrChanged++;
          $arrModules[] = $k;
        } elseif( $v['auth'] != $arrResult[$k]['auth'] || $v['script'] != $arrResult[$k]['script'] ) {
          $arrAjax[$k]['status'] = 1;
          $arrResult[$k]['status'] = 2;
          $arrChanged++;
          $arrModules[] = $k;
        } else {
          $arrResult[$k]['status'] = 0;
        }
      }
      // If somethings left in DB it would be deleted, or?
      foreach ($arrResult as $k => $v) {
        if (isset($v['status'])) continue;
        $arrResult[$k]['status'] = 2;
      }
      $arrGlued = array_merge($arrAjax,$arrResult);
      /*print "<pre>";
      print_r($arrGlued);
      print "</pre>";*/
      if ($input->get('action') == "ajax-update") {
        foreach ($arrGlued as $k => $v) {
          if ($v['status'] == 1) {
            // ADD
            //print "ADDED ".$v['script']."<br />";
            unset($v['status']);
            $db->o_insert('ajax',$v);
          } elseif($v['status'] == 2 && isset($v['id'])) {
            //print "DELETED ".$v['script']."<br />";
            $db->o_delete('ajax',$v['id']);
          //} else {
            //print "Nothing ".$v['script']."<br />";
          }
        }
        $arrChanged = 0;
      }
      if($arrChanged) {
        //print "Ajax CVhanged {$arrChanged} times<br />";
        print implode(',',$arrModules);
        setMsg('w',"Ajax have {$arrChanged} new updates.<br><b> Module: " . implode('<br>Module: ',$arrModules) . "</b> <br> Please update now! <a href=\"?action=ajax-update\">UPDATE</a>");
      } else {
        setMsg('n','Ajax is up to date');
      }


      // Compare , but not recognise if values has changed like script or auth values
      /*if (count(array_diff_key($arrAjax, $arrResult))>0 || count(array_diff_key($arrResult, $arrAjax))>0) {
        print "<pre>";
        print_r($arrAjax);
        print_r($arrResult);
        print "</pre>";
        if ($input->get('action') == "ajax-update") {
          // sort
          foreach ($arrAjax as $key => $value) {
            if (isset($arrResult[$key])) {
              unset($arrAjax[$key]);
              unset($arrResult[$key]);
            } else {
              $arrAjax[$key]['name'] = $key;
            }
          }
          // add
          if (count($arrAjax)>0) {
            foreach ($arrAjax as $key => $value) {
              print "AJAX (install) [$key] ".$value['script']."<br />";
              //$db->o_insert('ajax',$value);
            }
            setMsg('n','Ajax has been added');
          }
          // delete
          if (count($arrResult)>0) {
            foreach ($arrResult as $key => $value) {
              print "AJAX (delete) [$key] ".$value['script']."<br />";
              //$db->o_delete('ajax',$value['id']);
            }
            setMsg('n','Ajax has been deleted');
          }
        } else {
          setMsg('w','Ajax needs update <a href="?action=ajax-update">UPDATE</a>');
        }
      } else {
        setMsg('n','Ajax is up to date');
      }*/
    }

    function getRemoteVersions() {
      $serverUrl = $GLOBALS['CONFIG']['UpdateServer'];
      //print $serverUrl;
      $this->arrRemote = json_decode(file_get_contents($serverUrl),true);
      /*print "<pre>";
      print_r($this->arrRemote);
      print "</pre>";*/
    }

    function getLocalVersions() {
      // https://stackoverflow.com/questions/24099741/reading-commented-details-in-a-file-like-wordpress-theme-engine
      //print __FUNCTION__ ."<br />";
      // Set headers here
      $file_headers = array(
        'Name'        => 'Name',
        //'ThemeURI'    => 'Theme URI',
        'Description' => 'Description',
        'Author'      => 'Author',
        'AuthorURI'   => 'Author URI',
        'Type'        => 'Type',
        'Version'     => 'Version',
        'Template'    => 'Template',
        'Status'      => 'Status',
        'Tags'        => 'Tags',
        'TextDomain'  => 'Text Domain',
        'DomainPath'  => 'Domain Path'
      );
      // Load LOCAL kernel version
      $data = $this->get_file_data( SYSPATH .'configs/version.php', $file_headers);
      $this->arrLocal['kernel'] = $data['Version'];
      // Load LOCAL modules version
      $arrModuleLibs = array_diff(scandir(SYSPATH."modules"), array('..', '.'));
      foreach ($arrModuleLibs as $modulelib) {
        // Remove disabled modules
        if ($modulelib[0] == "_") { continue;}
        // Remove files in module root
        if (is_file(SYSPATH . "modules/" .$modulelib)) {continue;}
        // Check for versions
        $this->arrLocal['modules'][$modulelib] = $this->get_file_data( SYSPATH . "modules/" . $modulelib . "/" . $modulelib . ".php", $file_headers);
        $this->arrLocal['modules'][$modulelib]['installed'] = 1;
        $this->arrRemote['modules'][$modulelib]['key'] = $modulelib;
        //$this->arrLocal['modules'][$modulelib]['installed'] = 1;
      }
      /*print "<pre>";
      //print_r($this->arrLocal);
      //print json_encode($this->arrLocal['modules']);
      print "</pre>";*/
    }

    function compareVersions() {
      // Lokala kan inte triumfa remote i version
      // Gå bara igenom remote mot lokala
      if (isset($this->arrRemote['kernel']) && isset($this->arrLocal['kernel']) && $this->arrRemote['kernel'] > $this->arrLocal['kernel']) {
        setMsg('w', 'A new version of the kernel is avalible! <br />Local version: ' . $this->arrLocal['kernel'] . '<br />Remote version: ' . $this->arrRemote['kernel'] . '<br /> <a href="#" class="">UPDATE SYSTEM NOW!</a>');
        //print "<a href=\"#\" class=\"install_button_red\" title=\"" . $GLOBALS['LANG']['updater']['update'] . "\">A System update required! UPDATE SYSTEM NOW!</a>";
      } elseif(isset($this->arrRemote['kernel'])) {
        setMsg('n', 'System is up to date<br />Local version: ' . $this->arrLocal['kernel'] . '<br />Remote version: ' . $this->arrRemote['kernel'] . '');
      } else {
        setMsg('n', 'System is up to date<br />Local version: ' . $this->arrLocal['kernel'] . '<br />Remote version: <i style="color:red;">NOT AVALIBLE</i>');
      }
      //print "<span style=\";position: sticky; top:1rem;background-color:#FFF8C4;color:black;border-radius:0.5rem;border:none;padding:0.5rem;max-width: fit-content;\">System v. " . $this->arrLocal['kernel'] . "</span>";
      $strMessage = "";
      foreach ($this->arrRemote['modules'] as $key => $value) {
        $this->arrRemote['modules'][$key]['installed'] = 0;
        $this->arrRemote['modules'][$key]['key'] = $key;
        if ( isset($this->arrLocal['modules'][$key]['Version']) && is_numeric($this->arrLocal['modules'][$key]['Version']) && isset($this->arrRemote['modules'][$key]['Version']) && $this->arrRemote['modules'][$key]['Version'] > $this->arrLocal['modules'][$key]['Version']) {
          //$strMessage.= "MODULE: <b>" . $this->arrRemote['modules'][$key]['Name'] . "</b> has an update avalible! <a href=\"#\" class=\"install_button\">UPDATE NOW!</a><br />";
          $this->arrRemote['modules'][$key]['installed'] = 2;
          $this->arrLocal['modules'][$key]['installed'] = 2;
          $this->arrLocal['modules'][$key]['new_version'] = $this->arrRemote['modules'][$key]['Version'];

        } elseif ( isset($this->arrLocal['modules'][$key]) ) {
          $this->arrRemote['modules'][$key]['installed'] = 1;
          //$this->arrLocal['modules'][$key]['installed'] = 1;
        } elseif ( !isset($this->arrLocal['modules'][$key]) ) {
          $this->arrRemote['modules'][$key]['installed'] = 0;
        }
      }
      if (strlen($strMessage)>0) {
        setMsg('n', $strMessage);
      }
    }

    function listPlugins() {
      //print __FUNCTION__."<br />";

      print "<style>
        .updater_main_field {
          white-space: nowrap;
          vertical-align:top;
        }
        .updater_description_field {
          vertical-align:top;
        }
        .updater_installer_field {
          text-align:center;
          white-space: nowrap;

        }

      </style>";

      // Anropa dblist
      $dblist = new dblist();
      $this->dbFormSettings =  array (
                'table' => 'updater',
                'list'  => array (
                  'order' => 'Name,Description,installed'
                ),
                'fields' => array (
                  'Name' => array(
                    'type'     => 'varchar',
                    'caption'  => $GLOBALS['LANG']['updater']['name'],
                    'class_list' => 'updater_main_field',
              			'list_callback' => array('clsUpdater','title')
                  ),
                  'Description' => array(
                    'type'     => 'varchar',
                    'caption'  => $GLOBALS['LANG']['updater']['description'],
                    'class_list' => 'updater_description_field',
              			'list_callback' => array('clsUpdater','smallText')
                  ),
                  'installed' => array(
                    'type'     => 'varchar',
                    'caption'  => $GLOBALS['LANG']['updater']['installed'],
              			'list_callback' => array('clsUpdater','checkButton'),
                    'class_list' => 'updater_installer_field',
                  ),
                )

              );
      //$dblist->setData($this->arrRemote['modules']);
      $dblist->setData($this->arrLocal['modules']);
      $dblist->MakeWithSettings($this->dbFormSettings);
    }

    function installPlugins() {
      print __FUNCTION__."<br />";
    }

    function fixStatus($dca) {
      print $dca;
    }


    /**
     * Retrieve metadata from a file.
     *
     * Searches for metadata in the first 8kiB of a file, such as a plugin or theme.
     * Each piece of metadata must be on its own line. Fields can not span multiple
     * lines, the value will get cut at the end of the first line.
     *
     * If the file data is not within that first 8kiB, then the author should correct
     * their plugin file and move the data headers to the top.
     *
     * @param string $file Path to the file
     * @param array $default_headers List of headers, in the format array('HeaderKey' => 'Header Name')
     */

    function get_file_data( $file, $default_headers) {
        //print __FUNCTION__ ."<br />";
        //print $file."<br />";
        if (!file_exists($file)) { return array();}
        $fp = fopen( $file, 'r' );
        $file_data = fread( $fp, 8192 );
        fclose( $fp );
        $file_data = str_replace( "\r", "\n", $file_data );
        $all_headers = $default_headers;

        foreach ( $all_headers as $field => $regex ) {
            if (preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match )
                && $match[1])
                $all_headers[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
            else
                $all_headers[ $field ] = '';
        }

        return $all_headers;
    }

}

class clsUpdater {
  function checkButton($dca) {
    /*print "<pre>";
		print_r($dca);
		print "</pre>";*/
    if (isset($dca['installed'])) {
      if (!isset($dca['key'])) $dca['key'] = "";


      $d = $dca['installed'];
      if ($d == 0) {
        // Ej installerad
        // $GLOBALS['LANG']['updater']['install']
        // installed, install,update 24
        return "<a href=\"?install=" . $dca['key'] . "\" class=\"\" title=\"" . $GLOBALS['LANG']['updater']['install'] . "\">" . getIconFull('install24') . "</a>";
      } elseif ($d == 1) {
        // Installerad och uppdaterad
        return getIconFull('installed24','','',$GLOBALS['LANG']['updater']['isinstalled']) . getIconFull('delete');
      } elseif ($d == 2) {
        // Behöver uppdateras
        return "<a href=\"?update=" . $dca['key'] . "\" class=\"\" title=\"" . $GLOBALS['LANG']['updater']['update'] . "\">" . getIconFull('update24') . "</a>" . getIconFull('delete');
        return $d;
      } else {
        return "ERROR";
      }


    }
  }

  function smallText($dca) {
      if(!isset($dca['Description'])) return "ERROR!@" . __LINE__;
      $output = "<i style=\"font-size:12px;\">" . $dca['Description'];
      $output.= ($dca['Author']!="")? "<br />Author: ". $dca['Author'] : "";
      $output.= "</i>";
      return $output;
  }

  function title($dca) {
      if (!isset($dca['Name'])) return "ERROR!@" . __LINE__;
      $output = $dca['Name']."<br />";
      // if new version show it in red!
      if (isset($dca['Version'])){
        $output.= "<p style=\"font-size:12px;\">v: ". $dca['Version'];
        if (isset($dca['new_version'])) $output.=" <span style=\"color:red\">(NEW v. " . $dca['new_version'] . ")</span>";
        $output.= "</p>";
      }
      //$output.= "</i>";
      return $output;
	}

  /*function link2test($dca) {
		print "<pre>";
		print_r($dca);
		print "</pre>";
	}*/
}
