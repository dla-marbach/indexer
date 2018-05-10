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
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND sessionid=".$sessionid;
}
elseif( preg_match( '/^b[0-9]+$/', $p2 ))
{
	$bestandid = intval( substr( $p2, 1 ));
	$sql = "SELECT * FROM session WHERE `ignore`=0 AND bestandid=".$bestandid;
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
  $session = $row;
	$bestandid = $session['bestandid'];
	$sessionid = $session['sessionid'];
	echo "{$session['sessionid']}:{$session['name']}\n";
  log( 'recurse_archive.php', $row['sessionid'], null, 'info', 'start unpacking session' );

  $sql = "SELECT f.*, isi.id AS pronom, ah.name AS hname, ah.unpack, ah.remove
    FROM info_archive ia, archive_heuristic ah,
    `file` f
		LEFT JOIN info_nsrl ins ON (f.sessionid=ins.sessionid AND f.fileid=ins.fileid)
    LEFT JOIN info_siegfried isi ON (f.sessionid=isi.sessionid AND f.fileid=isi.fileid)
    WHERE f.sessionid=ia.sessionid
      AND f.fileid=ia.fileid
      AND ia.archive_heuristicid=ah.archive_heuristicid
      AND ins.ProductCode IS NULL
      AND f.sessionid={$row['sessionid']} AND f.filetype=".$db->qstr( 'archive' );
	echo "{$sql}\n";
  $rs2 = $db->Execute( $sql );
  foreach( $rs2 as $row2 ) {

		if($row2['pronom'] == "x-fmt/412" ) continue;

    $file = $row2;
		$fileid = $file['fileid'];
  	$basepath = $session['basepath'];
    $localpath = $session['localpath'];
    $mountpoint = $session['mountpoint'];

    $datapath = "{$localpath}/{$file['localcopy']}";
    $unpackPath = $localpath.'/archive/'.$file['fileid'];
    if( !is_dir( $unpackPath )) mkdir( $unpackPath, 0755, true );

    $unpack = $file['unpack'];
    $remove = $file['remove'];
    $hardlink = false;
		echo "#{$bestandid}.{$sessionid}.{$fileid}\n";

		if( !$update ) {
			$sql = "SELECT COUNT(*) FROM file WHERE archiveid={$fileid}";
			//echo "{$sql}\n";
			$num = intval( $db->GetOne( $sql ));
			if( $num ) {
				echo "[{$fileid}] - already ingested\n";
				continue;
			}
		}

    if( $unpack != null ) {
      $unpack = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($unpackPath) ), $unpack );
      if( $remove ) $remove = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($unpackPath) ), $remove );
      if( $remove && is_mounted( $unpackPath )){
        echo $remove."\n";
        passthru( $remove );
        if( is_mounted( $unpackPath )){
          log( 'recurse_archive.php', $row['sessionid'], $fileid, 'error', "[{$fileid}] cannot umount {$unpackPath}" );
          die( "[{$fileid}] cannot umount {$unpackPath}");
        }
      }
      echo $unpack."\n";
      passthru( $unpack );
      if( $remove && !is_mounted( $unpackPath )) {
        log( 'recurse_archive.php', $row['sessionid'], $fileid, 'error', "[{$fileid}] cannot mount {$unpackPath}" );
        echo( "[{$fileid}] cannot mount {$unpackPath}\n");
        rmdir( $unpackPath );
        continue;
      }

      if( file_exists( $unpackPath.substr( $file['localcopy'], 3 ) ) ) {
				if( strlen($file['ext']))
					$target = $unpackPath.'/'.substr( $file['name'], 0, -strlen( $file['ext'] )-1 );
				else
					$target = $unpackPath.'/'.$file['name'];

        echo "rename: ".$unpackPath.substr( $file['localcopy'], 3 )." --> ".$target."\n";
        rename( $unpackPath.substr( $file['localcopy'], 3 ), $target );
      }
    }

		$hasFiles = false;
		$d = opendir( $unpackPath );
		while( $f = readdir( $d )) {
			if( $f == '.' || $f == '..' ) continue;
			$hasFiles = true;
			break;
		}
		closedir( $d );
		if( !$hasFiles ) {
			log( 'recurse_archive.php', $row['sessionid'], $fileid, 'error', "[{$fileid}] empty archive: {$unpackPath}" );
			echo( "[{$fileid}] empty archive: {$unpackPath}\n");
			rmdir( $unpackPath );
			continue;
		}

    $s1 = stat( $localpath );
    $s2 = stat( $unpackPath );
    if( is_array( $s1 ) && is_array( $s2 )) {
      if( $s1['dev'] == $s2['dev'] ) $hardlink = true;
    }

    echo "hardlink: {$hardlink}\n";

    $recurse = new RecurseFS($update, $session, $db, $hardlink, $unpackPath, "{$file['path']}/{$file['name']}", $localpath, $fileid);

  	$recurse->recurse( '', $fileid, $file['level']+1 );

    if( $remove ) {
      echo $remove."\n";
      passthru( $remove );
      rmdir( $unpackPath );
    }
  }

	log( 'recurse_archive.php', $row['sessionid'], null, 'info', "unpacking session finished" );

}
?>
