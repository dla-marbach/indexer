<?php
namespace indexer;

require_once( 'indexer/helper.inc.php' );

function storeLink( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

  global $db, $update, $session;

  if( !is_link( "{$basepath}/{$fullpath}" )) {
    log( $sessionid, $fileid, 'error', 'not a symlink' );
    return;
  }

  $target = readlink( "{$basepath}/{$fullpath}" );
  if( $target === false ) {
   log( $sessionid, $fileid, 'error', "readlink failed" );
   return;
  }

  $sql = "UPDATE `file` SET
   comment=".$db->qstr( $target )."
   , readstate=".$db->qstr( $target===false ? 'error':'ok' )."
   , filetype=".$db->qstr( 'link')."
   WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $db->Execute( $sql );
}
 ?>
