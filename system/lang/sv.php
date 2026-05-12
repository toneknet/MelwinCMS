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

/*
 * ONLY ALLOW GENERAL, SYSTEM AND LOGIN TAGS HERE!!
 */


// SYSTEM
$GLOBALS['LANG']['SYSTEM']['id'] = array('ID', '');
$GLOBALS['LANG']['SYSTEM']['companyid'] = array('Företags ID', '');
$GLOBALS['LANG']['SYSTEM']['deleted'] = array('Raderad', '');
$GLOBALS['LANG']['SYSTEM']['ordning'] = array('Ordning', '');

// TABLE: VEHICLE

$GLOBALS['LANG']['vehicle']['id'] = array('ID', '');
$GLOBALS['LANG']['vehicle']['regnr'] = array('Reg.nr', 'Fordonets registreringsnummer');
$GLOBALS['LANG']['vehicle']['kategori'] = array('Kategori', 'Välj kategori fordonet skall tillhöra');
$GLOBALS['LANG']['vehicle']['ordning'] = array('Ordning', 'Välj i vilken ordning fordonet skall sorteras');
$GLOBALS['LANG']['vehicle']['active'] = array('Aktiv', 'Ange om fordonet är aktivt');
$GLOBALS['LANG']['vehicle']['b_name'] = array('Fordonets namn', 'Ange vad fordonet skall ha för namn i bokningen');
$GLOBALS['LANG']['vehicle']['b_color'] = array('Boknings färg', 'Ange vad fordonet skall ha för färg i bokningen');
$GLOBALS['LANG']['vehicle']['b_visible'] = array('Aktiv i bokning', 'Ange om fordonet skall vara aktiv i bokningen');
$GLOBALS['LANG']['vehicle']['b_id'] = array('Boknings ID', 'Ange ett ID som skall kopplas till bokningen.');
$GLOBALS['LANG']['vehicle']['deleted'] = array('Radera', 'Ange om du vill radera filen.');
$GLOBALS['LANG']['vehicle']['b_exist'] = array('Existerar', 'Visar om bilen finns i företaget enligt Transportstyrelsen');

// TABLE: DRIVER

$GLOBALS['LANG']['driver']['id'] = array('ID', '');
$GLOBALS['LANG']['driver']['driver'] = array('Förare', 'Ange förarens namn');
$GLOBALS['LANG']['driver']['shortdriver'] = array('Initialer', 'Ange förarens initialer');
$GLOBALS['LANG']['driver']['grupp'] = array('Grupp', 'Ange grupp som föraren skall tillhöra');
$GLOBALS['LANG']['driver']['ordning'] = array('Ordning', 'Ange vilken ordning föraren skall ha i sorteringen');
$GLOBALS['LANG']['driver']['ordinary'] = array('Ordinarie tur', 'Ange förarens ordinarie tur');
$GLOBALS['LANG']['driver']['active'] = array('Aktiv', 'Här anges om föraren skall synas');


// TABLE: SERVICE
$GLOBALS['LANG']['service']['id'] = array('ID', '');
$GLOBALS['LANG']['service']['atgard'] = array('Åtgärd', 'Här anges vilka åtgärder som gjorts');
$GLOBALS['LANG']['service']['regnr'] = array('Fordon', 'Här anges vilket fordon det gäller');
$GLOBALS['LANG']['service']['regnrtext'] = array('Regnr', 'Här anges vilket registreringsnummer fordonet har.');
$GLOBALS['LANG']['service']['mil'] = array('Mätarställning', 'Här anges mätarställningen');
$GLOBALS['LANG']['service']['datum'] = array('Datum', 'Här anges datumet för åtgärden');
$GLOBALS['LANG']['service']['tid'] = array('Tidsåtgång', 'Här anges den tidsåtgång som behövdes för åtgärden');
$GLOBALS['LANG']['service']['signatur'] = array('Signatur', 'Här anges signatur på personal som utförd åtgärden');
$GLOBALS['LANG']['service']['searchcaption'] = array('Sök på åtgärder', 'Ange registreringsnummer för att söka');
$GLOBALS['LANG']['service']['deleted'] = array('Raderad', '');



