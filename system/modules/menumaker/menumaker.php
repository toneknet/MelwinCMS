<?php
/**
Name: Menumaker
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Managing menu system and all stuff around including page and contents
Version: 1.1
Type: Module
*/

/*
 * 20240107 : Moved from updateDB to ajaxRequest and added all JS functions
 *            needed to the html/menumaker.js.
 *
 * Denna klass fixar man menyerna i
 *
 *
 *
 * Är sidan tillåten om man inte är inloggad? Kryssruta?
 *
 *
 *
 *
 */
class menumaker {


//    private $dbFormSettings;
    private $allowEdit = false;
    private $arrLabels = array();
    private $bolShowMenu = true;
    private $arrPost = array();
    private $pageid = '';
    private $dbFormSettings;
    private $d = true;
    private $SU = false;
    private $arrModules = array();

    function __construct($arrModuleSettings) {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        addJS('system/modules/menumaker/html/menumaker.js');
        addCSS('system/modules/menumaker/html/menumaker.css');
        // Fixa Access här!
        if ($arrModuleSettings['own_access']['edit']) { $this->allowEdit = true;}
        $this->SU = isSuperUser();


        print "<style>
        .icons a {
          padding: 0px 1px;
        }
        .button {
          margin-right:5px;
        }

        </style>";

        $input = new input();

        // Load menyes here
        $db = db::getInstance();

        $this->sortOrderInDB(0);


        // Actions här!
        if ($this->allowEdit) {
          $this->arrModules = $db->o_get_all('page_content', "SELECT * FROM [table] WHERE deleted=0 ORDER BY pid,ordning");
          $table = "page";
          $this->dbFormSettings = $GLOBALS['TABLE']['page'];
          $this->dbFormSettings['form']['excl_CID'] = true;
          $intId = intval($input->get('id'));
          //print "@" . __LINE__ . ": " .$intId;
          $strAction = $input->get('action');
          // Check if allowed commands for action
          $arrAllowedActions = array('save','add','addnew','edit','move','show','hide','delete','module');
          if (!in_array($strAction,$arrAllowedActions)) {
            unset($_GET['action']);
            $strAction = "";
          }



          if($strAction=='save' && $input->post('table')==$table && $this->allowEdit) {
              // Kolla referenspunkter först
              if ($input->post('hash')== salt( $table . date("Y-m-d") . $input->post('db_id') ) ) {
                  $formcatcher = new formcatcher();
                  $formcatcher->catchform($this->dbFormSettings);
              } else {
                  setMsg('e', "ERROR: In catching form (Have you remembered to activate 'id' as a field in 'listandedit'?");
              }
          }

            if ($strAction == 'add' ||  ($strAction == 'edit' && $intId>0) ) {
              returnBtn();
              $form = new form();
              if ($intId>0) {
                  $form->loadData($this->dbFormSettings, $intId);
              }
              // Function to add cancelbutton to existing FORM
              //addCancelButton2Form($form->MakeWithSettings($this->dbFormSettings,true));
                    //   pre($this->dbFormSettings);
              $form->MakeWithSettings($this->dbFormSettings);
              //$strOutput = $form->MakeWithSettings($this->dbFormSettings,true);

              //if (intval($input->get('id'))>0) {
                  // Lägga till radera här!
                  // 2015-04-02 - La till bekräftelse på radera!
  //                print "<a href=\"?action=delete&datum=" . $input->get('datum') . "&id=" . intval($input->get('id')) . "\" class=\"button button-delete smallbutton\" onclick=\"return confirmDelete(" . intval($input->get('id')) . ");\" style=\"float: right;\" >Radera bokning</a>\n";
                  // 2023-03-13 - La till så att man fick radera innanför Form så det vart snyggare estetiskt
                //  $strOutput = str_replace("</form>","",$strOutput);
                //  $strOutput.= "<a href=\"?action=nothing\" class=\"button button-delete smallbutton\" style=\"float: right;\" >Avbryt</a>\n</form>\n";
              //}
              //print $strOutput;
              print "<script>\n";
              print "
              \$( document ).ready(function() {
                    console.log('now');
                    
                    // db_src
                });
              
              
              
              
              
              
              ";


              print "\n</script>\n";


              //$form->MakeWithSettings($this->dbFormSettings);
/*              if ($strAction == 'add' ||  $strAction == 'edit') {
                print "<a href=\"?action=module&id=" . $this->pageid . "\" class=\"button button-primary button_red\" style=\"\">Avbryt</a>";
              }*/
              $this->bolShowMenu = false;
            } elseif ($intId>=0) {
                if ($strAction == 'move') {
                    if ( $input->get('after') != '' ) {
                        $intAfter = intval($input->get('after'));
                        if ($intAfter>0) {
                            $arrAfter = $db->o_get_row('page','SELECT * FROM [table] WHERE id='.$intAfter);
                            // UPDATE
                            $intOrder = $arrAfter['ordning'];
                            $intOrder++;
                            $pid = $arrAfter['pid'];
                            if ($db->o_update('page',array('ordning' => $intOrder, 'pid' => $pid)," id=" . $intId)) {
                                setMsg('s', "Sidan med ID: " . $intId . " har lagts till efter sidan med ID: " . $intAfter);
                            }
                        }
                        // unset move
                        unset($_GET['action']);
                        unset($_GET['after']);
                    } elseif ($input->get('into') != '') {
                        $intInto = intval($input->get('into'));
                        if ($intInto>0) {
                            $arrInto = $db->o_get_row('page','SELECT * FROM [table] WHERE id='.$intInto);
                            // UPDATE
                            $pid = $arrInto['id'];
                            if ($db->o_update('page',array('ordning' => 1, 'pid' => $pid)," id=" . $intId)) {
                                setMsg('s', "Sidan med ID: " . $intId . " har lagts till i sidan med ID: " . $intInto);
                            }
                        }
                        // unset move
                        unset($_GET['action']);
                        unset($_GET['inte']);
                    }

                } elseif ($strAction == 'show') {
                    if ($db->o_update('page',array('visible' => 1)," id=" . $intId)) {
                        setMsg('s', "Sidan med ID: " . $input->get('id') . " har gjorts synlig");
                    }
                    unset($_GET['action']);

                } elseif ($strAction == 'hide') {
                    if ($db->o_update('page',array('visible' => 0)," id=" . $intId)) {
                        setMsg('s', "Sidan med ID: " . $input->get('id') . " har dolts");
                    }
                    unset($_GET['action']);

                } elseif ($strAction == 'delete') {
                    if ($db->o_update('page',array('deleted' => 1)," id=" . $intId)) {
                        setMsg('s', "Sidan med ID: " . $input->get('id') . " har raderats");
                    }
                    unset($_GET['action']);
                } elseif ($strAction == 'module') {
                    $this->initModules();
                    $this->bolShowMenu = false;
                }
            }
        }

        // Show menu
        if ($this->bolShowMenu) {
            // Get pages
            $arrUnsorted = $db->o_get_all('page','SELECT * FROM [table] WHERE deleted=0 ORDER BY pid, ordning'); // ORDER BY pid, ordning
            // Reset and get key
            reset($arrUnsorted);
            $key = key($arrUnsorted);
            // Get all Labels
            foreach ($arrUnsorted[$key] as $skey => $svalue) {
                if (strpos($skey, 'label_') === 0) {
                    $this->arrLabels[] = $skey;
                }
            }
            // Make list
            print "<h1>" . $GLOBALS['LANG']['menumaker']['title'] . "</h1>";
            // Lägg till ny!
            if ($this->allowEdit) {
                print "<div if=\"edit__button__field\">";
                // print "<a href=\"?action=add\" class=\"button button-primary smallbutton\">" . $GLOBALS['LANG']['menumaker']['add_new'] . "</a>\n";
                print "<a href=\"?action=addnew\" class=\"button button-primary smallbutton\">" . $GLOBALS['LANG']['menumaker']['add_new'] . "</a>\n";
                print "</div>";
            }

            /*print "<style>
            .menumaker td {
              vertical-align: top;
            }
            .menumaker td:nth-child(3) {
              color: grey;
            }
            </style>";*/
            if ($input->get('action') == 'move' || $input->get('action') == 'addnew') {
                print "<style>
                .geticons:not(.no_blur) {
                    filter: brightness(0) saturate(100%) invert(60%) sepia(0%) saturate(7500%) hue-rotate(75deg) brightness(100%) contrast(107%);
                }
                </style>";
            }



            print "<table width='100%' border='0' class=\"menumaker\">";
            print "
            <tr>
              <th>" . $GLOBALS['LANG']['menumaker']['header']['page'] . "</th>
              <th>" . $GLOBALS['LANG']['menumaker']['header']['path'] . "</th>
              <th width=\"140px\">" . $GLOBALS['LANG']['menumaker']['header']['actions'] . "</th>
              <th width=\"250px\">" . $GLOBALS['LANG']['menumaker']['header']['modules'] . "</th>
            </tr>
            ";
            // Manuell ROOT
            print "
            <tr>
              <td><b>ROOT</b></td>
              <td>/</td>
              <td class=\"icons\">";
              print "<a>" . getIconFull('transp2') . "</a>";
              print "<a>" . getIconFull('transp2') . "</a>";
              print "<a>" . getIconFull('transp2') . "</a>";

              print "<a href=\"?action=module&amp;id=0\" title=\"Hantera moduler\">" . getIconFull('module',strClass:"geticons") . "</a>";
              /*print getIconFull('transp2');*/
              print "<a>" . getIconFull('transp2') . "</a>";
              $type = $input->get('action');
              if ( ($type == 'move' && $input->get('id') != 0) || $type == 'addnew' ) { //$value['id']
                  if($type == 'addnew') $type='add';
                  print "<a>" . getIconFull('transp2') . "</a>";
                  print "<a href=\"?action={$type}&id=" . $input->get('id') . "&into=0\" title=\"" . $GLOBALS['LANG']['GENERAL']['move_into'] . "\">" . getIconFull('move_into',strClass:'no_blur') . "</a>"; // " . $value['id'] . "
              } elseif ($type == 'move') {
                  print "<a>" . getIconFull('transp2') . "</a>";
                  print "<a>" . getIconFull('transp2') . "</a>";
              }
              print "<a>" . getIconFull('transp2') . "</a>";
              print "
              </td>
              <td class=\"menumaker__moduler\">";

              foreach ($this->arrModules as $svalue) {
                  if ($svalue['pid']==0) {
                    //print "<p>";
                      if(!$svalue['visible']) { print "<s>";}
                      //print $svalue['visible']? getIconFull('visible'): getIconFull('invisible');
                      print "&nbsp;" . $svalue['module']. " (id:" . $svalue['id'] . ")";
                      if(!$svalue['visible']) { print "</s>";}
                      // edit here?
                      print "<span class=\"icons float_right\">";
                      // https://www.hbook.se/settings/page?action=module&subaction=edit&mid=59&id=56
                      print "<a href=\"?action=module&subaction=edit&amp;mid=". $svalue['id'] . "&id=0\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFull('edit') . "</a>";
                      // ORDER
                      //print "<a href=\"?action=module&subaction=edit&amp;mid=". $key . "&id=" . $this->pageid . "\">" . getIconFull('edit') . "</a>";
                      //print "<a href=\"?action=module&subaction=move&mid=" . $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move'] . "\">" . getIconFull('move2') . "</a>";
                      // Måste fixa
                      print $svalue['visible'] ? "<a href=\"?action=module&subaction=hide&mid=" . $svalue['id'] . "&id=0\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['hide'] . "\">" . getIconFull('visible') . "</a>" : "<a href=\"?action=module&subaction=show&mid=" . $svalue['id'] . "&id=0\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['show'] . "\">" . getIconFull('invisible') . "</a>";
                      print "</span>";
                      //print "</p>";
                      print "<br>";

                  }
              }

              print "</td>
            </tr>";




            $this->generate_tree_list_edit($arrUnsorted,0,0,99);
            print "</table>";
            if ($strAction != "") {
              print "<a href=\"?action=nothing\" class=\"button button-delete smallbutton\" style=\"float: right;\" >" . $GLOBALS['LANG']['GENERAL']['cancel'] . "</a>\n";
            }
        }
    }

