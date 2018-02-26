<?php

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
 target=".$db->qstr( $target ).",
 , readstate=".$db->qstr( $target===false ? 'error':'ok' )."
 , filetype=".$db->qstr( 'link')."
 WHERE sessionid={$sessionid} AND fileid={$fileid}";
$db->Execute( $sql );

 ?>
