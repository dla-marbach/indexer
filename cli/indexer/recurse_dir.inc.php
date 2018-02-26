<?php

global $db, $update, $session;

echo "storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )\n";

if( !is_dir( "{$basepath}/{$fullpath}" )) {
  log( $sessionid, $fileid, , 'error', "not a directory" );
  return;
}

$sql = "UPDATE `file` SET
  readstate=".$db->qstr( 'ok' )."
  , filetype=".$db->qstr( 'dir')."
  WHERE sessionid={$sessionid} AND fileid={$fileid}";
$db->Execute( $sql );

recurse( $sessionid, $basepath, $localpath, $fullpath, $fileid, $level+1 );

 ?>
