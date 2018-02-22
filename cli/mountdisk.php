<?php

namespace indexer;

require_once( 'config.inc.php' );
include( 'db.inc.php' );


if( $argc < 2 ) die( "{$argv[0]} <sessionid> [umount]\n" );

$sessionid = intval( $argv[1] );
$umount = @trim( strtolower( $argv[2] )) == 'umount';
$sql = "SELECT * FROM session WHERE sessionid=".$sessionid;
$row = $db->getRow( $sql );
if( !$row ) die( "session {$sessionid} not found" );

switch( $row['group']) {
  case 'od':
  default:
    $type = 'iso9660';
    $ext = '.iso';
}

$basepath = $row['basepath'];
$imagepath = str_replace( '-mounted', '', $basepath )."/{$row['name']}{$ext}";

$cmd = "mkdirhier ".escapeshellarg( $basepath );
echo "{$cmd}\n";
exec( $cmd );

if( $umount ) $cmd = "umount ".escapeshellarg( $basepath );
else $cmd = "mount -t ".escapeshellarg( $type )." -o iocharset=iso8859-1 ".escapeshellarg( $imagepath  )." ".escapeshellarg( $basepath )."";
echo "{$cmd}\n";
exec( $cmd );