    /**
     * Sorting all PAGE orders in DB based on PID
     * @param int $pid Parental ID
     */
    private function sortOrderInDB($pid = '0') {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $db = db::getInstance();
        $intOrder = 0;
        $arrPages = $db->o_get_all('page','SELECT * FROM [table] WHERE pid='.$pid.' ORDER BY ordning');
        if (count($arrPages)>0) {
            foreach ($arrPages as $key => $value) {
                //print $value['id']."<br>";
                $intOrder++;
                $intOrder++;
                if (!$db->o_update('page',array('ordning' => $intOrder)," id=" . $value['id'])) {
                    setMsg('e', "PROBLEM VID SORTERING! med PID=".$pid);
                    return 0;
                }
                $this->sortOrderInDB($value['id']);
            }
        }
    }


    /**
     * Generates a list of all menus in order and with subs
     * @param type $array
     * @param type $parent
     * @param int $level
     * @param type $deep
     */
    private function generate_tree_list_edit($array, $parent = 0, $level = 0, $deep = 99)
    {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $input = new input();
        if (is_array($array))
        {
            $has_children = false;
            foreach($array as $key => $value)
            {
                if ($value['pid'] == $parent)
                {
                    if ($has_children === false)
                    {
                        $has_children = true;
                        $level++;
                    }
                    $href=("index.php?pages=" . $value['id']);
                    $padding=($level-1)*10;
                    $empty = str_replace("-", "&nbsp;", str_pad("", $level*4, "-", STR_PAD_LEFT));
                    print "<tr>";
                        print  "<td>" . $empty . $value['label'] . "</td>";
                        print  "<td>" . $value['src'] . "</td>";
                        if (count($this->arrLabels)>0) {
                            foreach ($this->arrLabels as $svalue) {
                                print  "<td>" . $value[$svalue] . "</td>";
                            }
                        }
                        if ($this->allowEdit) {
                            print  "<td class='icons'>";
                            print "<a href=\"?action=edit&id=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFull('edit') . "</a>";
                            print "<a href=\"?action=move&id=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move'] . "\">" . getIconFull('move2') . "</a>";
                            print $value['visible'] ? "<a href=\"?action=hide&id=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['hide'] . "\">" . getIconFull('visible') . "</a>" : "<a href=\"?action=show&id=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['show'] . "\">" . getIconFull('invisible') . "</a>";
                            print "<a href=\"?action=module&id=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['menumaker']['action_buttons']['modules'] . "\">" . getIconFull('module') . "</a>";
                            print "<a href=\"?action=delete&id=" . $value['id'] . "\" onclick=\"confirmDelete(" . $value['id'] . ");\" title=\"" . $GLOBALS['LANG']['GENERAL']['delete'] . "\">" . getIconFull('delete') . "</a>";
                            $type = $input->get('action');
                            if ( ($type == 'move' && $input->get('id') != $value['id']) || $type == 'addnew') {
                                if($type == 'addnew') $type = 'add';
                                print "<a href=\"?action={$type}&id=" . $input->get('id') . "&after=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move_after'] . "\">" . getIconFull('move_after',strClass:'no_blur') . "</a>";
                                print "<a href=\"?action={$type}&id=" . $input->get('id') . "&into=" . $value['id'] . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move_into'] . "\">" . getIconFull('move_into',strClass:'no_blur') . "</a>";
                            } elseif ($type == 'move') {
                              print "<a>" . getIconFull('transp2') . "</a>";
                              print "<a>" . getIconFull('transp2') . "</a>";
//                                print "<a href=\"?action=move&id=" . $input->get('id') . "&after=" . $value['id'] . "\">" . getIconFull('transp2') . "</a>";
//                                print "<a href=\"?action=move&id=" . $input->get('id') . "&into=" . $value['id'] . "\">" . getIconFull('transp2') . "</a>";
                            }
                            if ($this->SU) {
                              print "<a href=\"" . getURLbyID($value['id']) . "\" target=\"_blank\" title=\"" . $GLOBALS['LANG']['menumaker']['action_buttons']['open'] . "\">" . getIconFull('open') . "</a>";
                            }
                            //[Edit] [Move] [Visible] [Delete] ( [After] [Inside] )
                            print "</td>";
                            print "<td class=\"menumaker__moduler\">";
                            foreach ($this->arrModules as $svalue) {
                                if ($svalue['pid']==$value['id']) {
                                    if(!$svalue['visible']) { print "<s>";}
                                    //print $svalue['visible']? getIconFull('visible'): getIconFull('invisible');
                                    print "&nbsp;" . $svalue['module']. " (id:" . $svalue['id'] . ")";
                                    if(!$svalue['visible']) { print "</s>";}
                                    // edit here?
                                    print "<span class=\"icons float_right\">";
                                    // https://www.hbook.se/settings/page?action=module&subaction=edit&mid=59&id=56
                                    print "<a href=\"?action=module&subaction=edit&amp;mid=". $svalue['id'] . "&id=" . $value['id'] . "\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFull('edit') . "</a>";
                                    // ORDER
                                    //print "<a href=\"?action=module&subaction=edit&amp;mid=". $key . "&id=" . $this->pageid . "\">" . getIconFull('edit') . "</a>";
                                    //print "<a href=\"?action=module&subaction=move&mid=" . $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move'] . "\">" . getIconFull('move2') . "</a>";
                                    // Måste fixa
                                    print $svalue['visible'] ? "<a href=\"?action=module&subaction=hide&mid=" . $svalue['id'] . "&id=" . $value['id'] . "\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['hide'] . "\">" . getIconFull('visible') . "</a>" : "<a href=\"?action=module&subaction=show&mid=" . $svalue['id'] . "&id=" . $value['id'] . "\" class=\"float_right_xx\" title=\"" . $GLOBALS['LANG']['GENERAL']['show'] . "\">" . getIconFull('invisible') . "</a>";
                                    print "</span>";
                                    print "<br>";
                                }
                            }
                            print "</td>\n";
                        }
                    print "</tr>";
                    $this->generate_tree_list_edit($array, $key, $level, $deep);
                }
            }
        }
    }



