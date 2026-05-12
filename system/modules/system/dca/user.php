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

$GLOBALS['TABLE']['user'] = array(
	'table' => 'user',
	'list'  => array(
		'order' => 'namn,shortdriver',
		'width' => '',
		'edit' => true,
		'where' =>  'active=1 AND grupp!=0'
	),
	'allowAjax' => 1,
	'search'    => array(
		'searchfields'  =>  'regnrtext',
		'searchcaption' =>  $GLOBALS['LANG']['service']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array(
		'palette'   =>  'id,user,namn,shortdriver,email,grupp,sendemail,companyid,lostpassword',
		'onsubmit_callback' => array('tbl_user', 'submit')
	),
	'fields' => array(
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['user']['id']
		),
		'user' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['user']
		),
		'pass' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['pass']
		),
		'namn' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['namn']
		),
		'groups' => array(
			'type'     => 'checkboxtable',
			'caption'  => $GLOBALS['LANG']['user']['groups'],
			'foreignkey'    =>  'groups.namn',
			'value' => getDefaultGroupsForCompany() //'a:1:{i:1;s:1:"1";}'
		),
		'foretag' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['user']['foretag']
		),
		'referens' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['user']['referens']
		),
		'securepass' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['user']['securepass']
		),
		'driverid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['user']['driverid']
		),
		'shortdriver' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['shortdriver']
		),
		'grupp' => array(
			'type'     => 'radio',
			'caption'  => $GLOBALS['LANG']['user']['grupp'],
			'options' => array(0 => 'Visas ej i kalendern', 1 => 'Beställning', 2 => 'Skola', 3 => 'Underentreprenad', 4 => 'Kontor', 5 => 'Reseledare')
		),
		'ordning' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['user']['ordning']
		),
		'ordinary' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['ordinary']
		),
		'active' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['user']['active']
		),
		'deleted' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['user']['deleted']
		),
		'email' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['user']['email']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid'],
			'value' => getCompanyID()
		),
		'oldpass' => array(
			'type'     => 'password',
			'caption'  => $GLOBALS['LANG']['user']['oldpass']
		),
		'newpass' => array(
			'type'     => 'password',
			'caption'  => $GLOBALS['LANG']['user']['newpass']
		),
		'newpass2' => array(
			'type'     => 'password',
			'caption'  => $GLOBALS['LANG']['user']['newpass2']
		),
		'changepwdnextlogin' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['user']['changepwdnextlogin']
		),
		'sendemail' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['user']['sendemail']
		),
		'lostpassword' => array(
			'type'     => 'custom',
			'caption'  => $GLOBALS['LANG']['user']['lostpassword'],
			'callback' => array('tbl_user', 'lostpassword')
		)







	)
);

/**
 * Om ny, skicka lösenord på mejl?
 */
class tbl_user
{

	function lostpassword($dca)
	{
		// template.cls.php -> lostpassword
		// Hårdkopierat, men bör göras på ett annat sött
		//  HUR? bör inte auth hantera detta, eller en modul vid namn user?
		//
		//
		//
		$db = db::getInstance();
		$arrUser = $db->o_get_row('user', "SELECT id,user, namn, email FROM [table] WHERE email='" . $dca['username'] . "' AND deleted=0");
		// Är denna satt så borde alla vara det!
		if (!empty($arrUser['email'])) {
			// Skapa kod här!
			$code = salt(date("Y-m-d") . $arrUser['email'] . $arrUser['user'] . $arrUser['id']);
		}
		return "url";
	}

