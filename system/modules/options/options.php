<?PHP
/*
Name: Options ??
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Dont know yet
Version: 0.1
Type: Module
*/
class options {
  private $dbFormSettings;
  private $allowEdit = false;

  function __construct($arrModuleSettings) {
      // Fixa Access här!
      if ($arrModuleSettings['own_access']['edit']) { $this->allowEdit = true;}

      print "<pre>";
      print __CLASS__ ."\n";
      print_r($arrModuleSettings['own_access']);
      print "</pre>";


    }
}
