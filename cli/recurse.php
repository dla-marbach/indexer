<?php
/***********************************************************
 * indexer is a software for supporting the review process of unstructured data
 *
 * Copyright © 2012-2018 Juergen Enge (juergen@info-age.net)
 * FHNW Academy of Art and Design, Basel
 * Deutsches Literaturarchiv Marbach
 * Hochschule für Angewandte Wissenschaft und Kunst Hildesheim/Holzminden/Göttingen
 *
 * indexer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * indexer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with indexer.  If not, see <http://www.gnu.org/licenses/>.
 ***********************************************************/
namespace indexer;

require_once( 'config.inc.php' );
include( 'db.inc.php' );

$update = false;
$hardlink = false;

if( $argc < 2 ) die( "{$argv[0]} <sessionid|groupname>\n" );
if( $argc >= 3 ) $update = ( strtolower( $argv[2]) == 'update' );

$counter = 0;

require_once( 'indexer/helper.inc.php' );
require_once( 'indexer/recurseFS.class.php' );


$p2 = strtolower(trim( $argv[1] ));
if( preg_match( '/^[0-9]+$/', $p2 ))
{
	$sessionid = intval( $p2 );
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND `sessionid`={$sessionid}";
}
elseif( preg_match( '/^b[0-9]+$/', $p2 ))
{
	$bestandid = intval( substr( $p2, 1 ));
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND `bestandid`={$bestandid}";
}
else
{
	$group = $p2;
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND `group`=".$db->qstr($group);
	if( $group == 'all' ) $sql = "SELECT * FROM session WHERE `ignore`=0";
}

//$row = $db->getRow( $sql );
echo "{$sql}\n";
$rs = $db->Execute( $sql );
foreach( $rs as $row )
{
  log( 'recurse.php', $row['sessionid'], null, 'info', 'session starting session' );
  $sql = "SELECT COUNT(*) FROM file WHERE sessionid={$row['sessionid']}";
  $num = intval($db->GetOne( $sql ));
  if( $num && !$update ){
    log( 'recurse.php', $row['sessionid'], null, 'info', 'skipping session: '.$sql );
    continue;
  }
  $session = $row;

	$basepath = $row['basepath'];
  $localpath = $row['localpath'];
  $datapath = $row['datapath'];
  $mountpoint = $row['mountpoint'];

  $mount = null;
  $umount = null;
  $hardlink = false;
  if( !$mountpoint || strlen( $mountpoint ) < 3 ) {
    log( 'recurse.php',  $row['sessionid'], null, 'error', "no mountpoint" );
    continue;
  }

  if( $row['mount'] ) {
    $mount = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($mountpoint) ), $row['mount'] );
    $umount = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($mountpoint) ), $row['umount'] );

    if( is_mounted( $mountpoint )){
      echo $umount."\n";
      passthru( $umount );
      if( is_mounted( $mountpoint )){
        log( 'recurse.php', $row['sessionid'], null, 'error', "cannot umount {$mountpoint}" );
        die( "cannot umount {$mountpoint}");
      }
    }
    echo $mount."\n";
    passthru( $mount );
    if( !is_mounted( $mountpoint )){
      log( 'recurse.php', $row['sessionid'], null, 'error', "cannot mount {$datapath} to {$mountpoint}" );
      die( "cannot mount {$datapath} to {$mountpoint}");
    }

  }
  elseif( intval($row['hardlink']) > 0 ) {
    $s1 = stat( $localpath );
    $s2 = stat( $mountpoint );
    if( is_array( $s1 ) && is_array( $s2 )) {
      if( $s1['dev'] == $s2['dev'] ) $hardlink = true;
    }
  }

  $recurse = new RecurseFS($update, $session, $db, $hardlink, $mountpoint, '', $localpath);

	$recurse->recurse( '', 0, 0 );

  log( 'recurse.php', $row['sessionid'], null, 'info', "session finished" );


  if( $mount ) {
    echo $umount."\n";
    passthru( $umount );

  }
}
?>
