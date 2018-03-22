<?php
namespace indexer;

require_once( 'indexer/helper.inc.php' );


function storeFile( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {
  global $db, $config, $update, $session;

  $canread = true;

  if( !is_file( "{$basepath}/{$fullpath}" )) {
    log( $sessionid, $fileid, 'error', "not a file" );
    return;
   }

  $localcopy = null;
  if( $localpath )
  {
   $md5 = md5( "{$sessionid}:{$fullpath}" );
   $localcopy = $md5{0}.'/'.$md5{1}.'/'.substr( $md5, 2 );

   $src = fopen( "{$basepath}/{$fullpath}", 'r' );
   if( $src === false ) {
     log( $sessionid, $fileid, 'error', 'cannot original open file' );
     return;
   }
     $dest = fopen( "{$localpath}/{$localcopy}", 'w' );
     if( $dest === false ) {
       fclose( $src );
       log( $sessionid, $fileid, 'error', 'cannot create cache file' );
       return;
     }

     $bytes = stream_copy_to_stream($src, $dest);
     fclose( $src );
     fclose( $dest );
     if( $bytes === false ) {
       unlink( "{$localpath}/{$localcopy}" );
       log( $sessionid, $fileid, 'error', 'stream to stream copy error' );
       return;
     }
  /*
     if( !$bytes ) {
         unlink( "{$localpath}/{$localcopy}" );
       $localcopy = null;
       $canread = false;
     }
  */
     chmod( "{$localpath}/{$localcopy}", 0644 );
   }

  $cmd = $config['sha256']." -b "._escapeshellarg( "{$basepath}/{$fullpath}" );
  $sha256 = null;
  if( $canread ) {
   $sha256 = shell_exec( $cmd );
   if( preg_match( "/^[^0-9a-f]*([0-9a-f]+) .*/", $sha256, $matches ))
   {
      $sha256 = $matches[1];
      if( strlen( $sha256 ) > 64 ) $sha256 = null;
   }
   else {
     $sha256 = null;
   }
    if( $sha256 == null ) {
      log( $sessionid, $fileid, 'error', 'error generating sha256 checksum' );
      return;
     }
  }

  $sql = "UPDATE `file` SET
   ext=".$db->qstr( pathinfo( $this->fullpath, PATHINFO_EXTENSION ) )."
   , sha256=".$db->qstr( $sha256 )."
   , localcopy=".$db->qstr( $localcopy )."
   , readstate=".$db->qstr( 'ok' )."
   , filetype=".$db->qstr( 'file')."
   WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $db->Execute( $sql );

}
 ?>
