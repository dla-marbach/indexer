<?php

require_once( 'config.inc.php' );
require_once( $indexerPath.'/db.inc.php' );

$id = $_REQUEST['id'];

list( $bestandid, $sessionid, $fileid ) = explode( '.', $id );

$sql = "SELECT s.localpath, f.*, gi.mimetype FROM session s, file f, info_gvfs_info gi
  WHERE f.sessionid=gi.sessionid
    AND f.fileid=gi.fileid
    AND f.sessionid=s.sessionid
    AND f.sessionid={$sessionid}
    AND f.fileid={$fileid}";

$row = $db->getRow( $sql );
if( !is_array( $row ) && !count( $row )) {
  header('HTTP/1.0 404 file not found');
  echo 'could not find file with id '.$id;
  die();
}
if( $row['filetype'] != 'file' ) {
  header('HTTP/1.0 403 no valid file');
  echo 'no file: id '.$id;
  echo $sql;
  die();
}
$fname = "{$row['localpath']}{$row['localcopy']}";
if( !file_exists( $fname )) {
  header('HTTP/1.0 404 file not found');
  echo "#{$id}: could not find {$fname}";
  die();
}

header( "Content-type: {$row['mimetype']}" );
readfile( $fname );
