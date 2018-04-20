<?php
namespace indexer;

require_once( 'indexer/helper.inc.php' );

function storeOther( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

  global $db, $update;

  $sql = "UPDATE `file` SET
    readstate=".$db->qstr( 'ok' )."
    , filetype=".$db->qstr( 'other')."
    , mtime=NOW()
    WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $db->Execute( $sql );
}
 ?>
