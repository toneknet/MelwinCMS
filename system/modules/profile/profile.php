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
 * Låter användaren ändra SINA uppgifter
 * 
 * 
 * Om användaren byter epost så måste den nya epostadressen först bekräftas
 * 
 * Att byta lösenod skall ske på en egen flik
 * 
 * Hur ska det se ut för att enklast visa
 * 
 * 
 * 
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class profile {

    private $dbFormSettings;
    private $userData;

    function __construct() {

        $arrModuleSettings['table']="user";
        $table = $arrModuleSettings['table'];
        if (isset($GLOBALS['TABLE'][$table])) {
            $this->dbFormSettings = $GLOBALS['TABLE'][$table];

            // Gå igenom hela
            $this->dbFormSettings = array_merge_own($this->dbFormSettings, $arrModuleSettings);

        } else {
            setMsg("e", $GLOBALS['LANG']['ERROR']['table_data_missing']);
            return;
        }
        // $input = new input();
        // Dynamic button fix?

        createBtn(['title'=>'Profil', 'url'=> array('action' => '')],true);
        // createBtn(['title'=>'Byta Epost', 'url'=> array('action' => 'changeemail')],true);
        createBtn(['title'=>'Byta Lösenord', 'url'=>array('action' => 'changepassword')],true);
        print "<div></div>";

        $a = input::_get('action');
        if ($a == 'changeemail') {
            $this->changeEmail();
        } elseif ($a == 'changepassword') {
            $this->changePwd();
        } else {
            $this->showProfile();
        }
        
        
        // $input = new input();
        // $db = db::getInstance();
        // $auth = auth::getInstance();        
        
        // return;
        // $arrUserstuff = $this->userData = $auth->getUserAndCompany();
        
            // return;
        //         $this->dbFormSettings['form']['palette'] = "id,namn,securepass,shortdriver,email,oldpass,newpass,newpass2";
        //         $this->dbFormSettings['form']['onsubmit_callback'] = array('tbl_user','submit');


        //         if($input->get('action')=='save' && $input->post('table')==$table && $input->post('db_id')==$arrUserstuff['id']) {
        //             // Kolla referenspunkter först
        //             if ($input->post('hash')== salt( $table . date("Y-m-d") . $input->post('db_id') ) ) {
        //                 $formcatcher = new formcatcher();
        //                 $formcatcher->catchform($this->dbFormSettings);
        //             } else {
        // //                print "ERROR: In catching form";
        //                 setMsg('e', "ERROR: In catching form");
        //             }
        //         }







        //        print "<pre>";
        //        print_r($arrUserstuff);
        //        print "</pre>";

        //        $arrUser = $db->o_get_row('user',"SELECT id,namn,securepass,shortdriver,email FROM [table] WHERE id=".$arrUserstuff['id']." AND companyid=".getCompanyID());

        //            print "<pre>";
        //            print_r($this->dbFormSettings);
        //            print "</pre>";

        //
        //        print "<pre>";
        //        print_r($arrUser);
        //        print "</pre>";



        // $form = new form();
        // $form->loadData($this->dbFormSettings, $arrUserstuff['id']);
        // $form->MakeWithSettings($this->dbFormSettings);

        // print "USER!";
    }

    /**
     * Handles the change password procedure
     *
     * @return void
     */
    function changePwd() {
        print "<div></div>";
        /*
         * Gamla lösenordet
         * Nya lösenordet
         * Nya lösenordet igen
         * 
         * Kolla att den är korrekt.
         * 
         * 
         */

        if (input::_get('subaction')== "save") {
            $op = input::_post('db_oldpass');
            $np1 = input::_post('db_newpass1');
            $np2 = input::_post('db_newpass2');
            // Check if old password is valid
            if (auth::checkPassword($op)) {
                if (!login::is_password_invalid($np1,$np2)) {
                    $db = db::getInstance();
                    if ($db->o_update('user',array('securepass' => salt($np1),'pass' => ''),'id=' . auth::$userData['id'])) {
                        setMsg('s',$GLOBALS['LANG']['login']['password_success']);
                    } else {
                        setMsg('e',$GLOBALS['LANG']['login']['error_change_password']);        
                    }
                } else {
                    setMsg('e',$GLOBALS['LANG']['login']['error_password']);    
                }
            } else {
                setMsg('e',$GLOBALS['LANG']['login']['error_wrong_password']);
            }
        }

        $f = new form();
        print $f->form_begin( arrSettings:array( 'arrQuery' => array ('action' => 'changepassword', 'subaction' => 'save') ) );
        print $f->field_PASSWORD( array( 'type'     => 'varchar', 'field'     => 'oldpass' , 'caption'  => $GLOBALS['LANG']['user']['oldpass'] , 'value' => '') );
        print $f->field_PASSWORD( array( 'type'     => 'varchar', 'field'     => 'newpass1', 'caption'  => $GLOBALS['LANG']['user']['newpass'] , 'value' => '') );
        print $f->field_PASSWORD( array( 'type'     => 'varchar', 'field'     => 'newpass2', 'caption'  => $GLOBALS['LANG']['user']['newpass2'], 'value' => '') );
        print $f->form_submit();
        print $f->form_end();
    }

    function changeEmail() {
        print "<div></div>";
        print __FUNCTION__;
        if (input::_get('subaction')== "save") {
            debug();
            $oe = input::_post('db_email_old');
            $ne1 = input::_post('db_email_new1');
            $ne2 = input::_post('db_email_new2');


            
            if (auth::$userData['email'] == $oe) {
                if (!login::is_email_invalid($ne1) && $ne1 == $ne2) {
                    // Send mail to check new email to change
                    // $m = new mail();
                    // $m->sendz();
                    // $m->message = "Hej hopp";
                    // $m->subject = "Test";
                    // if ($m->send()) {
                    //     print "OK";
                    // } else {
                    //     print "FAIL";
                    // }



                } else {
                    setMsg("e","Din nya epostadress är inte korrekt eller så stämmer de inte överens");
                }

            } else {
                setMsg("e","Du har angett fel nuvarande epostadress");
            }
            pre(auth::$userData);
            print "<pre>";
            $activation_token = bin2hex(random_bytes(16));

            $activation_token_hash = hash("sha256", $activation_token);
            print $activation_token."\n";
            print $activation_token_hash."\n";
            
            print $oe."\n";
            print $ne1."\n";
            print $ne2."\n";
            print "</pre>";
            // return;
            // Kolla så att ingen annan användare på denna site använder denna epost.

            //     // Check if old password is valid
            //     if (auth::checkPassword($op)) {
            //         if (!login::is_password_invalid($np1,$np2)) {
            //             $db = db::getInstance();
            //             if ($db->o_update('user',array('securepass' => salt($np1),'pass' => ''),'id=' . auth::$userData['id'])) {
            //                 setMsg('s',$GLOBALS['LANG']['login']['password_success']);
            //             } else {
            //                 setMsg('e',$GLOBALS['LANG']['login']['error_change_password']);        
            //             }
            //         } else {
            //             setMsg('e',$GLOBALS['LANG']['login']['error_password']);    
            //         }
            //     } else {
            //         setMsg('e',$GLOBALS['LANG']['login']['error_wrong_password']);
            //     }
        }

        $f = new form();
        print $f->form_begin( arrSettings:array( 'arrQuery' => array ('action' => 'changeemail', 'subaction' => 'save') ) );
        print $f->field_INPUT( array( 'type'     => 'varchar', 'field'     => 'email_old' , 'caption'  => $GLOBALS['LANG']['user']['email_old'] , 'value' => '') );
        print $f->field_INPUT( array( 'type'     => 'varchar', 'field'     => 'email_new1', 'caption'  => $GLOBALS['LANG']['user']['email_new1'] , 'value' => '') );
        print $f->field_INPUT( array( 'type'     => 'varchar', 'field'     => 'email_new2', 'caption'  => $GLOBALS['LANG']['user']['email_new2'], 'value' => '') );
        print $f->form_submit();
        print $f->form_end();        
    }


    function showProfile() {
        // dblist?
        /*
         Vad visa?
         * Namn
         * Initialer
         * Epost
         * Special fields (sms,email,allowsms,allowemail ...)


        */
        print '
        <style>
        .layout {
            // width: 1366px;
            // height: 768px;
          
            // display: grid;
            // grid-template-rows: repeat(3, 1fr);
            // grid-template-columns: repeat(2, 1fr);
            // gap: 8px;
        }
        // .layout > * { padding:5px 10px;}
        // .layout > *:nth-child(even) {background: #CCC}
        .layout > *:nth-child(odd) {font-weight:bolder;}
        </style>

        <section class="layout">
            <div>' . $GLOBALS['LANG']['user']['namn'][0] . '</div>
            <div>' . auth::$userData['namn'] . '&nbsp;</div>
            <div>' . $GLOBALS['LANG']['user']['shortdriver'][0] . '</div>
            <div>' . auth::$userData['shortdriver'] . '&nbsp;</div>
            <div>' . $GLOBALS['LANG']['user']['email'][0] . '</div>
            <div>' . auth::$userData['email'] . '&nbsp;</div>
        </section>
      ';
    }

}
