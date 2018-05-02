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


if( is_numeric( $argv[1] ))
{
	$sessionid = intval( $argv[1] );
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND sessionid=".$sessionid;
}
else
{
	$group = trim( $argv[1] );
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND `group`=".$db->qstr($group);
	if( $group == 'all' ) $sql = "SELECT * FROM session WHERE `ignore`=0";
}

//$row = $db->getRow( $sql );
echo "{$sql}\n";
$rs = $db->Execute( $sql );
foreach( $rs as $row )
{
  $session = $row;
  log( 'recurse.php', $row['sessionid'], null, 'info', 'session starting session' );
  $sql = "SELECT f.*, isi.id AS pronom, ah.name AS hname, ah.unpack, ah.remove
    FROM info_archive ia, archive_heuristic ah,
    `file` f LEFT JOIN info_nsrl ins ON (f.sessionid=ins.sessionid AND f.fileid=ins.fileid)
    LEFT JOIN info_siegfried isi ON (f.sessionid=isi.sessionid AND f.fileid=isi.fileid)
    WHERE f.sessionid=ia.sessionid
      AND f.fileid=ia.fileid
      AND ia.archive_heuristicid=ah.archive_heuristicid
      AND ins.status IS NULL
      AND isi.id <> ".$db->qstr("x-fmt/412")."
      AND f.sessionid={$row['sessionid']} AND f.filetype=".$db->qstr( 'archive' );
  $rs2 = $db->Execute( $sql );
  foreach( $rs2 as $row2 ) {
    $file = $row2;

  	$basepath = $session['basepath'];
    $localpath = $session['localpath'];
    $mountpoint = $session['mountpoint'];

    $datapath = "{$localpath}/{$file['localcopy']}";
    $unpackPath = $localpath.'/archive/'.$file['fileid'];
    if( !is_dir( $unpackPath )) mkdir( $unpackPath, 0755, true );

    $unpack = $file['unpack'];
    $remove = $file['remove'];
    $hardlink = false;

    if( $unpack ) {
      $unpack = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($unpackPath) ), $unpack );
      if( $remove ) $remove = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($unpackPath) ), $remove );

      if( $remove && is_mounted( $unpackPath )){
        echo $remove."\n";
        passthru( $remove );
        if( is_mounted( $unpackPath )){
          log( 'recurse.php', $row['sessionid'], null, 'error', "cannot umount {$unpackPath}" );
          die( "cannot umount {$unpackPath}");
        }
      }
      echo $unpack."\n";
      passthru( $unpack );
      if( $remove && !is_mounted( $unpackPath )) {
        log( 'recurse.php', $row['sessionid'], null, 'error', "cannot mount {$unpackPath}" );
        echo( "cannot mount {$unpackPath}\n");
        rmdir( $unpackPath );
        continue;
      }

      if( file_exists( $unpackPath.substr( $file['localcopy'], 3 ) ) ) {
        echo "rename: ".$unpackPath.substr( $file['localcopy'], 3 )." --> ".$unpackPath.'/'.$file['name']."\n";
        rename( $unpackPath.substr( $file['localcopy'], 3 ), $unpackPath.'/'.$file['name'] );
      }
    }

    $s1 = stat( $localpath );
    $s2 = stat( $unpackPath );
    if( is_array( $s1 ) && is_array( $s2 )) {
      if( $s1['dev'] == $s2['dev'] ) $hardlink = true;
    }

    echo "hardlink: {$hardlink}\n";

    $recurse = new RecurseFS($update, $session, $db, $hardlink, $unpackPath, "{$file['path']}/{$file['name']}", $localpath);

  	$recurse->recurse( '', $file['fileid'], $file['level']+1 );

    log( 'recurse.php', $row['sessionid'], null, 'info', "session finished" );


    if( $remove ) {
      echo $remove."\n";
      passthru( $remove );
      rmdir( $unpackPath );
    }
  }

}
?>