// TABLE: BEMANNING
$GLOBALS['LANG']['booking']['id'] = array('ID', '');
$GLOBALS['LANG']['booking']['bus'] = array('Fordons ID', 'Fordonets id');
$GLOBALS['LANG']['booking']['startdatex'] = array('Startdatum och klockslag', '');
$GLOBALS['LANG']['booking']['stopdatex'] = array('Slutdatum och klockslag', '');
$GLOBALS['LANG']['booking']['multidate'] = array('Flera datum', 'Anger flera datum.');
$GLOBALS['LANG']['booking']['startdate'] = array('Startdatum', 'Här anges resans startdatum');
$GLOBALS['LANG']['booking']['stopdate'] = array('Slutdatum', 'Här anges resans slutdatum');
$GLOBALS['LANG']['booking']['starttime'] = array('Starttid', 'Här anges resans starttid');
$GLOBALS['LANG']['booking']['stoptime'] = array('Sluttid', 'Här anges resans sluttid');
$GLOBALS['LANG']['booking']['prel'] = array('', '');
$GLOBALS['LANG']['booking']['resan'] = array('Resans namn', 'Här anger du resans namn');
$GLOBALS['LANG']['booking']['chaffor'] = array('Chaufför', 'Här anger du chaufför för resan'); // Skall matas med id:t från chauffor_id, javascript
$GLOBALS['LANG']['booking']['reseledare'] = array('Reseledare', 'Här anger du om/vem som ska följa med som reseledare'); // Dropdown?
$GLOBALS['LANG']['booking']['kommentar'] = array('Kommentar', 'Här anger du en kommentar om resan');
$GLOBALS['LANG']['booking']['service'] = array('Service', 'Här anger du om bokningen är ett serviceärende');
$GLOBALS['LANG']['booking']['chbekr'] = array('Bekräftelse av chaufför', '');
$GLOBALS['LANG']['booking']['confirmed'] = array('Resa bekräftad', 'Här anger man om resan är bekräftad');
$GLOBALS['LANG']['booking']['ts'] = array('', '');
$GLOBALS['LANG']['booking']['chauffor_id'] = array('Chaufför', 'Här anger du vilken chaufför som skall köra resan');
$GLOBALS['LANG']['booking']['visible'] = array('Synlig för alla', 'Väljer du detta val så blir bokningen synlig för alla');
$GLOBALS['LANG']['booking']['avresa'] = array('Avresa', 'Här anger du information till chaufför om avresa');
$GLOBALS['LANG']['booking']['program'] = array('Program', 'Här anger du programmet till chauffören');
$GLOBALS['LANG']['booking']['hemresa'] = array('Hemresa', 'Här anger du information om hemresan till chauffören');
$GLOBALS['LANG']['booking']['antal'] = array('Antal', 'Här anger du antalet resenärer');
$GLOBALS['LANG']['booking']['referens'] = array('Referens', 'Här anger du referens för bokningen inkl ev. telenr');
$GLOBALS['LANG']['booking']['ovrigt'] = array('Övrig information', 'Här anger du övrig information till bokningen');
$GLOBALS['LANG']['booking']['kalk_tim'] = array('Kalkylerad arbetstid', 'Här anger du chaufförens kalkylerade arbetstid');
$GLOBALS['LANG']['booking']['kalk_vante'] = array('Kalkylerad väntetid', 'Här anger du chaufförens kalkylerade väntetid');
$GLOBALS['LANG']['booking']['kalk_ob'] = array('Kalkylerad ob ersättning', 'Här anger du chaufförens kalkylerade ob ersättning');
$GLOBALS['LANG']['booking']['kalk_trakt'] = array('Kalkylerad traktamente', 'Här anger du chaufförens kalkylerade traktamente');
$GLOBALS['LANG']['booking']['kalk_km'] = array('Kalkylerad kilometer', 'Här anger du resans kalkylerade kilometer');
$GLOBALS['LANG']['booking']['companyid'] = array('Företags ID', 'Här anger du företags ID');

// TABLE: FUEL
$GLOBALS['LANG']['fuel']['id'] = array('', '');
$GLOBALS['LANG']['fuel']['datum'] = array('Datum', 'Datum för tankning');
$GLOBALS['LANG']['fuel']['liter'] = array('Liter', 'Liter som tankats');
$GLOBALS['LANG']['fuel']['regnr'] = array('Registreringsnummer', 'Registreringsnummer');
$GLOBALS['LANG']['fuel']['odometer'] = array('Mätarställning', 'Mätarställning vid tankning');

