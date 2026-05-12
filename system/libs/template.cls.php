<?php
/**
Name: Template
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles templates and output the content, Now removed all stuff handling login, signup and lost password
Version: 1.1 (SYNCED)
Type: Library
*/


/**
 * Description of template
 * Denna skall man kunna lägga till fält som man vill och sedan visa dem när man
 * gör en publish på hela alltet.
 * Man måste även ha valt en template, annars så kör man med en default template
 * som är en "lätt" version.
 *
 * Om det blir 404 fel så skall man först kolla om filen 404 finns, annars visar
 * man "vanliga" layouten fast med ett fördefinerat 404 meddelande.
 *
 * TANKE!
 * Header, Page samt Footer filer?
 *
 * Ska man göra som så att man tillåter både php och tpl filer?
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class template {

    // Holds the template html
    private $template = null;

    // Checks if the class has been published, if not then autopublish
    private $published = false;

    // Holds the path to selected template
    private $path = null;

    // Holds the data
    private $data = array();

    // Holds the css
    private $_css = null;

    // Holds the js
    private $_js = null;

    // Load the template up and sets some variabels.
    /*
     * Hut kunna ladda egna templates i moduler om man inte gör denna modular?
     */
    public function __construct($template = '') {
        //var_dump($template);
        if ($template != "") {
            //
            if ( is_file ( $template)) {
                $this->template = file_get_contents( $template );
            } else {
                $this->template = $this->getTemplate($template);
                //throw new Exception("TEMPLATE: Files missing for the template to work correct!");
            }
        } else if (isset($GLOBALS['CONFIG']['template'])) {
            // Sets the path to template
            $this->path = TPLPATH . $GLOBALS['CONFIG']['template'] . "/";
            if ( is_file ( $this->path . "/index.tpl")) {
                $this->template = file_get_contents( $this->path . "/index.tpl" );
            } else {
                throw new Exception("TEMPLATE: Files missing for the template to work correct!");
            }
        } else {
            throw new Exception("TEMPLATE: No template selected");
        }
        $this->title = $GLOBALS['CONFIG']['title'];
        $this->data['header']  = "";
        $this->data['navigation']  = "";
        $this->data['main']  = "";
        $this->data['footer']  = "";
        $this->data['outside']  = "";

    }

    function __destruct() {
//        if ($GLOBALS['ERROR']) { return '';exit;}
        if ($this->published == false) {
            $this->publish();
        }
    }

    /**
     * Publish the template
     * @param bool $print false for none-print and only return
     * @return html|null returns null on $print = true else returns the html
     */
    public function publish($print = true) {
        // Endast för debug
        //        foreach ($this->data as $key => $value) {
        //            $this->data[$key]="<strong>[" . $key . "]</strong>" . $this->data[$key];
        //        }
        // print "<pre>";
        // print_r($this->data);
        // print "</pre>";

        extract($this->data);
        $css = $this->_css;
        $js = $this->_js;

        if (is_array($GLOBALS['tpl']['js'])) {
            foreach ($GLOBALS['tpl']['js'] as $valJS) {
                $js.= "<script src=\"" .URLBASE . "/" . $valJS . "?v=" .time() . "\"></script>\n";
            }
        }
        //var_dump($js);

        if (is_array($GLOBALS['tpl']['css'])) {
            foreach ($GLOBALS['tpl']['css'] as $valCSS) {
                $css.= "<link rel=\"stylesheet\" href=\"" .URLBASE . "/" . $valCSS . "?v=" .time() . "\">\n";
            }
        }


        ob_start();
        eval("?>".$this->template.""); //<?
        $tmp=ob_get_contents();
        ob_end_clean();
        $this->published = true;
        if ($print) {
            print $tmp;
            return null;
        } else {
            return $tmp;
        }
    }

    /**
     * Sets the variables to use on page dynamic here
     * @param string $name The variabel to add content to
     * @param string $content The content to add
     */
    public function __set($name, $content) {
      if (isset($this->data[$name])) {
        if (gettype($content) == 'array') {
            $this->data[$name] = $content;
        } else {
            $this->data[$name].= $content;
        }
      } else {
        $this->data[$name] = $content;
      }
    }

    public function add($name,$content) {
      // print $name;
      if (isset($this->data[$name])) {
        if (gettype($content) == 'array') {
            $this->data[$name] = $content;
        } else {
            $this->data[$name].= $content;
        }
      } else {
        $this->data[$name] = $content;
      }
    }





    /**
     * Add Javascript file
     * @param string $file The path to the file
     */
    public function js($file) {
        $this->_js.= "\t\t<script src=\"{$file}\"></script>\n";
    }

    /**
     * Add Stylesheet file
     * @param string $file The path to the file
     * @param string $media The type of media, default is ALL
     */
    public function css($file, $media = "all") {
       $this->_css.= "\t\t<link rel=\"stylesheet\" type=\"text/css\" href=\"{$file}\" media=\"{$media}\">\n";
    }

    public function loginForm() {
        throw new Exception("Error Processing Request: loginForm", 1);
    }

    public function confirmpassword() {
        throw new Exception("Error Processing Request: confirmpassword", 1);
    }

    /**
     * Ber dig att byta lösenord om valet BYT LÖSENORD VID NÄSTA INLOGGNING är
     * angett. Ska hantera både formulär och vidare...
     */
    public function changePassword() {
        $i = new input();
        $action = $i->get('action');
        if ($action=='change') {
            $p = $i->postAll();
            // Har båda fälten fyllts i och är de likadana, samt är de ok pwd?
            if ( $p['password'] == $p['password2']) {
                // Är lösenordet ok?
                if (validatePwd($p['password'])) {
                    // Är det samma lösenord som sist?
                    $db = db::getInstance();
                    $uid = getUserID();

                    $arrUser = $db->o_get_row("user","SELECT securepass, id FROM [table] WHERE deleted=0 AND id='{$uid}'");
                    $s = $arrUser['securepass'];
                    // Skapa nya hashen!
                    $newpwd = salt($p['password']);
                    if ($newpwd != $s) {
                        // Spara nya lösenordet!
                        if ($db->o_update("user",array('securepass'=>$newpwd,'changepwdnextlogin'=>0)," id=".$uid)) {
                            setMsg('s','Ditt lösenord har ändrats!');
                            $_SESSION['li']['cpl']=false;
                            return;
                        } else {
                            $alert = new alertbox();
                            $alert->showBox('error', "Något gick fel när lösenordet skulle ändras!");
                        }
                    } else {
                        $alert = new alertbox();
                        $alert->showBox('error', "Du kan inte använda samma lösenord igen!");
                    }
                } else {
                    $alert = new alertbox();
                    $alert->showBox('error', "Ditt lösenord uppfyllde inte kraven på ett lösenord!");
                }
            } else {
                $alert = new alertbox();
                $alert->showBox('error', "Lösenorden stämde inte överens!");
            }
        }
        // 20240109 Changed to new template function for loading file
        print $this->getTemplate('changepassword');
        //print file_get_contents( TPLPATH. "default/changepassword.tpl" );
    }

    public function page404() {
        showMsg();
        // 20240109 Changed to new template function for loading file
        print $this->getTemplate('404');
        //print file_get_contents( TPLPATH. "default/404.tpl" );
        $this->published = true;
        exit();
    }

    static function _page404() {
        showMsg();
        // 20240109 Changed to new template function for loading file
        // print self::getTemplate('404');

        $file = "404";
        if (isset($GLOBALS['CONFIG']['template'])) {
            // Sets the path to template
            $path = TPLPATH . $GLOBALS['CONFIG']['template'] . "/";
            if ( is_file ( $path . "/{$file}.tpl")) {
              print file_get_contents($path . "/{$file}.tpl");
            } else {
              print file_get_contents(TPLPATH. "default/{$file}.tpl");
            }
        } else {
          print file_get_contents(TPLPATH. "default/{$file}.tpl");
        }


        //print file_get_contents( TPLPATH. "default/404.tpl" );
        //self::publish();
        //self::published = true;
        die();
    }

    /**
     * Prints the header
     *
     * @return void
     */
    public function header($path = "") {
        $file = "header";
        // print $path;
        if ($path <> "") {
            $path = TPLPATH . $path; // . "/";
            // print $path;
            // print $path . "/{$file}.tpl";
            if ( is_file ( $path . "/{$file}.tpl")) {
                print file_get_contents($path . "/{$file}.tpl");
            }           
        } elseif (isset($GLOBALS['CONFIG']['template'])) {
            // Sets the path to template
            $this->path = TPLPATH . $GLOBALS['CONFIG']['template']; // . "/";
            if ( is_file ( $this->path . "/{$file}.tpl")) {
              print file_get_contents($this->path . "/{$file}.tpl");
            }
        }
        // print "<!-- HEADER -->";
    }

    /**
     * Prints the footer
     *
     * @return void
     */
    public function footer($path = "") {
        $file = "footer";
        // print $this->path;
        if ($path <> "") {
            $path = TPLPATH . $path; // . "/";
            if ( is_file ( $path . "/{$file}.tpl")) {
                print file_get_contents($path . "/{$file}.tpl");
            }           
        } elseif (isset($GLOBALS['CONFIG']['template'])) {
            // Sets the path to template
            $this->path = TPLPATH . $GLOBALS['CONFIG']['template']; // . "/";
            if ( is_file ( $this->path . "/{$file}.tpl")) {
              print file_get_contents($this->path . "/{$file}.tpl");
            }
        }
        // print $this->path;
        // print "<!-- FOOTER -->";
    }

    /**
     * Checks if template exist in template directory. If NOT, fallback to default/
     * @param  string $file     Filename excl .tpl
     * @return string           template file loaded to string
     */
    function getTemplate($file) {
      //$this->add('main', "Template to load: {$file}<br />");
      if (isset($GLOBALS['CONFIG']['template'])) {
          // Sets the path to template
          $this->path = TPLPATH . $GLOBALS['CONFIG']['template'] . "/";
          if ( is_file ( $this->path . "/{$file}.tpl")) {
            return file_get_contents($this->path . "/{$file}.tpl");
          } else {
            return file_get_contents(TPLPATH. "default/{$file}.tpl");
          }
      } else {
        return file_get_contents(TPLPATH. "default/{$file}.tpl");
      }
    }

    function setTemplate($file) {
      $this->template = $this->getTemplate($file);
    }
}
