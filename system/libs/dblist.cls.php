<?php

/**
Name: dblist
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Creates a table listing from db table. Added support for ForeignKey. Combined from 3 versions to 1
Version: 1.1.2 (synced)
Type: Module
 */

/**
 * Skapar lista från databas
 *
 * PDF not implemented ??
 * 
 * @author Martin Tonek <martin@tonek.se>
 */

class dblist
{

    public $data; //private
    private $unique;
    private $allowSearch = false;
    private $dbFormSettings = [];
    private $allowEdit = false;

    /**
     * Render one row in list table.
     *
     * @param int|string $key
     * @param array $value
     * @param array $arrOrder
     * @param array $dbFormSettings
     * @param bool $bolSortable
     * @param array $arrForeignKey
     * @return string
     */
    private function renderDataRow($key, $value, $arrOrder, $dbFormSettings, $bolSortable, &$arrForeignKey)
    {
        $output = "\t\t<tr id=\"" . $key . "\" data-id=\"" . $key . "\">\n";
        $output .= ($bolSortable) ? "\t\t\t<td><span class=\"handle\">" . getIconFullSVG('sort', 12, 12) . "</span></td>\n" : "";
        foreach ($arrOrder as $field) {
            $output .= "\t\t\t<td";
            if (isset($dbFormSettings['fields'][$field]['class_list']) && $dbFormSettings['fields'][$field]['class_list'] !== "") {
                $output .= " class=\"" . $dbFormSettings['fields'][$field]['class_list'] . "\"";
            }
            $output .= ">\n";

            if (isset($dbFormSettings['fields'][$field]['list_callback']) && is_array($dbFormSettings['fields'][$field]['list_callback'])) {
                $callback = $dbFormSettings['fields'][$field]['list_callback'];
                $class = new $callback[0]();
                $output .= call_user_func(array($class, $callback[1]), $value);
            } elseif (isset($dbFormSettings['fields'][$field]['list_foreignKey'])) {
                if (!isset($arrForeignKey[$field])) {
                    list($strTable, $strField) = explode(".", $dbFormSettings['fields'][$field]['list_foreignKey']);
                    $db = db::getInstance();
                    $tmp = $db->o_get_all($strTable, "SELECT id, " . $strField . " FROM [table] WHERE deleted=0 AND ( companyid=" . getCompanyID() . " OR companyid=0 )");
                    foreach ($tmp as $fID => $fVALUE) {
                        $arrForeignKey[$field][$fID] = $fVALUE[$strField];
                    }
                }
                $x = $value[$field];
                $output .= $arrForeignKey[$field][$x];
            } else {
                $output .= "<!--$field--> ";
                $output .= $value[$field];
            }
            $output .= "\t\t\t</td>\n";
        }
        if (isset($dbFormSettings['list']['edit']) && $dbFormSettings['list']['edit'] != false && $this->allowEdit) {
            $output .= "\t\t\t<td width=\"32px;\" style=\"white-space: nowrap;\">";
            if (isset($dbFormSettings['list']['buttons']) && is_array($dbFormSettings['list']['buttons'])) {
                $arrToolBut = array();
                foreach ($dbFormSettings['list']['buttons'] as $arrButtons) {
                    $arrToolBut[$arrButtons['action']] = $arrButtons;
                    if (!isSuperUser()) {
                        $output .= "<a class=\"dblist_buttons\" href=\"?" . $arrButtons['action'] . "&id=" . $key . "\" title=\"" . $arrButtons['label'] . "\"><img src=\"" . getIcon($arrButtons['icon']) . "\" title=\"" . $arrButtons['label'] . "\"></a>";
                    }
                }
            }
            if (isSuperUser()) {
                if (!isset($arrToolBut)) $arrToolBut = array();
                $output .= toolbox($key, $arrToolBut);
            } else {
                $output .= "<a class=\"dblist_buttons\" href=\"?action=edit&id=" . $key . "\"><img src=\"" . getIcon('edit') . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['edit'] . "\"></a>";
                $output .= "<a class=\"dblist_buttons\" href=\"?action=delete&id=" . $key . "\" onClick=\"return confirmDelete(" . $key . ");\"><img src=\"" . getIcon('delete') . "\" title=\"" . $GLOBALS['LANG']['GENERAL']['delete'] . "\"></a>";
            }
            $output .= "</td>\n";
        }
        $output .= "\t\t</tr>\n";
        return $output;
    }