// TABLE: COMPANY
$GLOBALS['LANG']['company']['id'] = array('ID', 'Företagets ID');
$GLOBALS['LANG']['company']['namn'] = array('Företagsnamn', 'Företagets namn');

$GLOBALS['LANG']['company']['dbprefix'] = array('Företagets Databas prefix', 'Här anges företagets databas prefix');
$GLOBALS['LANG']['company']['companyid'] = array('Företagets ID', 'Här anges företagets ID. ÄNDRAS ALDRIG!');


// TABLE: USER
$GLOBALS['LANG']['user']['id'] = array('ID', '');
$GLOBALS['LANG']['user']['user'] = array('Användarnamn (bör genereras automatiskt)', '<b>Detta skapas oftast automatiskt efter du fyllt i användarens namn.</b><br /><i>När du lägger till en ny användare skall du ALLTID som användarnamn ange personens tre första bokstäver i för och efternamn som gemener.<br /> T.ex. marton = Martin Tonek. Använd INTE å ä ö.</i>');
$GLOBALS['LANG']['user']['pass'] = array('Lösenord', 'Användarens lösenord');
$GLOBALS['LANG']['user']['namn'] = array('Namn', 'För och efternamn på användaren');
$GLOBALS['LANG']['user']['groups'] = array('Access', 'Här anger du vilken access användaren skall ha.');
$GLOBALS['LANG']['user']['foretag'] = array('Företag', '');
$GLOBALS['LANG']['user']['referens'] = array('Referens', '');
$GLOBALS['LANG']['user']['securepass'] = array('securepass', '');
$GLOBALS['LANG']['user']['driverid'] = array('driverid', '');
$GLOBALS['LANG']['user']['shortdriver'] = array('Initialer (bör genereras automatiskt)', 'Här anger du personalens initialer');
$GLOBALS['LANG']['user']['grupp'] = array('Grupp', 'Här anger du om personen skall tillhöra en viss grupp vid planeringen. 0 = visas ej i kalender');
$GLOBALS['LANG']['user']['ordning'] = array('ordning', '');
$GLOBALS['LANG']['user']['ordinary'] = array('Standardkörning', '');
$GLOBALS['LANG']['user']['active'] = array('Aktiv', '');
$GLOBALS['LANG']['user']['deleted'] = array('Raderad', '');
$GLOBALS['LANG']['user']['email'] = array('Epost', 'Här anger du personalens epostadress');
$GLOBALS['LANG']['user']['companyid'] = array('Företags', 'Här anger du till vilket företagt användaren tillhör');
$GLOBALS['LANG']['user']['oldpass'] = array('Ditt gamla lösenord', 'Här anger du det lösenord du har nu');
$GLOBALS['LANG']['user']['newpass'] = array('Ditt nya lösenord', 'Här anger du det lösenord du vill byta till');
$GLOBALS['LANG']['user']['newpass2'] = array('Verifiera ditt nya lösenord', 'Här anger du en gång till det nya lösenord du önskar');
$GLOBALS['LANG']['user']['changepwdnextlogin'] = array('Användare måste byta lösenord vid nästa inloggning', 'Användaren tvingas byta lösenord vid nästa login');
$GLOBALS['LANG']['user']['error_pass'] = "Det lösenord du har angivit stämmer inte överens med ditt lösenord";
$GLOBALS['LANG']['user']['error_compare_pass'] = "Det nya lösenordet stämmer inte överens med varandra.";
$GLOBALS['LANG']['user']['error_username'] = "Användarnamnet är redan upptaget, välj ett annat. Pröva de tre första bokstäverna i ditt för- och efternamn tillsammans.";
$GLOBALS['LANG']['user']['error_email'] = "Epostadressen används redan på ett konto, välj en annan epostadress.";
$GLOBALS['LANG']['user']['error_email_missing'] = "Du har inte angivit någon epostadress.";
$GLOBALS['LANG']['user']['ok_mail'] = "Ett meddelande har skickats till den nya användaren!";
$GLOBALS['LANG']['user']['sendemail'] = array('Ta emot körordrar via epost', 'Användaren vill ta emot körordrar via epost');
$GLOBALS['LANG']['user']['lostpassword'] = array('Skicka mejl om nytt lösenord', '');

