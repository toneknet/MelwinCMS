<?php
/**
Name: Config class
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles the config file
Version: 1.0.1
Type: Library
*/
class config
{
    /**
     * Sets the config and save it
     *
     * @param string $key The key to save the value to
     * @param string $value The Value to set
     * @return void
     */
    static function setConfig($key = null,$value = null)
    {
        if ( is_null($key) || is_null($value) ) return false;
        $GLOBALS['CONFIG'][$key] = $value;
        return self::saveConfig();
    }

    /**
     * Gets the config value
     *
     * @param string $key The key to get value from
     * @return void
     */
    static function getConfig($key) {
        return $GLOBALS['CONFIG'][$key] ?? NULL;
    }

    static function saveConfig() {
        $output = "";
        foreach ($GLOBALS['CONFIG'] as $key => $value) {
            $header = str_pad("\$GLOBALS['CONFIG']['". $key . "']",45);
            $output.= "{$header} = \"{$value}\";\n";
        }
        // Selects between ftp or ordinary mode
        if($GLOBALS['CONFIG']['ftp_host'] !=""){
            $ftp_conn = ftp_connect($GLOBALS['CONFIG']['ftp_host'], $GLOBALS['CONFIG']['ftp_port'], 5);
            if (@ftp_login($ftp_conn, $GLOBALS['CONFIG']['ftp_user'], $GLOBALS['CONFIG']['ftp_pass'])) {
                $fp = fopen('php://temp', 'r+');
                fwrite($fp, "<?php \n".$output);
                rewind($fp);
                if(ftp_fput($ftp_conn, $GLOBALS['CONFIG']['ftp_remotepath'].'system/configs/localconfig.inc.php', $fp, FTP_ASCII)) {
                    return true;
                }
                ftp_close($ftp_conn);
            }
        } else {
            $a = file_put_contents(SYSPATH."configs/localconfig.inc.php","<?php \n".$output);
            return true;
        }
        return false;
    }
}