    /*
     *
     * BELOW has with MODULES TO DO
     *
     */



    /**
     * Handle the Module part
     */
    private function initModules() {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        //print "INIT";
        $input = new input();
        //$intId = intval($input->get('id'));
        $this->arrPost = $input->postAll();
        if (($this->arrPost['db_page'] !== null) && is_numeric($this->arrPost['db_page'])) {
          $this->pageid = intval($this->arrPost['db_page']);
        } elseif (($input->get('id') !== null) && is_numeric($input->get('id'))) {
          $this->pageid = intval($input->get('id'));
        } else {
          $this->pageid = 0;
        }
//        print "page_id: ".$this->pageid;

        $this->dbFormSettings = array(
/*          'list'  => array(
              'order' => 'id,namn',
              'width' => '',
              'edit' => true,
              'buttons' => array (
                array('action' =>'action=settings','icon' => 'settings', 'label' => 'Set company specific settings')
              )
          ),*/
            'form'    =>  array (
                'palette' =>  'page,target,module',
            ),
            'fields'  =>  array(
                'id'    =>  array(
                    'type'    => 'text',
                    'field'    =>  'id',
                ),
//                'page'    =>  array(
//                    'type'    => 'select',
//                    'field'    =>  'page',
//                    'options' =>  $this->getPages(),
//                    'caption'   => array('Ange Sida',''),
//                    'value'   => $this->pageid
//                ),
                'target'  =>  array(
                    'type'    => 'select',
                    'field'    =>  'target',
                    'options' =>  array('main'=>'Main','navigation'=>'Navigation','header'=>'Header','footer'=>'Footer','left'=>'Left','right'=>'Right','custom'=>'Custom'),
                    'caption'   => array('Ange målzon',''),
                    'value'   => $this->arrPost['db_target'],
                ),
                'module' =>  array(
                    'type'    => 'select',
                    'field'    =>  'module',
                    'options' =>  $this->getModules(),
                    'caption'   => array('Ange module',''),
                    'value'   => $this->arrPost['db_module'],
                ),
                'pid' =>  array (
                    'type'    =>  'hidden',
                    'field'   =>  'pid',
                    'value'   =>  $input->get('pid'),
                    'caption' =>  array('',''),
                ),
                'table' =>  array(
                    'type'    => 'select',
                    'field'    =>  'table',
                    'options' =>  array(),
                    'caption'   => array('Ange tabell',''),
                    'value'   => $this->arrPost['db_table'],
                ),
                'template' =>  array(
                    'type'    => 'select',
                    'field'    =>  'template',
                    'options' =>  array(),
                    'caption'   => array('Ange htmlmall',''),
                    'value'   => $this->arrPost['db_template'],
                ),
                'palette' =>  array(
                    'type'    => 'text',
                    'field'    =>  'palette',
                    'caption'   => array('Ange palett',''),
                    'value'   => $this->arrPost['db_palette'],
                    'class'   => 'w300',
                ),
            ),
        );
        $arrModuleLibs = array_diff(scandir(SYSPATH."modules"), array('..', '.'));
        foreach ($arrModuleLibs as $modulelib) {
            $file =  SYSPATH . "modules/" . $modulelib . "/config/settings.php" ;
            if ( is_file($file) ) {
                require_once( $file );
            }
        }


        $db = db::getInstance();
        //print "HÄR";
        if (is_array($_GET)) {
          $arrGET = $input->getAll();
        }
        // ID of module here
        $intId = $arrGET['mid'];

        if ($input->get('subaction') == 'save' && $input->post('db_submit')!="" && is_numeric($input->post('db_pid'))) {
//            print "save";
            $arrPost = $input->postAll();
            unset($arrPost['db_submit']);
            foreach (array('pid','target','module') as $strField) {
                $arrSave[$strField] = $arrPost['db_'.$strField];
                unset($arrPost['db_'.$strField]);
            }
            // Table
            if (isset($arrPost['db_table'])) {
                $arrData['table'] = $arrPost['db_table'];
                unset($arrPost['db_table']);
            }
            // Palette
            if (isset($arrPost['db_palette'])) {
                $arrData['form']['palette'] = $arrPost['db_palette'];
                unset($arrPost['db_palette']);
            }
            if (is_numeric($arrPost['db_id'])) {
                //UPDATE
                $mid = $arrPost['db_id'];
                unset($arrPost['db_id']);
            } else {
                // NEW
                unset($arrPost['db_id']);
            }
            if (is_array($arrPost) && count($arrPost)>0) {
                foreach ($arrPost as $key => $value) {
                    $key = str_replace("db_", "", $key);
                    $arrData[$key] = $value;
                }
            }
            $arrSave['settings'] = serialize($arrData);

            // Spara
            if (isset($mid)) {
                if ($db->o_update('page_content',$arrSave,"id={$mid}")) {
                    setMsg('s',$GLOBALS['LANG']['GENERAL']['update']);
                }
            } else {
                if($db->o_insert('page_content',$arrSave)) {
                    setMsg('s',$GLOBALS['LANG']['GENERAL']['saved']);
                }
            }
            // OM sparat så välj automatiskt samma kategori igen
            $this->arrPost['db_page'] = $this->arrPost['db_pid'];
            // ???
            $this->pageid = $this->arrPost['db_pid'];
            $this->dbFormSettings['fields']['page']['value'] = $this->arrPost['db_pid'];
        } elseif ($input->get('subaction') == 'delete' && is_numeric($input->get('id'))) {
//            print "DELETE";
            if ($db->o_delete('page_content',intval($input->get('mid')))) {
              setMsg('s',$GLOBALS['LANG']['GENERAL']['saved']);
            }
        } elseif ($input->get('subaction') == 'move' &&
          is_numeric($input->get('id')) &&
          is_numeric($input->get('mid')) &&
          is_numeric($input->get('after'))
        ) {
          $intAfter = intval($input->get('after'));
          if ($intAfter>0) {
              $arrAfter = $db->o_get_row('page_content','SELECT * FROM [table] WHERE id='.$intAfter);
              // UPDATE
              $intOrder = $arrAfter['ordning'];
              $intOrder++;
              //$pid = $arrAfter['pid'];
/*              print "<pre>";
              print_r($arrAfter);
              print "NEW ORDER: ".$intOrder."\n";
              print "ID: " . $intId."\n";
              print "</pre>";*/
              if ($db->o_update('page_content',array('ordning' => $intOrder, )," id=" . $intId)) {
                  setMsg('s', "Modulen med ID: " . $intId . " har lagts till efter modulen med ID: " . $intAfter);
              }
          }
          // unset move
          unset($_GET['subaction']);
          unset($_GET['after']);

        } elseif (
          $input->get('subaction') == 'show' &&
            is_numeric($input->get('id')) &&
            is_numeric($input->get('mid'))
          ) {

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
print "HEY";*/
             if ($db->o_update('page_content',array('visible' => 1)," id=" . $intId)) {
                 setMsg('s', "Modulen med ID: " . $input->get('id') . " har gjorts synlig");
             }
             unset($_GET['subaction']);

         } elseif (
           $input->get('subaction') == 'hide' &&
           is_numeric($input->get('id')) &&
           is_numeric($input->get('mid'))
         ) {

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
print "HEY";*/
             if ($db->o_update('page_content',array('visible' => 0)," id=" . $intId)) {
                 setMsg('s', "Modulen med ID: " . $input->get('id') . " har dolts");
             }
             unset($_GET['subaction']);


        }



        print "<h1>Meny &gt; Modules </h1>";
        if ($input->get('subaction') == 'add' || $input->get('subaction') == 'edit') {
            $this->editModules();
        } else {
            $this->sortOrderModulesInDB();
            $this->listModules();
        }

    }