// TABLE: CUSTOMERS
$GLOBALS['LANG']['customers']['id'] = array('Kundnr', 'Här anges Kundnumret');
$GLOBALS['LANG']['customers']['customer_type'] = array('Kundtyp', 'Här anges om personen är företag eller privatperson');
$GLOBALS['LANG']['customers']['firstname'] = array('Förnamn', '');
$GLOBALS['LANG']['customers']['lastname'] = array('Efternamn', '');
$GLOBALS['LANG']['customers']['personalno'] = array('Personnummer', '');
$GLOBALS['LANG']['customers']['email'] = array('Epost', '');
$GLOBALS['LANG']['customers']['adress'] = array('Postadress', '');
$GLOBALS['LANG']['customers']['city'] = array('Postort', '');
$GLOBALS['LANG']['customers']['cityno'] = array('Postnr', '');
$GLOBALS['LANG']['customers']['country'] = array('Land', '');
$GLOBALS['LANG']['customers']['prefered_send'] = array('Skicka fakturor med', 'Välj e-post eller brev');
$GLOBALS['LANG']['customers']['house_id'] = array('Fastighetsbeteckningen', '');

//  MODULE: BUSCALENDER
$GLOBALS['LANG']['BUSCALENDER']['error_crash'] = "Det finns redan en bokning under denna period!<br><b>%s</b>";
//$GLOBALS['LANG']['BUSCALENDER']['weekday']="%s <br> %s";
//$GLOBALS['LANG']['BUSCALENDER']['footer']="<strong>&Ouml;vrigt:</strong> <br> %s";

//  MODULE: ONLYEDIT
$GLOBALS['LANG']['MODULE']['ONLYEDIT']['edit'] = array('Här anger du nyckelfält för editering', '');

$GLOBALS['LANG']['MODULE']['tablemaker']['name'] = array('Ange fältets namn', 'Här anger du fältets namn');
$GLOBALS['LANG']['MODULE']['tablemaker']['type'] = array('Ange fältets typ', 'Här anger du fältets typ');
$GLOBALS['LANG']['MODULE']['tablemaker']['length'] = array('Ange fältets längd', 'Detta fält används inte till alla typer');
$GLOBALS['LANG']['MODULE']['tablemaker']['default'] = array('Ange fältets standardvärde', 'Lämna tomt om inget önskas');
$GLOBALS['LANG']['MODULE']['tablemaker']['caption'] = array('Rubriktext', 'Här anger du fältets rubriktext');
$GLOBALS['LANG']['MODULE']['tablemaker']['smallcaption'] = array('Undertext', 'Här anger du fältets undertext');
$GLOBALS['LANG']['MODULE']['tablemaker']['table'] = array('Tabellnamn', 'Här anger du vad tabellen skall heta');
$GLOBALS['LANG']['MODULE']['tablemaker']['options'] = array('Tillval', 'Lämna tomt om inget önskas');
$GLOBALS['LANG']['MODULE']['tablemaker']['options_callback'] = array('Förvalt tillval', 'Lämna tomt om inget önskas');
$GLOBALS['LANG']['MODULE']['tablemaker']['foreignKey'] = array('Databas koppling som värde', 'Denna används som tabell.nyckelfält . Lämna tomt om inget önskas');
$GLOBALS['LANG']['MODULE']['tablemaker']['save_callback'] = array('Kör funktion vid sparande', 'Här anger du om du vill köra en funktion på detta fältet när det sparas. Lämna tomt om inget önskas');
$GLOBALS['LANG']['MODULE']['tablemaker']['default_callback'] = array('Ange fältets standardvärde med funktion', 'Detta val skriver över fältets standardvärde');

$GLOBALS['LANG']['MODULE']['tablemaker']['deleteNotAvalible'] = "Av säkerhetsskäl så kan du inte radera tabeller eller fält. Måste detta ske så kontakta din administratör.";
$GLOBALS['LANG']['MODULE']['tablemaker']['illegalTableName'] = "Ej tillåtet tabell namn. Namnet innehåller otillåtna tecken eller mellanslag.";
$GLOBALS['LANG']['MODULE']['tablemaker']['TableExist'] = "Otillåtet tabell namn. Tabellen finns redan.";