	function submit($dca)
	{
		// Ladda nödvändiga variabler
		$db = db::getInstance();
		// Sätt failed till 0
		$dca['bolFailed'] = false;
		// Pass for new user
		$strNewUserPWD = "start";
		/**
		 *  Ny användare
		 * --------------
		 *  Skicka ett mejl med uppgifterna till nya användaren
		 *  VIKTIGT!!! Inte tillåta samma epost på flera konton!!
		 *
		 */
		// 2024-11-18 Tog bort denna då den resettade alla användare
		// if (!isset($dca['db_groups'])) {


		// system/default_group

		// 	$dca['db_groups'] = 'a:1:{i:1;s:1:"1";}';
		// 	$dca['form']['palette'] .= ",groups";
		// }

		// Kolla om ny så lägg till start som epost samt skicka denne ett mail!
		if ($dca['db_id'] == "") {
			// Finns användare redan??
			$arrUser = $db->o_get_all('user', "SELECT * FROM [table] WHERE user='" . $dca['db_user'] . "' AND deleted=0 AND companyid=" . getCompanyID());
			if (count($arrUser) > 0) {
				$dca['bolFailed'] = true;
				setMsg('e', $GLOBALS['LANG']['user']['error_username']);
				return $dca;
			}
			// Fixa med lösenordet samt fixa paletten
			$dca['db_securepass'] = salt($strNewUserPWD);
			$dca['form']['palette'] .= ",securepass";
			// Kolla om epost finns
			if ($dca['db_email'] == "") {
				$dca['bolFailed'] = true;
				setMsg('e', $GLOBALS['LANG']['user']['error_email_missing']);
				return $dca;
			}
			// Kolla dublett EPOST
			$arrUser = $db->o_get_all('user', "SELECT * FROM [table] WHERE email='" . $dca['db_email'] . "' AND deleted=0 AND companyid=" . getCompanyID());
			if (count($arrUser) > 0) {
				$dca['bolFailed'] = true;
				setMsg('e', $GLOBALS['LANG']['user']['error_email']);
				return $dca;
			}
			// Om group är ställd till 0
			if ($dca['db_grupp'] == 0) {
				setMsg('e', 'Ni kommer inte att se användaren i "PERSONAL LISTAN" då användaren inte är tillagd i någon grupp! <br />Vill du ändra detta så gå in på INSTÄLLNINGAR &gt; ANVÄNDARE och ändra där till någon annan grupp.<br />Har du inte access dit så hör av dig till ansvarig för sajten.');
				// Vill du ändra det direkt. Klicka här!
			}

			$userdata = auth::$userData;

			// Mail new user
			$strMsg = nl2br("Hej!\n
            Ett konto har skapats för dig på <a href=\"" . $GLOBALS['CONFIG']['www-root'] . "\">" . $GLOBALS['CONFIG']['www-root'] . "</a> av " . $userdata['company'] . ".\n
            ———————— Uppgifter för login --------------------------\n
            Vänligen spara detta och se till att detta inte sprids till obehöriga.\n
            <b>Anv.namn: " . $dca['db_user'] . "</b> \n
            <b>Lösen*: " . $strNewUserPWD . "</b> \n
            \n
            *OBS! Detta lösen fungerar endast första gången du loggar in på <a href=\"" . $GLOBALS['CONFIG']['www-root'] . "\">" . $GLOBALS['CONFIG']['title'] . "</a>\n
            Du blir då ombedd att ändra till ett personligt lösen.\n
            Ett \"säkert\" lösen skall vara minst 8 tecken långt och innehålla minst en stor bokstav (A-Z), en liten bokstav (a-z), ett specialtecken (ex. !/%&) och en siffra (0-9). Det skall dock INTE innehålla mellanslag eller Å Ä Ö.
            Detta är ett krav för <a href=\"" . $GLOBALS['CONFIG']['www-root'] . "\">" . $GLOBALS['CONFIG']['title'] . "</a>.\n
            \n
            Lycka till\n
            " . $GLOBALS['CONFIG']['SMTP-from-name'] . "\n
            ");
			// pre($strMsg);
			// pre(auth::$userData);
			// $auth = auth::getInstance();
			// $arrUser = $auth->getUserAndCompany();
			// print($arrUser['company']);
			if (sendMail($dca['db_email'], 'Ett konto är skapat på ' . $GLOBALS['CONFIG']['title'] . ' för dig.', $strMsg)) {
				setMsg('n', $GLOBALS['LANG']['user']['ok_mail']);
			}
			// Fixa så nytt lösen vid inlogg
			$dca['db_changepwdnextlogin'] = 1;
		}





		/*         print "<pre>";
         print_r($dca);
         print "<pre>";*/
		//         print "MINI MEE!";
		/*         exit;*/
		//


		// Kolla lösenord
		if (!empty($dca['db_oldpass']) && !empty($dca['db_newpass']) && !empty($dca['db_newpass2'])) {
			$db = db::getInstance();
			$auth = auth::getInstance();
			$arrUserstuff = $auth->getUserAndCompany();
			$arrUser = $db->o_get_row('user', "SELECT * FROM [table] WHERE id=" . $arrUserstuff['id'] . " AND deleted=0 AND companyid=" . getCompanyID());
			// Jämför gamla lösenorder med det som finns i databasen
			if ($arrUser['securepass'] == salt($dca['db_oldpass'])) {
				if ($dca['db_newpass2'] == $dca['db_newpass']) {
					$dca['db_securepass'] = salt($dca['db_newpass']);
				} else {
					setMsg("e", $GLOBALS['LANG']['user']['error_compare_pass']);
				}
			} else {
				setMsg("e", $GLOBALS['LANG']['user']['error_pass']);
			}
		}
		unset($dca['db_oldpass']);
		unset($dca['db_newpass']);
		unset($dca['db_newpass2']);
		$dca['form']['palette'] = str_replace(",oldpass,newpass,newpass2", "", $dca['form']['palette']);

		// debug();
		// die('<h1>Martin jobbar med denna funktion nu! INGET SPARAS!</h1>');

		return $dca;
	}
}

function getDefaultGroupsForCompany()
{
	// $arrAnswer = getCompanySettings();
	$arrAnswer = settings::getSettings();
	if (isset($arrAnswer['module']['system']['default_group'])) {
		return serialize($arrAnswer['module']['system']['default_group']);
	}
	//print_r($arrAnswer);
	return 'a:1:{i:1;s:1:"1";}';
}

/*

———————— Uppgifter för login --------------------------

Vänligen spara detta och se till att detta inte sprids till obehöriga.



Konto: hallensbuss
Anv.namn: marton (de 3 första bokstäverna i för- resp. efternamn sammanslaget)
Lösen*: start




*OBS! Detta lösen fungerar endast första gången du loggar in på www.idha.com/se/start
Du blir då ombedd att ändra till ett personligt lösen.
Detta går EJ att återskapa, se därför till att det är ett lösen du kan komma ihåg.




Ett "säkert" lösen bör vara minst 6 tecken långt och innehålla minst en stor bokstav (A-Z), en liten bokstav (a-z), ett specialtecken (ex. !/%&) och en siffra (0-9). Det skall dock INTE innehålla mellanslag eller Å Ä Ö.
Detta är en rekommendation, men inte ett krav för idha-Online.

*/
addJS("system/modules/system/js/user.php");
