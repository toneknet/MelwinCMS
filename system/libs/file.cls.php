<?php
/**
Name: File
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Handles files in system. Use ftp(not implemented yet!) if needed with upload.
Version: 0.1
Type: Library
 */
/**
 * Description:
 * Handles files in system. Use ftp(not implemented yet!) if needed with upload.
 * 
 * 
 * Features:
 * form widget?
 * 
 * Dependencies:
 * FTP?
 * Handles automatic if ftp mode is set by settings
 * 
 * 
 * Status:
 *  Upload: done
 * 
 */

class file
{

  private $uploadPath = NULL;
  private $path;

  function __construct()
  {
    // Check if is set!
    if (!isset($GLOBALS['CONFIG']['uploadDir']) || $GLOBALS['CONFIG']['uploadDir'] == "") throw new Exception($GLOBALS['LANG']['ERROR']['file_path_missing'], 1);
    $this->uploadPath = ROOTPATH . $GLOBALS['CONFIG']['uploadDir'];
    if (!is_dir($this->uploadPath)) throw new Exception($GLOBALS['LANG']['ERROR']['file_path_missing'], 1);
    // Check if string ends with /
    $chk = substr($this->uploadPath,-1,1);
    if ($chk != "/" && $chk != "\\") $this->uploadPath.="/";
  }

  /**
   * List dir. using upload/ as ROOT for function. NOT ALLOWED to go further!!!
   *
   * @return void
   */
  function listDir($path)
  {
    throw new Exception("Value must be 1 or below");
  }

  function getFile() {}