$GLOBALS['LANG']['MODULE']['tablemaker']['TableCreated'] = "Tabellen har skapats.";
$GLOBALS['LANG']['MODULE']['tablemaker']['CouldNotCreateTable'] = "Något gick fel. Kunde inte skapa tabellen.";
$GLOBALS['LANG']['MODULE']['tablemaker']['SystemErrorTable'] = "Systemet kunde inte genomföra ändringarna på grund av förekommande fel!";
$GLOBALS['LANG']['MODULE']['tablemaker']['CouldNotCreateField'] = "Något gick fel. Kunde inte skapa vissa fält.";
$GLOBALS['LANG']['MODULE']['tablemaker']['SucessCreateField'] = "Fältet/en har ändrats/lagts till!";


//  LIB: FORMCATCHER
$GLOBALS['LANG']['FORMCATCHER']['error_callback'] = "Något har gått fel under data insamlandet! Var god försök igen!";

//  LIB: FORM
$GLOBALS['LANG']['FORM']['submit'] = "Spara";

// GENERAL
$GLOBALS['LANG']['GENERAL']['ok'] = "OK";
$GLOBALS['LANG']['GENERAL']['cancel'] = "Avbryt";
$GLOBALS['LANG']['GENERAL']['save'] = "Spara";
$GLOBALS['LANG']['GENERAL']['add'] = "Lägg till";
$GLOBALS['LANG']['GENERAL']['edit'] = "Ändra";
$GLOBALS['LANG']['GENERAL']['delete'] = "Radera";
$GLOBALS['LANG']['GENERAL']['delete_confirm'] = "Är du säker på att du vill radera denna post?";
$GLOBALS['LANG']['GENERAL']['yes'] = "Ja";
$GLOBALS['LANG']['GENERAL']['no'] = "Nej";
$GLOBALS['LANG']['GENERAL']['hide'] = "Dölj";
$GLOBALS['LANG']['GENERAL']['show'] = "Visa";
$GLOBALS['LANG']['GENERAL']['move'] = "Flytta";
$GLOBALS['LANG']['GENERAL']['return'] = "Tillbaka";

$GLOBALS['LANG']['GENERAL']['move_after'] = "Placera efter objektet";
$GLOBALS['LANG']['GENERAL']['move_into'] = "Placera i objektet";

$GLOBALS['LANG']['GENERAL']['show_all'] = 'Visa alla';

$GLOBALS['LANG']['GENERAL']['noentry'] = "-- Inget Valt --";

$GLOBALS['LANG']['GENERAL']['saved'] = "Händelsen har sparats";
$GLOBALS['LANG']['GENERAL']['update'] = "Händelsen har uppdaterats";
$GLOBALS['LANG']['GENERAL']['deleted'] = "Händelsen har raderats";
//$GLOBALS['LANG']['GENERAL']['save']="Spara";
$GLOBALS['LANG']['GENERAL']['search'] = "Sök";
$GLOBALS['LANG']['GENERAL']['search_no_result'] = "Sökningen gav inget resultat";
$GLOBALS['LANG']['GENERAL']['hidden'] = "Händelsen har dolts";
$GLOBALS['LANG']['GENERAL']['visible'] = "Händelsen har gjorts synlig";
$GLOBALS['LANG']['GENERAL']['error'] = "Händelsen har inte kunnat sparas";

$GLOBALS['LANG']['GENERAL']['weekday']['long'][1] = "M&aring;ndag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][2] = "Tisdag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][3] = "Onsdag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][4] = "Torsdag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][5] = "Fredag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][6] = "L&ouml;rdag";
$GLOBALS['LANG']['GENERAL']['weekday']['long'][7] = "S&ouml;ndag";

$GLOBALS['LANG']['GENERAL']['weekday']['short'][1] = "M&aring;n";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][2] = "Tis";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][3] = "Ons";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][4] = "Tor";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][5] = "Fre";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][6] = "L&ouml;r";
$GLOBALS['LANG']['GENERAL']['weekday']['short'][7] = "S&ouml;n";

$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][1] = "M&aring;";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][2] = "Ti";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][3] = "On";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][4] = "To";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][5] = "Fr";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][6] = "L&ouml;";
$GLOBALS['LANG']['GENERAL']['weekday']['shorter'][7] = "S&ouml;";