    /**
     * Lists Modules to work with on specific page
     */
    private function listModules() {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $input = new input();
        //$moduleID = $input->get('id');
        $db = db::getInstance();
        if (intval($this->pageid)>=0) {
            // Lista moduler
            $ret = $db->o_get_all('page_content',"SELECT * FROM [table] WHERE pid=".$this->pageid . " AND deleted=0 ORDER BY ordning");
/*            print "<pre>";
            print_r($ret);
            print "</pre>";*/
            print "<a href=\"?action=module&subaction=add&id=" . $this->pageid . "\" class=\"button button-primary\">Lägg till ny</a><br><br>";
            print "<table class=\"moduler\">\n";
            print "<thead>\n";
            print "<tr>\n";
            print "<th>" . $GLOBALS['LANG']['menumaker']['module']['modul'] . "</th>\n";
            print "<th>" . $GLOBALS['LANG']['menumaker']['module']['target'] . "</th>\n";
            print "<th>" . $GLOBALS['LANG']['menumaker']['module']['actions'] . "</th>\n";
            print "</tr>\n";
            print "</thead>\n";
            print "<tbody>\n";
            if (count($ret)>0 && is_array($ret)) {
                foreach ($ret as $key => $value) {
                    print "<tr id=\"". $key . "\">\n";
                    print "<td>" . $value['module'] . "</td>\n";
                    print "<td>" . $value['target'] . "</td>\n";
                    print "<td class='icons'>";
                    // EDIT
                    print "<a href=\"?action=module&subaction=edit&amp;mid=". $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFull('edit') . "</a>";
                    // ORDER
                    //print "<a href=\"?action=module&subaction=edit&amp;mid=". $key . "&id=" . $this->pageid . "\">" . getIconFull('edit') . "</a>";
                    print "<a href=\"?action=module&subaction=move&mid=" . $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move'] . "\">" . getIconFull('move2') . "</a>";
                    // Måste fixa
                    print $value['visible'] ? "<a href=\"?action=module&subaction=hide&mid=" . $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['hide'] . "\">" . getIconFull('visible') . "</a>" : "<a href=\"?action=module&subaction=show&mid=" . $key . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['show'] . "\">" . getIconFull('invisible') . "</a>";
                    // DELETE
                    print "<a href=\"?action=module&subaction=delete&amp;mid=". $key . "&id=" . $this->pageid . "\" onclick=\"return confirmDelete(" . $key . ");\" title=\"" . $GLOBALS['LANG']['GENERAL']['delete'] . "\">" . getIconFull('delete') . "</a>\n";

                    if ($input->get('subaction') == 'move' && $input->get('mid') != $value['id']) {
                        print "<a href=\"?action=module&subaction=move&mid=" . $input->get('mid') . "&after=" . $value['id'] . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['move_after'] . "\">" . getIconFull('move_after') . "</a>";
                        //print "<a href=\"?action=module&subaction=move&id=" . $input->get('mid') . "&into=" . $value['id'] . "\">" . getIconFull('move_into') . "</a>";
                    } elseif ($input->get('subaction') == 'move') {
                        print "<a>" . getIconFull('transp2') . "</a>";
                        //print "<a href=\"?action=module&subaction=move&mid=" . $input->get('mid') . "&after=" . $value['id'] . "&id=" . $this->pageid . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\">" . getIconFull('transp2') . "</a>";
                        //print "<a href=\"?action=module&subaction=move&id=" . $input->get('mid') . "&into=" . $value['id'] . "\">" . getIconFull('transp2') . "</a>";
                    }
                    print "</td>\n";

                    print "</tr>\n";
                }
            }
            print "</tbody>\n";
            print "</table>\n";
            if ($input->get('subaction') == 'move') {
              print "<a href=\"?action=module&id=" . $this->pageid . "\" class=\"button button-delete smallbutton\" style=\"float:right;\">Avbryt</a>";
            }
            print "<a href=\"?action=nothing\" class=\"button button-primary button_red\"> &lt;&lt; Tillbaka</a><br><br>";

        }
    }