    function setData($data)
    {
        $GLOBALS['DEBUG'][] = "LIB " . __CLASS__ . " : " . __FUNCTION__;
        $GLOBALS['DEBUG'][] = $data;
        $this->data = $data;
    }

    function MakeWithSettings($dbFormSettings, $return = false)
    {
        if ($this->unique == "") {
            $this->unique = bin2hex(random_bytes(10));
        }
        if (isset($dbFormSettings['own_access']['edit'])) {
            $this->allowEdit = $dbFormSettings['own_access']['edit'] ? $dbFormSettings['own_access']['edit'] : 0;
        }
        // pre($dbFormSettings);
        $GLOBALS['DEBUG'][] = "LIB " . __CLASS__ . " : " . __FUNCTION__;
        $GLOBALS['DEBUG'][] = $dbFormSettings;
        // Vad göra om man inte har en tabell men data redan är satt?
        $table = $dbFormSettings['table'];
        // Kollar om man får söka i tabellen
        if (isset($dbFormSettings['list']['allow_search'])) {
            $this->allowSearch = $dbFormSettings['list']['allow_search'] ? $dbFormSettings['list']['allow_search'] : "0";
        }
        // ACTIONS from dca!! NEED TO DOCUMENT THIS!!!
        if (isset($dbFormSettings['list']['buttons']) && is_array($dbFormSettings['list']['buttons']) && input::_get('action') <> "") {
            foreach ($dbFormSettings['list']['buttons'] as $key => $value) {
                if (isset($value['action']) && input::_get('action') == $value['action'] && isset($value['function']) &&  is_array($value['function'])) {
                    // pre($value);
                    $action_callback = $value['function'];
                    $action_class = new $action_callback[0]();
                    call_user_func(array($action_class, $action_callback[1]));
                }
            }
        }
        // Order om man inte har satt det? och data redan är satt?
        $arrOrder = explode(',', $dbFormSettings['list']['order']);
        // pre($dbFormSettings['list']['order']);
        // 2023-12-07 BUGFIX? Den hittar inte någon palette om man ändrat i sidhanteraren och den är tom
        if (is_array($arrOrder) && count($arrOrder) == 1 && $arrOrder[0] == "" && isset($GLOBALS['TABLE'][$table]['list']['order'])) {
            $arrOrder = explode(',', $GLOBALS['TABLE'][$table]['list']['order']);
            //print "HEY";
        }
        $this->dbFormSettings = $dbFormSettings;
        // Order om man inte har satt det? och data redan är satt?
        // $arrOrder = explode(',', $dbFormSettings['list']['order']);
        $output = "";
        $output .= "<div class=\"dblist\" >\n";
        $output .= "<div id=\"dbtable\" style=\"display: none;\">{$table}</div>\n";

        if (is_array($this->data)) {
            $arrAnswer = $this->data;
        } else {
            // Flyttade hit db satsen från rad 51 då den inte används om inte
            // denna funktion anropas. (Ovan koden "if (is_array($this->data)) {")
            $db = db::getInstance();

            // Flyttat hit från innan IF sats
            $order = " ORDER BY ";
            // VARFÖR GÖR JAG SÅ HÄR????? JAG HAR JU SORTERING ENLIGT 'ORDER' OCH INTE 'SORTORDER'!!!!
            // ÄR INTE SAMMA SAK!!            
            if (isset($dbFormSettings['list']['sortorder'])) {
                // pre($dbFormSettings['list']['sortorder']);
                $order .= $dbFormSettings['list']['sortorder'];
                if (substr($order, -1, 1) != ",") {
                    $GLOBALS['DEBUG'][__CLASS__][__FUNCTION__] = "KOMMA TILLAGT!! på *SORTORDER*";
                    $order .= ", ";
                }
            }

            // 20260306 - Behov av ny sökfunktion mot databasen. Tar emot en hel sträng
            if (isset($dbFormSettings['list']['sortorder_string'])) {
                // pre($dbFormSettings['list']['sortorder_string']);
                $order .= $dbFormSettings['list']['sortorder_string'];
                // if (substr($order, -1, 1) != ",") {
                    $GLOBALS['DEBUG'][__CLASS__][__FUNCTION__] = "KOMMA TILLAGT!! på *SORTORDER_string*";
                    $order .= ", ";
                // }
            }

            $order .= "ordning";


            // BUG: Måste fixa så att WHERE finns med i settingsfilen på ngt sätt
            if (isset($dbFormSettings['list']['where']) && $dbFormSettings['list']['where'] != "") {
                $where = " AND " . $dbFormSettings['list']['where'];
            } else {
                $where = "";
            }
            //BUG 2015-03-07 - Blir fel vid företagsval
            // 2017-03-27 - Lagt till user som tabell //&& $table!='user'
            if ($table != 'company') {
                $where .= " AND companyid=" . getCompanyID() . " ";
            }
            // Gör SQL anrop här
            $sql = "SELECT * FROM [table] WHERE deleted=0 " . $where . $order;
            $arrAnswer = $db->o_get_all($table, $sql); //ORDER BY ordning
            //active=1 AND
        }


        if (!is_array($arrAnswer) || count($arrAnswer) == 0) {
            //            $alert = new alertbox();
            //            $alert->showBox("notice", "Inget funnet");
            setMsg('n', "Inget funnet!");
            return;
        }

        $table_class = "";
        // 20240109 UPDATE bytte från table-3 till sortable
        $bolSortable = false;
        $groupingEnabled = isset($dbFormSettings['list']['group_by']) && is_array($dbFormSettings['list']['group_by']) && isset($dbFormSettings['list']['group_by']['field']) && $dbFormSettings['list']['group_by']['field'] != "";
        if (isset($dbFormSettings['allowAjax']) && $dbFormSettings['allowAjax'] && $this->allowEdit) {
            $bolSortable = !$groupingEnabled;
            if ($bolSortable) {
                //$table_class.=" table-3";
                $table_class .= "sortable";
                $this->sortable();
            }
        } else {
            if (isset($dbFormSettings['allowAjax']) && $dbFormSettings['allowAjax'] && $this->allowEdit) {
                $table_class .= " table-3";
            }
        }
        // Add searchbox if allowed
        if ($this->allowSearch) $this->searchbox();


        $output .= "<table class='{$table_class} u-full-width dblist_{$this->unique}' id='dblist'>\n";

        $output .= "\t<thead>\n";
        $output .= "\t\t<tr>\n";
        if (!is_array($arrOrder)) {
            return "ERROR IN TABLE DATA FILE";
        }
        // Test of draggable
        $output .= ($bolSortable) ? "\t\t\t<th width=\"8px\"></th>\n" : "";
        foreach ($arrOrder as $value) {
            if ($value == "") continue;
            $value = trim($value);
            $output .= "\t\t\t<th";
            if (isset($dbFormSettings['fields'][$value]['class_list'])) {
                $output .= " class=\"" . $dbFormSettings['fields'][$value]['class_list'] . "\"";
            }
            $output .= ">";
            if (!isset($dbFormSettings['fields'][$value]['caption'][0]) && isSuperUser()) {
                print "<pre class='warning'>";
                print __CLASS__ . " :: " . __FUNCTION__ . " @ #" . __LINE__ . "&gt;<br />ERROR! Missing CAPTION for \"$value\"! Temporary value? \n\n";
                // var_dump($value);
                // $tmpA = 
                // "'{$value}' => Array (\n" .
                // "    'type' => '',\n" .
                // "        'caption' => Array (\n" .
                // "            '0' => '',\n" .
                // "            '1' => ''\n" .
                // "        )\n" . 
                // "    )\n" .
                // "),";
                // print($tmpA);

            // [name] => Array
            //     (
            //         [type] => varchar
            //         [caption] => Array
            //             (
            //                 [0] => Namn
            //                 [1] => Här anger du ett namn för fältet
            //             )

            //     )


                print "</pre>";
            }
            $output .= $dbFormSettings['fields'][$value]['caption'][0] . "</th>\n";
        }

        // EDIT
        if (isset($dbFormSettings['list']['edit']) && $dbFormSettings['list']['edit'] != false && $this->allowEdit) {
            $output .= "\t\t\t<th> </th>\n";
        }


        $output .= "\t\t</tr>\n";
        $output .= "\t</thead>\n";
        $output .= "\t<tbody>\n";
        // Om det finns något
        if (is_array($arrAnswer)) {
            $arrForeignKey = array();
            if ($groupingEnabled) {
                $groupField = $dbFormSettings['list']['group_by']['field'];
                $groupLabel = isset($dbFormSettings['list']['group_by']['label']) ? $dbFormSettings['list']['group_by']['label'] : $groupField;
                $priorityValue = isset($dbFormSettings['list']['group_by']['priority_value']) ? (string) $dbFormSettings['list']['group_by']['priority_value'] : "";
                $emptyLabel = isset($dbFormSettings['list']['group_by']['empty_label']) ? $dbFormSettings['list']['group_by']['empty_label'] : "Övrigt";
                $showCount = isset($dbFormSettings['list']['group_by']['show_count']) ? (bool) $dbFormSettings['list']['group_by']['show_count'] : true;
                $stickyConfig = isset($dbFormSettings['list']['group_by']['sticky_group']) && is_array($dbFormSettings['list']['group_by']['sticky_group']) ? $dbFormSettings['list']['group_by']['sticky_group'] : array();
                $stickyField = isset($stickyConfig['field']) ? $stickyConfig['field'] : "";
                $stickyValue = isset($stickyConfig['value']) ? (string) $stickyConfig['value'] : "";
                $stickyLabel = isset($stickyConfig['label']) ? $stickyConfig['label'] : "Öppna";
                $stickyRows = array();
                $groupedRows = array();

                foreach ($arrAnswer as $key => $value) {
                    if ($stickyField !== "" && isset($value[$stickyField]) && (string) $value[$stickyField] === $stickyValue) {
                        $stickyRows[$key] = $value;
                        continue;
                    }
                    $groupValue = isset($value[$groupField]) ? trim((string) $value[$groupField]) : "";
                    if ($groupValue === "") {
                        $groupValue = "__empty__";
                    }
                    if (!isset($groupedRows[$groupValue])) {
                        $groupedRows[$groupValue] = array();
                    }
                    $groupedRows[$groupValue][$key] = $value;
                }

                // Behåll SQL-sorteringen mellan grupper (första förekomst styr gruppordning).
                // Om man vill tvinga priority först kan force_priority_first=true användas.
                if (
                    isset($dbFormSettings['list']['group_by']['force_priority_first'])
                    && $dbFormSettings['list']['group_by']['force_priority_first']
                    && $priorityValue !== ""
                    && isset($groupedRows[$priorityValue])
                ) {
                    $tmpGroupedRows = array($priorityValue => $groupedRows[$priorityValue]);
                    unset($groupedRows[$priorityValue]);
                    $groupedRows = $tmpGroupedRows + $groupedRows;
                }

                // Lägg sticky-grupp överst (t.ex. obekräftade) men behåll intern SQL-sortering.
                if (count($stickyRows) > 0) {
                    $groupedRows = array("__sticky__" => $stickyRows) + $groupedRows;
                }

                $colspan = count($arrOrder) + (($bolSortable) ? 1 : 0) + ((isset($dbFormSettings['list']['edit']) && $dbFormSettings['list']['edit'] != false && $this->allowEdit) ? 1 : 0);
                foreach ($groupedRows as $groupValue => $groupRows) {
                    $safeKey = preg_replace('/[^a-zA-Z0-9\-_]/', '_', (string) $groupValue);
                    $isPriority = (($priorityValue !== "" && (string) $groupValue === $priorityValue) || $groupValue === "__sticky__");
                    if ($groupValue === "__empty__") {
                        $currentLabel = $emptyLabel;
                    } elseif ($groupValue === "__sticky__") {
                        $currentLabel = $stickyLabel;
                    } else {
                        $currentLabel = $groupValue;
                    }
                    $countText = $showCount ? " (" . count($groupRows) . ")" : "";
                    $openAttribute = $isPriority ? " open" : "";

                    $output .= "\t\t<tr class=\"dblist-group-header\">\n";
                    $output .= "\t\t\t<td colspan=\"" . $colspan . "\">\n";
                    $output .= "\t\t\t\t<details class=\"dblist-group\" data-dblist-group=\"" . $safeKey . "\"" . $openAttribute . ">\n";
                    $output .= "\t\t\t\t\t<summary>" . $groupLabel . ": " . $currentLabel . $countText . "</summary>\n";
                    $output .= "\t\t\t\t</details>\n";
                    $output .= "\t\t\t</td>\n";
                    $output .= "\t\t</tr>\n";

                    foreach ($groupRows as $key => $value) {
                        $row = $this->renderDataRow($key, $value, $arrOrder, $dbFormSettings, $bolSortable, $arrForeignKey);
                        $row = str_replace("<tr ", "<tr class=\"dblist-group-row\" data-dblist-group=\"" . $safeKey . "\" ", $row);
                        $output .= $row;
                    }
                }
            } else {
                foreach ($arrAnswer as $key => $value) {
                    $output .= $this->renderDataRow($key, $value, $arrOrder, $dbFormSettings, $bolSortable, $arrForeignKey);
                }
            }
        }
        $output .= "\t</tbody>\n";

        $output .= "</table>\n";
        $output .= "</div>";
        if ($groupingEnabled) {
            $output .= "<script>
            (function() {
                var listTables = document.querySelectorAll('.dblist table');
                listTables.forEach(function(tbl) {
                    var detailsList = tbl.querySelectorAll('details.dblist-group[data-dblist-group]');
                    var toggleGroupRows = function(groupKey, isOpen) {
                        var rows = tbl.querySelectorAll('tr.dblist-group-row[data-dblist-group=\"' + groupKey + '\"]');
                        rows.forEach(function(row) {
                            row.style.display = isOpen ? '' : 'none';
                        });
                    };
                    detailsList.forEach(function(groupDetails) {
                        var groupKey = groupDetails.getAttribute('data-dblist-group');
                        toggleGroupRows(groupKey, groupDetails.open);
                        groupDetails.addEventListener('toggle', function() {
                            toggleGroupRows(groupKey, groupDetails.open);
                        });
                    });
                });
            })();
            </script>";
        }

        // Toolbox scripts
        if ($this->allowEdit) {
            $this->toolbox();
        }

        // Skapa PDF här
        if (isset($this->dbFormSettings['list']['pdf']) && 1 == 2 & isset($this->dbFormSettings['list']['pdf'])) {
            $output .= $this->createPDF($arrAnswer);
        }
        // And let it out!!
        if ($return == true) {
            return $output;
        } else {
            print $output;
        }
    }