$GLOBALS['LANG']['GENERAL']['month']['short'][1] = "jan";
$GLOBALS['LANG']['GENERAL']['month']['short'][2] = "feb";
$GLOBALS['LANG']['GENERAL']['month']['short'][3] = "mar";
$GLOBALS['LANG']['GENERAL']['month']['short'][4] = "apr";
$GLOBALS['LANG']['GENERAL']['month']['short'][5] = "maj";
$GLOBALS['LANG']['GENERAL']['month']['short'][6] = "jun";
$GLOBALS['LANG']['GENERAL']['month']['short'][7] = "jul";
$GLOBALS['LANG']['GENERAL']['month']['short'][8] = "aug";
$GLOBALS['LANG']['GENERAL']['month']['short'][9] = "sep";
$GLOBALS['LANG']['GENERAL']['month']['short'][10] = "okt";
$GLOBALS['LANG']['GENERAL']['month']['short'][11] = "nov";
$GLOBALS['LANG']['GENERAL']['month']['short'][12] = "dec";

$GLOBALS['LANG']['GENERAL']['month']['long'][1] = "januari";
$GLOBALS['LANG']['GENERAL']['month']['long'][2] = "februari";
$GLOBALS['LANG']['GENERAL']['month']['long'][3] = "mars";
$GLOBALS['LANG']['GENERAL']['month']['long'][4] = "april";
$GLOBALS['LANG']['GENERAL']['month']['long'][5] = "maj";
$GLOBALS['LANG']['GENERAL']['month']['long'][6] = "juni";
$GLOBALS['LANG']['GENERAL']['month']['long'][7] = "juli";
$GLOBALS['LANG']['GENERAL']['month']['long'][8] = "augusti";
$GLOBALS['LANG']['GENERAL']['month']['long'][9] = "september";
$GLOBALS['LANG']['GENERAL']['month']['long'][10] = "oktober";
$GLOBALS['LANG']['GENERAL']['month']['long'][11] = "november";
$GLOBALS['LANG']['GENERAL']['month']['long'][12] = "december";

// ERROR

$GLOBALS['LANG']['ERROR']['db_error_prefix'] = "Något har blivit fel med databasen. Kontakta din systemadministratör för att få hjälp med att lösa problemet.";
//$GLOBALS['LANG']['ERROR']['table_data_missing']="Missing TABLE data in file.";
//$GLOBALS['LANG']['ERROR']['table_info_missing']="Missing TABLE info in config.";
$GLOBALS['LANG']['ERROR']['table_data_missing'] = "Tabell data saknas från filen. Skapa filen i modulens undermapp. Gå till <a href=\"https://www.hbook.se/_dev/createTableData.php\">https://www.hbook.se/_dev/createTableData.php</a>";
$GLOBALS['LANG']['ERROR']['table_info_missing'] = "Tabell information saknas från konfigurationen.";

$GLOBALS['LANG']['ERROR']['ajax'] = "Ett fel har uppstått";

// UPLOAD
$GLOBALS['LANG']['CONFIG']['upload_success'] = "Filen har sparats";
$GLOBALS['LANG']['CONFIG']['upload_success_version'] = "Filen fanns redan. Lägger till filen med versionsnummer %s";
$GLOBALS['LANG']['CONFIG']['upload_error'] = "Något gick fel när filen skulle sparas";
$GLOBALS['LANG']['CONFIG']['upload_error_prefix'] = "Filen har fel prefix och är inte godkänd att ladda upp";


// SETTINGS
$GLOBALS['LANG']['install']['CREATE']       = 'Create new tables';
$GLOBALS['LANG']['install']['ALTER_ADD']    = 'Add new columns';
$GLOBALS['LANG']['install']['ALTER_CHANGE'] = 'Change existing columns';
$GLOBALS['LANG']['install']['ALTER_DROP']   = 'Drop existing columns';
$GLOBALS['LANG']['install']['DROP']         = 'Drop existing tables';

// PAGE
$GLOBALS['LANG']['page']['id'] = array('ID', '');
$GLOBALS['LANG']['page']['pid'] = array('Förälder', 'Detta val är föräldern till denna sida. Ange INGET för ROOT');
$GLOBALS['LANG']['page']['src'] = array('Länk', 'Här anges länkadress till sidan');
$GLOBALS['LANG']['page']['label'] = array('Etikett', 'Här anges sidans namn som syns i menyerna');
$GLOBALS['LANG']['page']['isstartpage'] = array('Är startsida', 'Kryssa i om sidan skall vara startsida');
$GLOBALS['LANG']['page']['jumpTo'] = array('Vidarebefodra till', 'Här väljer man om man vill vidarebefodra sidan till en annan sida');
$GLOBALS['LANG']['page']['companyid'] = array('Företags id', '');
$GLOBALS['LANG']['page']['deleted'] = array('Raderad', '');
$GLOBALS['LANG']['page']['ordning'] = array('Ordning', '');
$GLOBALS['LANG']['page']['visible'] = array('Synlig', '');

