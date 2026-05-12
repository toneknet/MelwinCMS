<?php
//
//  IMPORTANT!!!!!
//
// DON'T MAKE CHANGES IN THIS FILE
// use localconfig.inc.php
//
//
$GLOBALS['CONFIG']['www-root']          = "";
$GLOBALS['CONFIG']['title']             = "My Melwin Site";
// Locals
$GLOBALS['CONFIG']['language']          = "en";
$GLOBALS['CONFIG']['timezone']          = "Europe/Stockholm";
// ERROR Settings
$GLOBALS['CONFIG']['displayErrors']     = 0;
$GLOBALS['CONFIG']['displayDebug']      = 0;
// DB Settings
$GLOBALS['CONFIG']['dbUser']            = "";
$GLOBALS['CONFIG']['dbPass']            = "";
$GLOBALS['CONFIG']['dbName']            = "";
$GLOBALS['CONFIG']['dbHost']            = "";
$GLOBALS['CONFIG']['dbPrefix']          = "m_";
$GLOBALS['CONFIG']['public_tables']     = "company,groups,help,page,page_content,user,crontab,options,ajax";           // Denna håller vilka tabeller som är publika
// System Settings
$GLOBALS['CONFIG']['template']          = "default";
$GLOBALS['CONFIG']['salt']              = "";
$GLOBALS['CONFIG']['noExecSql']         = 0;
$GLOBALS['CONFIG']['TheCompanyPage']    = 0;
$GLOBALS['CONFIG']['install_password']  = "657bc6839f4732873d96cb31f1a71baa0a40201f88c1acd13bf9c59a08865393";
$GLOBALS['CONFIG']['uploadDir']         = "upload/";
$GLOBALS['CONFIG']['uploadType']        = ['pdf','jpg','jpeg','png','gif','bmp','svg','mp4','avi','mpeg','mp3','ogg','wav'];
$GLOBALS['CONFIG']['repoUrl']           = "https://github.com/toneknet/MelwinCMS";
$GLOBALS['CONFIG']['repoUrlAddons']     = "https://github.com/toneknet/MelwinCMS-addons";
$GLOBALS['CONFIG']['repoUrlAuth']       = "";
$GLOBALS['CONFIG']['thumbnailsize']['x']= 200;
$GLOBALS['CONFIG']['thumbnailsize']['y']= 200;
// SMTP/Mail settings
$GLOBALS['CONFIG']['smtp_host']         = "";
$GLOBALS['CONFIG']['smtp_encrypt']      = ""; // Allowed: empty = "", SSL, STARTTLS
$GLOBALS['CONFIG']['smtp_port']         = "";
$GLOBALS['CONFIG']['smtp_user']         = "";
$GLOBALS['CONFIG']['smtp_pass']         = "";
$GLOBALS['CONFIG']['smtp_mail']         = "";
$GLOBALS['CONFIG']['smtp_from_name']    = "";
$GLOBALS['CONFIG']['from_email']        = "";
$GLOBALS['CONFIG']['smtp_BCC']          = [];
// FTP settings
$GLOBALS['CONFIG']['ftp_host']          = "";
$GLOBALS['CONFIG']['ftp_port']          = "";
$GLOBALS['CONFIG']['ftp_user']          = "";
$GLOBALS['CONFIG']['ftp_pass']          = "";
// Update services
$GLOBALS['CONFIG']['UpdateServer']      = "https://data.tonek.se/version.php";
// Allow registration and lost password functions
$GLOBALS['CONFIG']['allow_signup']      = 0;
$GLOBALS['CONFIG']['allow_lostpassword']= 0;
$GLOBALS['CONFIG']['allow_install']     = 1; // Ska denna vara här? Bättre att göra EXIT i fil?
$GLOBALS['CONFIG']['allow_company']     = 0; // NYHET! Ska den användas? Tror ej det
$GLOBALS['CONFIG']['allow_log']         = 0;

$GLOBALS['CONFIG']['tpl_domain']              = "somewhere.com";
$GLOBALS['CONFIG']['tpl_email']               = "mail (at) somewhere (dot) com";