  function downloadFile($id) {
    // Check if id
    if (preg_match('/^[a-z0-9]+$/', $id) !== 1) return false;
    // Check if file exist
    $db = db::getInstance();
    $file = $db->o_get_row('files',"SELECT * FROM [table] where uniqueid='{$id}' and deleted=0");
    // pre($file);
    if (is_array($file)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file['fullpath']));
      readfile($file['fullpath']);
      die();
    }




    // funct ion isValidId($idString) {
    // This regular expression matches strings that contain only lowercase letters and numbers
    // $pattern = '/^[a-z0-9]+$/';
    // if (preg_match('/^[a-z0-9]+$/', $id) !== 1) {
    //   print "TRUE";
    // } else {
    //   print "FALSE";
    // }
    // }

    // Example usage:
    // $id = "abc123";
    // echo isValidId($id) ? 'True' : 'False';  // Output: True

    // $id = "abc123!";
    // echo isValidId($id) ? 'True' : 'False';  // Output: False

    // print $ie = preg_match('/^[a-z0-9]/', $id); 
    // if ($ie == $id) { print "SAME";}
    return;

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);

    

  }

  function setdownloadFilelink($file) {
    // https://am.tonek.se//system/ajax/ajaxRequest.php?action=file&subaction=get&id=aa8414d33f1abff30cf9e7bc5b40c471268a8d177a0cadf23cb7b8ad87a9f973249805065f43acb3f794da7cd1255f3b58ef5b78a793326146449bc7b2c2157babb7ca7f67743a7a191d48a26a87d6507d17b6e139e6b70b0e942b153ef9cb0ab07632782015babae00d895c0636a4787ec14e2d27bf9a2e3099a3597c
  }
  /**
   * Creates link for download
   *
   * @param array $file the file array to use (no error detection used)
   * @return void
   */
  function createdownloadFilelink($file) {
    $output = "<a href=\"" . URLBASE . "/system/ajax/ajaxRequest.php?action=file&subaction=get&id=" . $file['uniqueid'] . "\" target=\"_blank\">" . $file['name'] . "</a>";
    return $output;
    // https://am.tonek.se//system/ajax/ajaxRequest.php?action=file&subaction=get&id=aa8414d33f1abff30cf9e7bc5b40c471268a8d177a0cadf23cb7b8ad87a9f973249805065f43acb3f794da7cd1255f3b58ef5b78a793326146449bc7b2c2157babb7ca7f67743a7a191d48a26a87d6507d17b6e139e6b70b0e942b153ef9cb0ab07632782015babae00d895c0636a4787ec14e2d27bf9a2e3099a3597c
  }

  /**
   * Set specific subpath in upload directory
   *
   * @param string $path as pathname
   * @return void
   */
  function setPath($path) {
    // Check if empty
    if ($path == "") return;
    // Check if tempered with
    if ($path != basename($path)) return;
    // Check if dir exists, else create it
    if (!is_dir($this->uploadPath . basename($path))) {
      mkdir($this->uploadPath . basename($path));
    }
    // Sets the new path
    $this->path = basename($path);
    $chk = substr($this->path,-1,1);
    if ($chk != "/" && $chk != "\\") $this->path.="/";
  }

  /**
   * Uploads file to system. Have a functionality to revision documents with same name
   * Require form to be created with subfunction of filelist
   * on AJAX, require 'modules/system/js/file.php' and 'modules/system/ajax.file.php'.
   *
   * @return mixed Result of fileinfo as array or boolean false on failure
   */
  function uploadFile() {
    $db = db::getInstance();
    // If FILES isnt set, cancel 
    if (!isset($_FILES) || !isset($_FILES['file'])) return false;
    $uid = getUserID();
    // Check if uniqueid is unique
    $ok=false;
    $out=null;
    do {
      $uniqueid = bin2hex(random_bytes(125));
      $out = $db->o_get_all("files","SELECT * FROM [table] WHERE uniqueid='{$uniqueid}'");
          if ((is_array($out) && count($out) == 0) || !is_array($out)) $ok = true;
    } while ( $ok == false);
    // Create the paths for uploading
    $file = $_FILES['file'];
    $uploadDir = $this->uploadPath . $this->path;// . $_FILES['file']['name']
    $uploadFilePath = $uploadDir . basename($file['name']);
    $size = self::humanFileSize($file['size']);
    // Set array for output
    $fileArray = [
      'name' => basename($file['name']), 
      'uid'=> $uid, 
      'uniqueid' => $uniqueid, 
      'fullpath' => $uploadFilePath,
      'size' => $size
    ];
    // Check if file exists, if.. add version ??
    if (is_file($uploadFilePath)) {
      $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
      $fileNameWithoutExtension = pathinfo($file['name'], PATHINFO_FILENAME);
      $version = 1;
      // Check for existing files and determine the next version number
      while (file_exists($uploadDir . $fileNameWithoutExtension . '_v' . str_pad($version, 3, '0', STR_PAD_LEFT) . '.' . $fileExtension)) {
        $version++;
      }
      // Construct the new filename with the version number
      $uploadFilename_wo_path = $fileNameWithoutExtension . '_v' . str_pad($version, 3, '0', STR_PAD_LEFT) . '.' . $fileExtension;
      $uploadFilePath = $uploadDir . $uploadFilename_wo_path;
      $fileArray['name'] = $uploadFilename_wo_path;
      $fileArray['revision'] = $version;
      $fileArray['fullpath'] = $uploadFilePath;
    }
    // Move the uploaded file to the upload directory
    if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
      // $fileArray['uploaded'] = 'now()';
      $fileArray['uploaded'] = date("Y-m-d H:i:s");
      $db->o_insert("files", $fileArray);
      $fileArray['id'] = $db->o_insert_id();
      return (array) $fileArray;
    } else {
      return false;
    }
  }

  /**
   * Helper function for human readable filesize
   *
   * @param integer $size
   * @param string $unit
   * @return void
   */
  static function humanFileSize($size, $unit="") {
    if (!is_numeric($size)) return;
    if((!$unit && $size >= 1<<30) || $unit == " GB")
        return number_format($size/(1<<30),2)." GB";
    if((!$unit && $size >= 1<<20) || $unit == " MB")
        return number_format($size/(1<<20),2)." MB";
    if((!$unit && $size >= 1<<10) || $unit == " KB")
        return number_format($size/(1<<10),2)." KB";
    return number_format($size)." B";
  }   
}