    /**
     * Edit/Add the modules
     */
    private function editModules() {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $form = new form();
        $db = db::getInstance();
        $input = new input();

        if ($input->get('subaction')=='edit' && is_numeric($input->get('mid'))) {
//            print "EDIT";
            $arrPost = $input->postAll();
            $mid = intval($input->get('mid'));
            // hämta värdet!
            $arrData = $db->o_get_row('page_content','SELECT * FROM [table] WHERE id='.$mid);
            // Lägg in värdet i alla fälts value
//            print "<pre>";
//            print_r($arrData);
//            print "</pre>";

            // Om bytt
            if ($arrData['module'] != $arrPost['db_module'] && $arrPost['db_module']!="") {
                $arrData['module'] = $arrPost['db_module'];
                $arrData['settings'] = '';
            }


//
            foreach (array('id','pid','target','module') as $fields) {
                $this->dbFormSettings['fields'][$fields]['value'] = $arrData[$fields];
                $this->arrPost['db_'.$fields] = $arrData[$fields];
            }
            //Temporär

            $module = $this->arrPost['db_module'];
            // Settings
            if ($arrData['settings']!="") {
                $arrTmp = unserialize($arrData['settings']);
                if (is_array($arrTmp)) {
                    //foreach
                }
                // table,template
                foreach (array('table','template') as $value) {
                    if (isset($arrTmp[$value])) {
                        $this->dbFormSettings['fields'][$value]['value'] = $arrTmp[$value];
                        unset($arrTmp[$value]);
                    }
                }
                // Form
                if (isset($arrTmp['form']['palette'])) {
                    $this->dbFormSettings['fields']['palette']['value'] = $arrTmp['form']['palette'];
                    unset($arrTmp['form']['palette']);
                }
                // List
                if (is_array($arrTmp['list'])) {
                    foreach ($arrTmp['list'] as $key => $value) {
                        $GLOBALS['MODULE'][$module]['fields'][$key]['value'] = $value;
                    }
                    unset($arrTmp['list']);
                }



            }

        } else {
            $this->dbFormSettings['fields']['pid']['value'] = $input->get('id');
        }


        // Hämta om ny modul här
        $module = $this->arrPost['db_module'];
        $_GET['subaction'] = "save";

        print $form->form_begin('dbform',array('arrQuery' => $_GET));
        print $form->field_SELECT($this->dbFormSettings['fields']['module']);
        print $form->field_SELECT($this->dbFormSettings['fields']['target']);
        print $form->field_HIDDEN($this->dbFormSettings['fields']['pid']);
        print $form->field_HIDDEN($this->dbFormSettings['fields']['id']);

        // Ladda de anpassade här!
        if (@is_array($GLOBALS['MODULE'][$module])) {
            // Table
            if ($GLOBALS['MODULE'][$module]['table']) {
                $arrTemp = $db->o_list_tables();
                $arrTables['-'] = "-- välj tabell --";

                // TODO: Fixa till DB
                $arrdbPrefixes = getdbPrefixes();
/*                print "<pre>";
                print_r($arrdbPrefixes) ;
                print "</pre>";*/

                foreach ($arrTemp as $tables) {
                  // BUGFIX: 20210325 - Got error when trying to use table that
                  // isn't hbook
                  /**  @disregard P1006 Undefined type */
                  $tables = str_replace($arrdbPrefixes, "", $tables);
//                  $tables = str_replace($GLOBALS['CONFIG']['dbPrefix'], "", $tables);
                  if (!isset($arrTables[$tables])) {
                    $arrTables[$tables] = $tables;
                  }
                }
                // Sorting tables for nicer looking
                asort($arrTables);
                $this->dbFormSettings['fields']['table']['options'] = $arrTables;
                print $form->field_SELECT($this->dbFormSettings['fields']['table']);
            }
            // template
            if ($GLOBALS['MODULE'][$module]['template']) {
                $tpl_path = SYSPATH."modules/" . $module . "/tpl/";
                $arrTemplates['-'] = "-- standard --";
                foreach (glob($tpl_path . "*.tpl") as $filename) {
                    $newfilename = str_replace($tpl_path,"",$filename);
                    $newfilename = str_replace(".tpl","",$newfilename);
                    $arrTemplates[$filename] = $newfilename;
                }
                // Prefix set!
                if (isset($GLOBALS['MODULE'][$module]['template_prefix'])) {
                  // Check if template dir exist in tpl!
                  $tpl_p_path = TPLPATH . $GLOBALS['CONFIG']['template']."/templates/";
                  if (is_dir($tpl_p_path)) {
                    foreach (glob($tpl_p_path . "*.tpl") as $filename) {
                      $arrTemplates[$filename] = str_replace($tpl_p_path,"",$filename);
                    }
                  }
                }



                $this->dbFormSettings['fields']['template']['options'] = $arrTemplates;
                print $form->field_SELECT($this->dbFormSettings['fields']['template']);
            }
            // Form palette
            if ($GLOBALS['MODULE'][$module]['form']['palette']) {
                print $form->field_INPUT($this->dbFormSettings['fields']['palette']);
                // Lägg in ajax om tabell..
                //print "\n<script>\n";
                // Hämtar hem tabell fälten för aktuell tabell
                /*print "\t$( \"#db_table\" ).change(function() {\n";
                print "\t\tvar table = $( \"#db_table\" ).val();\n";
                print "\t\tvar order = '&action=GetPalette&palette=' + table + '&checksum=" . salt('moduler.php'.date('Y-m-d'),10) . "';\n";
                // Byta till ajax hook?
                print "\t\t$.post(\"". $GLOBALS['CONFIG']['www-root'] . "/updateDB.php\", order, function(theResponse){\n";
                print "\t\t\t$(\"#allowed_fields\").html(theResponse);";
                print "\t\t});\n";
                print "\t});\n";
                // Fixar paletten
                print "$('body').on('click','.addtopalette',function(){
                    var word = $(this).text();
                    var textField = $('#db_palette');
                    if (textField.val()=='') {
                        textField.val(textField.val()+word);
                    } else {
                        textField.val(textField.val()+','+word);
                    }
                });";*/
                //print "</>\n";
                //print "<div id=\"allowed_palette_fields\"></div>";
            }

            // Bygg resten här!
            if (@is_array($GLOBALS['MODULE'][$module]['fields']) && count($GLOBALS['MODULE'][$module]['fields'])>0) {
                // Check fields here
                foreach ($GLOBALS['MODULE'][$module]['fields'] as $strField => $strValue) {
                    if (isset($arrTmp[$strField])) {
                        $GLOBALS['MODULE'][$module]['fields'][$strField]['value'] = $arrTmp[$strField];
                    }
                }
                if (isset($GLOBALS['MODULE'][$module]['fields']['order'])) {
                  if (isset($GLOBALS['MODULE'][$module]['fields']['order']['class'])) {
                    $GLOBALS['MODULE'][$module]['fields']['order']['class'].= " mm_db_list_order";
                  } else {
                    $GLOBALS['MODULE'][$module]['fields']['order']['class'] = "mm_db_list_order";
                  }
                }

                $form->MakeFields($GLOBALS['MODULE'][$module]);
            }
        }



        print "<div class=\"w100pct\">".$form->form_submit('Spara')."</div>";
        print "<a href=\"?action=module&id=" . $input->get('id') . "\" class=\"button button-primary button_red\"> &lt;&lt; Tillbaka</a><br><br>";
        print $form->form_end();
        print "<script>\n";
        print "$( \"#db_module\" ).change(function() {\n";
        print "var url = window.location.href; ";
        print "$( \"form\" ).attr('action', url);";
        print "$( \"#db_submit\" ).click();\n";
        print "});\n";
        print "</script>\n";

    }

