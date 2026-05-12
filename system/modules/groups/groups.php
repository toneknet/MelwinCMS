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

/**
 * Description of groups
 *
 *
 * FÖRST VILKA SIDOR MAN FÅR SE
 *  SEDAN  KAN MAN GÅ IN DJUPARE PÅ VARJE NIVÅ..
 * t.ex bemanning. Vilka får LÄSA, vilka får ändra
 * eller. bussar. Vilka bussar får man se( och hur implementerar man det?)
 *
 *
 *
 * VIKTIGT!
 * Om nya sidor har tillkommit så kommer man INTE åt dem föräns de är tillagda i gruppen
 *
 * ATT GÖRA!!
 * Hur spärra så att man inte kan lägga till sidor man inte har access till?
 * Hur kunna lägga till grupper till andra företag? Endast superadmin!
 *
 * Använda sig av dblist?
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class groups
{

    private $arrAccess = array();
    private $allowEdit = false;
    private $dbFormSettings;

    function __construct($arrModuleSettings)
    {
        // Säkerhet
        $this->arrAccess = $arrModuleSettings['own_access_pages'];
        $this->allowEdit = $arrModuleSettings['own_access']['edit'];
        // pre($arrModuleSettings);

        // Laddar nödvändiga klasser
        $input = new input();
        if ($input->get('action') == 'save') {
            $this->saveGroup();
        }
        // Delete here
        if ($input->get('action') == 'delete' && intval($input->get('id')) > 0) {
            $db = db::getInstance();
            if ($db->updateDelete('groups', intval($input->get('id')))) {
                setMsg('s', "Gruppen har raderats!");
            }
        }

        if ($input->get('action') == 'edit' || $input->get('action') == 'add') {
            $this->editGroup();
        } else {
            $db = db::getInstance();
            $arrPages = $db->o_get_all('groups', "SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID() . " ORDER BY ordning");

            // Gå igenom så att man har access som finns i grupperna för att
            // ändra! Annars tas gruppen bort.
            $auth = auth::getInstance();
            if (!$auth->isSuperUser()) {
                foreach ($arrPages as $key => $value) {
                    $arrTmp = unserialize($value['settings']);
                    $arrDiff = array_diff_assoc($arrTmp, $arrModuleSettings['own_access_pages']);
                    if (count($arrDiff) > 0) {
                        unset($arrPages[$key]);
                    }
                }
            }
            // print "här";
            // Skapar lista
            print "<a class=\"button button-primary\" href=\"?action=add\">Lägg till ny</a>\n";
            print "<br>";
            $dblist = new dblist();
            // pre($GLOBALS['TABLE']['groups']);
            // MERGE
            $dblist->setData($arrPages);
            $this->dbFormSettings = array_merge_own($GLOBALS['TABLE']['groups'], $arrModuleSettings);
            $dblist->MakeWithSettings($this->dbFormSettings);
        }
    }




    private function saveGroup()
    {
        $db = db::getInstance();
        $input = new input();
        $arrFields = $input->postAll();
        //        print "<pre>";
        //        print_r($arrFields);
        //        print "</pre>";

        if (intval($arrFields['db_id']) > 0) {
            $id = intval($arrFields['db_id']);
            // UPDATE
            $namn = $arrFields['db_namn'];
            $description = $arrFields['db_description'];
            $settings = serialize($arrFields['db_page']);
            if ($db->o_update('groups', array('namn' => $namn, 'description' => $description, 'settings' => $settings, 'companyid' =>  getCompanyID()), 'id=' . $id)) {
                $success = true;
                $strMsg = "Gruppen har uppdaterats";
            } else {
                $success = false;
                $strMsg = "Ändringarna kunde inte sparas!";
            }
        } else {
            // INSERT
            $namn = $arrFields['db_namn'];
            $description = $arrFields['db_description'];
            $settings = serialize($arrFields['db_page']);
            if ($db->o_insert('groups', array('namn' => $namn, 'description' => $description, 'settings' => $settings, 'companyid' =>  getCompanyID()))) {
                $success = true;
                $strMsg = "Den nya gruppen har sparats!";
            } else {
                $success = false;
                $strMsg = "Gruppen kunde inte läggas till!";
            }
        }
        if ($success) {
            setMsg('s', $strMsg);
        } else {
            setMsg('e', $strMsg);
        }
    }

    /**
     * Skapar listan med editeringsformuläret
     *
     */
    private function editGroup()
    {
        $db = db::getInstance();
        $form = new form();
        $input = new input();
        // Sätter en temporär array här
        $arrFields['id']['field'] = 'id';
        $arrFields['namn']['field'] = 'namn';
        $arrFields['namn']['caption'] = array('Namn på grupp', 'Här anger du namnet på gruppen');
        $arrFields['description']['field'] = 'description';
        $arrFields['description']['caption'] = array('Beskrivning av grupp', 'Här anger du en kort beskrivning av gruppen');
        //        $arrFields['settings']['field'] = 'settings';



        // Ladda data om det behövs?
        if ($input->get('id') != "") {
            $id = intval($input->get('id'));
            // Hämta data
            $arrData = $db->o_get_row('groups', "SELECT * FROM [table] WHERE companyid=" . getCompanyID() . " AND deleted=0 AND id=" . $id);
            $arrFields['id']['value'] = $id;
            $arrFields['namn']['value'] = $arrData['namn'];
            $arrFields['description']['value'] = $arrData['description'];
            if ($arrData['settings'] != "") {
                $arrSettings = unserialize($arrData['settings']);
            }
        }
        $GLOBALS['DEBUG'][][__CLASS__ . "::" . __FUNCTION__] = $arrSettings;

        // Endast visa sidor man har access till!!!
        // Bygger en WHERE sats här som endast visar sidor man själv har access till
        foreach ($this->arrAccess as $key => $value) {
            if ($value['edit'] == 1) {
                $arrWhere[] = "`id` = '$key'";
            }
        }
        $strWhere = implode(' OR ', $arrWhere);

        //        print $strWhere."<br>";
        //        print "SELECT * FROM [table] WHERE {$strWhere} ORDER BY pid,ordning"."<br>";
        $GLOBALS['DEBUG'][][__CLASS__ . "::" . __FUNCTION__] = $arrWhere;
        // Bygger en specialare för företags sidan
        $auth = auth::getInstance();
        $strWhere = "(" . $strWhere . ")";
        if (!$auth->isSuperUser()) {
            $strWhere .= " AND id!=" . $GLOBALS['CONFIG']['TheCompanyPage'];
        } else {
            $strWhere = " id!=0 ";
        }
        // Hämtar sidor här
        $arrPages = $db->o_get_all('page', "SELECT * FROM [table] WHERE {$strWhere} AND deleted=0 AND visible=1 ORDER BY isstartpage DESC, pid ASC, ordning ASC");

        // Denna fungerar VISST men vi måste plocka bort sidor som inte finns längre!
        if (is_array($arrSettings)) {
            foreach ($arrSettings as $key => $value) {
                foreach ($value as $skey => $svalue) {
                    if (is_array($arrPages[$key])) {
                        $arrPages[$key][$skey] = $svalue;
                    }
                }
            }
        }
        // Börja skriva ut formuläret
        print $form->form_begin();
        print $form->field_HIDDEN($arrFields['id']);
        print "<p>" . $form->field_INPUT($arrFields['namn']) . "</p>";
        print "<p>" . $form->field_TEXTAREA($arrFields['description']) . "</p>";
        print "\n<table>\n";
        print "\t<thead>\n";
        print "\t\t<tr>\n";
        print "\t\t\t<th>Sida</th>\n";
        print "\t\t\t<th>Tillåten</th>\n";
        print "\t\t\t<th>Editera</th>\n";
        print "\t\t</tr>\n";
        print "\t</thead>\n";
        $this->GenerateTree($arrPages, 0, 0);
        print "</table>\n";
        print $form->form_submit();
        print $form->form_end();
    }

    /**
     * Genererar listan som ett träd
     *
     * @param array $arrData Datat som skall gås igenom
     * @param integer $intPid Idt för modern
     * @param integer $intLevel Nivån på listan
     */
    private function GenerateTree($arrData, $intPid, $intLevel)
    {
        if (is_array($arrData)) {
            $form = new form();
            foreach ($arrData as $value) {
                if ($value['pid'] == $intPid) {
                    print "\t<tr>\n";
                    print "\t\t<td>";
                    for ($index = 0; $index < $intLevel; $index++) {
                        //print "&nbsp;&nbsp;";
                        print getIconFull('transp', 30, 5);
                    }
                    print $value['visible'] ? getIconFull('visible') : getIconFull('invisible');
                    print " " . $value['label'] . "</td>\n";
                    print "\t\t<td>" . $form->field_CHECKBOX(array('field' => "page[" . $value['id'] . "][allow]", 'value' => $value['allow'])) . "</td>\n";
                    print "\t\t<td>" . $form->field_CHECKBOX(array('field' => "page[" . $value['id'] . "][edit]", 'value' => $value['edit'])) . "</td>\n";
                    print "\t</tr>\n";
                    //                    print $value['id']."<br>";
                    $this->GenerateTree($arrData, $value['id'], $intLevel + 1);
                }
            }
        }
    }
}
