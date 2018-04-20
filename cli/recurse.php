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

if( $argc < 2 ) die( "{$argv[0]} <sessionid|groupname>\n" );
if( $argc >= 3 ) $update = ( strtolower( $argv[2]) == 'update' );

$counter = 0;

require_once( 'indexer/helper.inc.php' );
include( 'indexer/recurse_link.inc.php' );
//include( 'indexer/recurse_dir.inc.php' );
include( 'indexer/recurse_file.inc.php' );
include( 'indexer/recurse_other.inc.php' );

function storeDir( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

  global $db, $update, $session;

  echo "storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level )\n";

  if( !is_dir( "{$basepath}/{$fullpath}" )) {
    log( 'recurse.php',  $sessionid, $fileid, 'error', "not a directory" );
    return;
  }

  $sql = "UPDATE `file` SET
    mtime=NOW()
    , readstate=".$db->qstr( 'ok' )."
    , filetype=".$db->qstr( 'dir')."
    WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $db->Execute( $sql );

  recurse( $sessionid, $basepath, $localpath, $fullpath, $fileid, $level+1 );
}


function recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )
{
   global $update, $counter, $fscharset, $session, $db;

   echo "recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )\n";

   $d = opendir( "{$basepath}/{$path}" );
   while( $file = readdir( $d ))
   {

		if( $file == '.' || $file == '..' ) continue;

    //$file = iconv($fscharset, "UTF-8", $file);
    $fullpath = "{$path}/{$file}";
		$fullpath = trim( $fullpath, '/' );

		echo "#".($counter++)." {$sessionid}: {$basepath} // {$file} // {$fullpath} ----\n";


    $fileid = null;

    // check for database
    $sql = "SELECT fileid, readstate, filetype FROM file WHERE sessionid={$sessionid} AND path=".$db->qstr( $path )." AND name=".$db->qstr( $file ) ;
    $row = $db->GetRow( $sql );
    if( count( $row )) {
      $fileid = $row['fileid'];
      // if entry exists but is not ok, then delete it and retry
      if(( $row['readstate'] == 'ok' ||  $row['readstate'] == 'skip' ) && $row['filetype'] != 'dir' ) {
        echo "   --- skipping\n";
        continue;
      }
    }

    // get status and permissions.
    $strStat = doStat( "{$basepath}/{$fullpath}", $stat, $statError );

    // if fileid is set, we must not create a new entry...
    if( $fileid ) {
      $sql = "UPDATE `file` SET
        parentid={$parentid}
        , name=".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $file ))."
        , path=".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path ))."
        , `level`={$level}
        , `filesize`=".($statError ? 0 : intval($stat['size']))."
        , `filectime`='".date( "Y-m-d H:i:s", $stat['ctime'] )."'
        , `filemtime`='".date( "Y-m-d H:i:s", $stat['mtime'] )."'
        , `fileatime`='".date( "Y-m-d H:i:s", $stat['atime'] )."'
        , `stat`=".$db->qstr( $strStat )."
        , readstate=".$db->qstr( $statError ? 'error':'start' )."
        WHERE sessionid={$sessionid} AND fileid={$fileid}";

      $db->Execute( $sql );
    }
    else {
      // insert initial record. continue to next file on stat error
      $sql = "INSERT INTO `file` (sessionid
        , parentid
        , name
        , path
        , `level`
        , `filesize`
        , `filectime`
        , `filemtime`
        , `fileatime`
        , `stat`
        , readstate )
        VALUES( {$sessionid}
          , {$parentid}
          , ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $file ))."
          , ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path ))."
          , {$level}
          , ".($statError ? 0 : intval($stat['size']))."
          , '".date( "Y-m-d H:i:s", $stat['ctime'] )."'
          , '".date( "Y-m-d H:i:s", $stat['mtime'] )."'
          , '".date( "Y-m-d H:i:s", $stat['atime'] )."'
          , ".$db->qstr( $strStat )."
          , ".$db->qstr( $statError ? 'error':'start' )."
          )";

      $db->Execute( $sql );
      $fileid = $db->Insert_ID();
    }

    // logentry on stat error
    if( $statError ) {
      log( 'recurse.php',  $sessionid, $fileid, 'error', 'cannot stat file' );
      continue;
    }
    if( is_link( "{$basepath}/{$fullpath}" ))
		{
			echo "link: {$basepath}/{$fullpath} --> {$target}\n";
			storeLink( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		elseif( is_dir( "{$basepath}/{$fullpath}" ))
		{
			echo "dir: {$basepath}/{$fullpath}\n";
      storeDir( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		elseif( is_file( "{$basepath}/{$fullpath}" ))
		{
			echo "file: {$basepath}/{$fullpath}\n";
      storeFile( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		else
		{
		   echo "other: {$basepath}/{$fullpath}\n";
       storeOther( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
   }
   closedir( $d );
}

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
  $fscharset = $row['fscharset'];
  //print_r( $session );
  $mount = null;
  $umount = null;

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

	recurse( $row['sessionid'], $mountpoint, $localpath, '', 0, 0 );

  log( 'recurse.php', $row['sessionid'], null, 'info', "session finished" );


  if( $mount ) {
    echo $umount."\n";
    passthru( $umount );

  }
}
?>
