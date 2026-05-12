<?PHP
/*
Name: Edit only
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Dont know yet
Version: 0.1
Type: Module
*/
class editonly {

      private $dbFormSettings;
      private $allowEdit = false;

      function __construct($arrModuleSettings) {
          if (!isset($arrModuleSettings['table'])) {
              setMsg("e", $GLOBALS['LANG']['ERROR']['table_info_missing']);
              return;
          } else {
              $table = $arrModuleSettings['table'];
              if (isset($GLOBALS['TABLE'][$table])) {
                  $this->dbFormSettings = $GLOBALS['TABLE'][$table];
                  // Gå igenom hela
                  $this->dbFormSettings = array_merge_own($this->dbFormSettings, $arrModuleSettings);
  /*                        print "<pre>";
                          print_r($this->dbFormSettings['fields']) ;
                          print "</pre>";*/
              } else {
                  setMsg("e", $GLOBALS['LANG']['ERROR']['table_data_missing']);
                  return;
              }
          }
/*          print "<pre>";
          print_r($arrModuleSettings) ;
          print $arrModuleSettings['list']['id'];
          print "</pre>";*/

          if (isset($arrModuleSettings['list']['id']) && intval($arrModuleSettings['list']['id'])>0) {
            $id = $arrModuleSettings['list']['id'];
          } else {
            setMsg("e", $GLOBALS['LANG']['ERROR']['editonly_id_missing']);
            return;
          }

          // Fixa Access här!
          if ($arrModuleSettings['own_access']['edit']) { $this->allowEdit = true;} else {return;}

          $input = new input();

          if($input->get('action')=='save' && $input->post('table')==$table && $this->allowEdit) {
              // Kolla referenspunkter först
              if ($input->post('hash')== salt( $table . date("Y-m-d") . $input->post('db_id') ) ) {
                  $formcatcher = new formcatcher();
                  $formcatcher->catchform($this->dbFormSettings);
              } else {
  //                print "ERROR: In catching form";
                  setMsg('e', "ERROR: In catching form (Have you remembered to activate 'id' as a field in 'listandedit'?");
              }
          }
          if ($this->allowEdit) {
              $form = new form();
              $form->loadData($this->dbFormSettings, intval($id));
              $form->MakeWithSettings($this->dbFormSettings);
          }
      }
  }