    /**
     * Creates a pdf from the list
     *
     * @param array $arrAnswer the datalist to print
     * @return string output
     */
    function createPDF($arrAnswer)
    {
        if (isset($this->dbFormSettings['list']['pdf']) && 1 == 2) {
            if (isset($this->dbFormSettings['list']['pdf']['filename'])) {
                $keyfieldResult = "";
                if (isset($this->dbFormSettings['list']['pdf']['keyfield'])) {
                    $arrTmp = reset($arrAnswer);
                    $keyfieldResult = $arrTmp[$this->dbFormSettings['list']['pdf']['keyfield']];
                    $keyfieldResult = str_replace(" ", "_", $keyfieldResult);
                }
                $strFilename = sprintf($this->dbFormSettings['list']['pdf']['filename'], $keyfieldResult, date("Y_m_d"));
                $filename = ROOTPATH . "pdf/" . $strFilename . ".pdf";
            } else {
                $strFilename = $this->dbFormSettings['table'] . date("Y_m_d_H_i_s");
                $filename = ROOTPATH . "pdf/" . $strFilename . ".pdf";
            }
            include_once(ROOTPATH . "resources/plugins/mpdf2/mpdf.php");
            $format = 'A4';
            if (isset($this->dbFormSettings['list']['pdf']['format'])) {
                $format = $this->dbFormSettings['list']['pdf']['format'];
            }
            //$this->dbFormSettings
            /**  @disregard P1009 Undefined type */
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => $format]);
            $mpdf->SetDisplayMode('fullpage');
            // $mpdf = new mPDF('c', 'A4', '', '', 32, 25, 27, 25, 16, 13);
            // $mpdf->SetDisplayMode('fullpage');
            $header = "
          <html>
          <head>
          <style>
          @page {
            size: auto;
            margin:5px;
            margin-header: 15mm;
            margin-footer: 10mm;
          }
          table tr {
            border:1px solid black;
          }
          table td {
            border-bottom:1px solid black;
            text-align:center;
          }
          </style>
          </head>
          <body>
          <h1>" . $this->dbFormSettings['list']['pdf']['title'] . "</h1>
          ";
            $footer = "</body></html>";
            $mpdf->WriteHTML($header . $output . $footer);
            @unlink($filename);
            $mpdf->Output($filename, "F"); //I
            $output .= "\n<a href=\"" . URLBASE . "/pdf/{$strFilename}.pdf\" class=\"pdf link\"  target=\"_blank\">Hämta pdf</a>";
        }
    }

    /**
     * Add searchbox avalible
     *
     * @return void
     */
    function searchbox()
    {
        print "<div></div><input type=\"text\" id=\"myInput_{$this->unique}\" placeholder=\"" . $GLOBALS['LANG']['GENERAL']['search'] . "\">\n"; // onkeyup=\"tableSearch_{$this->unique}();\"
        //dblist_{$this->unique}
        print "
        <script type=\"application/javascript\">
        myInput_{$this->unique}  = document.getElementById(\"myInput_{$this->unique}\");
        myInput_{$this->unique}.addEventListener(\"keyup\",function(){
            var keyword = this.value;
            keyword = keyword.toUpperCase();
            var table = document.getElementsByClassName(\"dblist_{$this->unique}\")[0];
            // console.log(table);
            var all_tr = table.getElementsByTagName(\"tr\");
            for(var i=0; i<all_tr.length; i++){
                    var all_columns = all_tr[i].getElementsByTagName(\"td\");
                    for(j=0;j<all_columns.length; j++){
                        if(all_columns[j]){
                            var column_value = all_columns[j].textContent || all_columns[j].innerText;
                            column_value = column_value.toUpperCase();
                            if(column_value.indexOf(keyword) > -1){
                                all_tr[i].style.display = \"\"; // show
                                break;
                            }else{
                                all_tr[i].style.display = \"none\"; // hide
                            }
                        }
                    }
                }
        }) 
        </script>
        ";
    }

    /**
     * Add toolbox scripts and functions
     *
     * @return void
     */
    function toolbox()
    {
        print "<style>
        /* TOOLBOX  - dblist - lib */
        .toolbox_mother,
        *:has(> .toolbox) {
          position: relative;
          min-height: 20px;
          width:20px;
        }
        .toolbox {
            text-align: right;
            top: 0;
            right: 0;
            margin: 0px;
            position: absolute;
            padding: 2px;
            border-bottom-left-radius: 10px;
            /*width: 24px;*/
            /*transition: background-color 2s ease-out 100ms;*/
            /*transition: all 2.5s linear;*/
          }
          .toolbox:hover {
              background-color: var(--menu-bg-color);
              /*width:auto;*/
              /*border:1px solid lightgrey;
              border-top: none;
              border-right: none;*/
              padding-left:10px;
          }
          .toolbox:hover >a{
            display:inline-block;
          }

          .toolbox>a {
            display: none;
          }
        </style>";
    }

    /**
     * Add sortable scripts and functions
     *
     * @return void
     */
    function sortable()
    {
        print "<script>
        $(document).ready(function() {
                $(\".sortable tbody tr\").on( \"mouseenter\", function() {
                  $( this ).addClass( \"hover\" );
                } ).on( \"mouseleave\", function() {
                  $( this ).removeClass( \"hover\" );
                } );
                $(\"table tbody\").sortable({
                    handle: '.handle',
                    update: function (event,ui) {
                      var dta = \"\";
                      $(\".sortable tbody tr\").each(function(i) {
                          var id = $(this).attr('id');
                          dta+=\"&dblist%5B%5D=\" + id;
                          //console.log(id);
                      });
                      //var data = $(\".sortable tbody tr\").sortable('serialize');
                      //console.log(dta);
                      var tablename = $( \"#dbtable\" ).text();
                      $('#AjaxResult').load(\"" . URLBASE . "\" + \"/system/ajax/ajaxRequest.php?action=system&subaction=reorder&table=\" + tablename + dta);
                    },
                }).disableSelection();
        });
        </script>";
        print "<style>
        .hover {
          /*background-color:blue;*/
        }
        .hover > td:first-child {
          /*background-color:red;*/
        }
        .hover > td:first-child .handle img {
          display:inline-block;
        }
        .handle {
          display:inline-block;
          /*padding-right:3px;
          padding-top: 3px;*/
          width:16px;
        }
        .handle img {
          display:none;
        }
        </style>";
    }
}