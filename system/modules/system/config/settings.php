<?php
$GLOBALS['MODULE']['system']['settings']=array (
		'pdf_footer' => array (
			'type'     => 'select',
      'field'    => 'system/pdf_footer',
			'caption'  => $GLOBALS['LANG']['system']['settings']['pdf_footer'],
			'options'   =>  getTemplates('footer')
		),
		'default_group' => array (
			'type'     => 'checkboxtable',
			'field'    => 'system/default_group',
			'caption'  => $GLOBALS['LANG']['system']['settings']['default_group'],
			//'foreignkey'    =>  'groups.namn',
			'options'   =>  getCustomForeignkey('groups.namn'),
		),
		/*'width' => ''*/
	);

// 	function getCustomForeignkey($str) {
// 				// Custom companyid here
// 				if (!isset($_GET['id'])) {
// 					setMsg("e","No ID is set. " . __FUNCTION__ . " failed");
// 					return;
// 				} else {
// 					$companyid = intval($_GET['id']);
// 				}

// 				// foreignKey
// 				if (isset($str) || isset($str)) { //  && is_array($str)
// 						if (isset($str)) {
// 								$foreignKey = $str;
// 						} elseif (isset($str)) {
// 								$foreignKey = $str;
// 						}
// 						list($strTable,$strField) = explode(".", $foreignKey);
// 				// print "Tbl: $strTable    Fld: $strField <br>";
// 				$db = db::getInstance();
// 						$GLOBALS['DEBUG'][] = "NU GRUPPER:";
// 						// 20170307 - BUGG HITTAD!!! companyid används ej på 'page' t.ex.
// 						// La till OR companyid = 0 för att se om det fungerar!
// 						// Hur bygga in funktionen med PID?
// 						// Kolla sort key?
// 						// foreignKeyisPage
// 						if (isset($data['foreignKeyisPage'])) {
// 								$arrOptions=array('0'=>' - Inget Valt - ');
// 								$arrAnswer = $db->o_get_all($strTable,"SELECT id, " . $strField . ",pid FROM [table] WHERE deleted=0 AND ( companyid={$companyid} OR companyid=0 ) ORDER BY pid ASC, {$strField} ASC");//
// 								$arrReturn = $arrOptions + $this->OptionsTree($arrAnswer, 0, 0);
// 								return (array) $arrReturn;
// 						} else {
// 					$arrAnswer = $db->o_get_all($strTable,"SELECT id, " . $strField . " FROM [table] WHERE deleted=0 AND ( companyid={$companyid} OR companyid=0 ) ORDER BY {$strField} ASC");//
// 								foreach ($arrAnswer as $key => $value) {
// 										$arrOptions[$key] = $value[$strField];
// 								}
// 						}
// 						foreach ($arrAnswer as $key => $value) {
// 								$arrOptions[$key] = $value[$strField];
// 						}
// 						return (array) $arrOptions;
// 				}

// 	}