// Mail
$GLOBALS['LANG']['mail']['to'] = "Mottagare har ej angetts!";
$GLOBALS['LANG']['mail']['subject'] = "Rubrik på meddelandet har inte angetts!";
$GLOBALS['LANG']['mail']['msg'] = "Meddelande har ej angetts!";
$GLOBALS['LANG']['mail']['not_sent'] = "Meddelandet har inte skickat!";
$GLOBALS['LANG']['mail']['sent'] = "Meddelandet har skickats!";

// Settings
$GLOBALS['LANG']['system']['settings']['pdf_footer'] = array("PDF Footer mall:", "Vilken footer skall användas till detta företag vid skapande av PDF:er.");
$GLOBALS['LANG']['system']['settings']['default_group'] = array('Standard grupp för användare', 'Ange vilken grupp som alltid skall väljas vid nya användare.');


// LOGIN/SIGNUP/LOST PASSWORD
$GLOBALS['LANG']['login']['username'] = "Användarnamn";
$GLOBALS['LANG']['login']['password'] = "Lösenord";
$GLOBALS['LANG']['login']['password_new'] = "Nytt lösenord";
$GLOBALS['LANG']['login']['password2'] = "Bekräfta lösenord";
$GLOBALS['LANG']['login']['email'] = "Epost";

$GLOBALS['LANG']['login']['btnLogin'] = "Login";
$GLOBALS['LANG']['login']['btnSignup'] = "Sign up";
$GLOBALS['LANG']['login']['btnLostpassword'] = "Ask for password";
$GLOBALS['LANG']['login']['btnChangepassword'] = "Change password";

$GLOBALS['LANG']['login']['frmLogin'] = "LOGIN";
$GLOBALS['LANG']['login']['frmSignup'] = "SIGN UP";
$GLOBALS['LANG']['login']['frmLostpassword'] = "LOST PASSWORD";
$GLOBALS['LANG']['login']['frmChangepassword'] = "CHANGE PASSWORD";

$GLOBALS['LANG']['login']['mathAdd'] = "Räkna ut summan av %s adderat med %s";
$GLOBALS['LANG']['login']['mathSub'] = "Räkna ut differensen av %s subtraherat med %s";
$GLOBALS['LANG']['login']['math'] = "Skriv summan här";
// Success
$GLOBALS['LANG']['login']['signup_success'] = "Du har registrerat dig.";
$GLOBALS['LANG']['login']['password_success'] = "Ditt lösenord har ändrats";

$GLOBALS['LANG']['login']['lostpassword_success'] = "Om epostadressen finns i vårt register kommer ett mejl med information hur du återställer ditt lösenord skickas.";

// Errors
$GLOBALS['LANG']['login']['error_empty'] = "Något fält är inte ifyllt korrekt";
$GLOBALS['LANG']['login']['error_email'] = "Epostadressen är ogiltig";
$GLOBALS['LANG']['login']['error_password'] = "Lösenordet är felaktigt. Antingen är det kortare än 8 tecken, innehåller felaktiga tecken eller så stämmer inte lösenorden överens med varandra.";
$GLOBALS['LANG']['login']['error_email_exist'] = "Epostadressen finns redan i våra register. Har du glömt ditt lösenord? Välj i så fall att hämta ett nytt.";
$GLOBALS['LANG']['login']['error_user_exist'] = "Användarnamnet är redan registrerat. Välj ett annat användarnamn.";
$GLOBALS['LANG']['login']['error_math'] = "Beräkningen är felaktig";

// LOGIN/SIGNUP/LOST EMAIL
$GLOBALS['LANG']['login']['email_retrive_subject'] = "Återställning av lösenord";
$GLOBALS['LANG']['login']['email_retrive'] = "Hej!\n För att återställa ditt lösenord skall du klicka in på denna länk (om det inte går får du kopiera den till din webbläsare och köra den direkt istället):\n\n <a href=\"%s\">%s</a>\n\n När du kommer in på denna länk så får du ange ditt nya lösenord.\nKoden är aktiv i 1 timme.\n\n Lycka till\n \n";
$GLOBALS['LANG']['login']['email_retrive_success'] = "Hej!\n Lösenordet på ditt konto har blivit ändrat.\n Om du inte har ändrat detta bör du snarast försöka åtgärda detta.\n";