    /**
     * Get all avalible modules
     * @return array Return all avalible modules
     */
    private function getModules() {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $arrModuleLibs = array_diff(scandir(SYSPATH."modules"), array('..', '.'));
        $arrReturn = array();
        foreach ($arrModuleLibs as $modulelib) {
            if (substr($modulelib, 0, 1) =="_") {continue;}
            $arrReturn[$modulelib] = $modulelib;
        }
        return (array) $arrReturn;
    }


    /**
     * Sorting all PAGE orders in DB based on PID
     * @param int $pid Parental ID
     */
    private function sortOrderModulesInDB() {
        if ($this->d) { $GLOBALS['DEBUG'][] = __CLASS__."::".__FUNCTION__; }
        $db = db::getInstance();
        $intOrder = 0;
        $arrPages = $db->o_get_all('page_content','SELECT * FROM [table] ORDER BY pid,ordning');
        if (count($arrPages)>0) {
            $oldpid = -1;
            foreach ($arrPages as $key => $value) {
                $pid = $value['pid'];
                //print $value['id']."<br>";
                if ($oldpid != $pid) {
                    $intOrder = 0;
                }

                $intOrder++;
                $intOrder++;

//                print " ID: " . $value['id'] . ",  PID: " . $pid . ", ORDER: {$intOrder} <br>";

                if (!$db->o_update('page_content',array('ordning' => $intOrder)," id=" . $value['id'])) {
                    setMsg('e', "PROBLEM VID SORTERING! med PID=".$pid);
                    return 0;
                }
                $oldpid = $pid;
                //$this->sortOrderModulesInDB($value['id']);
            }
        }
    }


}