// CONFIG FILE
$GLOBALS['LANG']['CONFIG']['www-root'] = "Hemsidans root adress inkl. https://";
$GLOBALS['LANG']['CONFIG']['title'] = "Hemsidans namn";
$GLOBALS['LANG']['CONFIG']['language'] = "Hemsidans standardspråk. Standard är en";
$GLOBALS['LANG']['CONFIG']['displayErrors'] = "DEBUG: Visa fel för användaren";
$GLOBALS['LANG']['CONFIG']['displayDebug'] = "DEBUG: Visa debug data för användaren";
$GLOBALS['LANG']['CONFIG']['dbUser'] = "DATABAS: Användarnamn";
$GLOBALS['LANG']['CONFIG']['dbPass'] = "DATABAS: Lösenord";
$GLOBALS['LANG']['CONFIG']['dbName'] = "DATABAS: Namn";
$GLOBALS['LANG']['CONFIG']['dbHost'] = "DATABAS: Server";
$GLOBALS['LANG']['CONFIG']['dbPrefix'] = "DATABAS: Prefix";
$GLOBALS['LANG']['CONFIG']['template'] = "Hemsidans design mall";
$GLOBALS['LANG']['CONFIG']['salt'] = "Ett hemligt krypteringsord för sidan. OBS! Denna får aldrig ändras efter uppstart.";
$GLOBALS['LANG']['CONFIG']['noExecSql'] = "DEBUG: Tillåt inte att databasen ändras";
$GLOBALS['LANG']['CONFIG']['TheCompanyPage'] = "Företagssida (används denna?)";
$GLOBALS['LANG']['CONFIG']['public_tables'] = "Vilka tabeller som är publika";
$GLOBALS['LANG']['CONFIG']['install_password'] = "Lösenordet för installation";
$GLOBALS['LANG']['CONFIG']['allow_install'] = "INSTALL: Tillåt installation. Är du klar med hemsidan skall denna sättas till 0. (används denna?)";
$GLOBALS['LANG']['CONFIG']['from_email'] = "EPOST: Från adress";
$GLOBALS['LANG']['CONFIG']['allow_company'] = "Tillåta att hemsidan kan ha flera företag";
$GLOBALS['LANG']['CONFIG']['smtp_host'] = "EPOST: Utgående server";
$GLOBALS['LANG']['CONFIG']['smtp_encrypt'] = "EPOST: Utgående servers kryptering";
$GLOBALS['LANG']['CONFIG']['smtp_port'] = "EPOST: Utgående servers port";
$GLOBALS['LANG']['CONFIG']['smtp_user'] = "EPOST: Utgående servers användarnamn";
$GLOBALS['LANG']['CONFIG']['smtp_pass'] = "EPOST: Utgående servers lösenord";
$GLOBALS['LANG']['CONFIG']['smtp_mail'] = "EPOST: Utgående servers epostadress";
$GLOBALS['LANG']['CONFIG']['ftp_host'] = "FTP: Server";
$GLOBALS['LANG']['CONFIG']['ftp_port'] = "FTP: Port";
$GLOBALS['LANG']['CONFIG']['ftp_user'] = "FTP: Användarnamn";
$GLOBALS['LANG']['CONFIG']['ftp_pass'] = "FTP: Lösenord";
$GLOBALS['LANG']['CONFIG']['UpdateServer'] = "Uppdateringsserver för systemet";
$GLOBALS['LANG']['CONFIG']['allow_signup'] = "Tillåt registrering på hemsidan";
$GLOBALS['LANG']['CONFIG']['allow_lostpassword'] = "Tillåt att använda förlorat lösenord";
$GLOBALS['LANG']['CONFIG']['ftp_remotepath'] = "FTP: Servers fjärrmapp till webb root";
$GLOBALS['LANG']['CONFIG']['uploadDir'] = "Mapp för att ladda upp filer";
$GLOBALS['LANG']['CONFIG']['dbPrefix_private'] = "DATABAS: Privat prefix. (Används bara vid ETT företag)";
