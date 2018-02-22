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


if( $argc < 2 ) die( "{$argv[0]} <sessionid|groupname>\n" );
$update = false;

$counter = 0;

function _escapeshellarg( $arg ) {
  return "'{$arg}'";
}

function doStat( $path, &$stat )
{
   $info = '';
   $perms = fileperms( $path );
	// Besitzer
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ?
				(($perms & 0x0800) ? 's' : 'x' ) :
				(($perms & 0x0800) ? 'S' : '-'));

	// Gruppe
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ?
				(($perms & 0x0400) ? 's' : 'x' ) :
				(($perms & 0x0400) ? 'S' : '-'));

	// Andere
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ?
				(($perms & 0x0200) ? 't' : 'x' ) :
				(($perms & 0x0200) ? 'T' : '-'));

    $stat = lstat( $path );
    if( $stat !== false )
	{
		$upw = posix_getpwuid( $stat['uid'] );
		$uname = $upw['name'];
		$ggr = posix_getgrgid( $stat['gid'] );
		$gname = $ggr['name'];
		$strStat = sprintf( "%s%s %u %s %s", (is_file( $path ) ? '-': (is_link( $path ) ? 'l': (is_dir( $path ) ? 'd':' '))), $info, $stat['nlink'], $uname, $gname );

		$strStat .= "\n\n";
		$strStat .= "perms: ".substr( sprintf( "%o", $perms ), -4 )."\n";
		foreach( $stat as $key=>$val )
		{
		   if( is_int( $key )) continue;
		   $strStat .= "{$key}: {$val}\n";
		}

	}
	else
	{
	   $strStat .= "ERROR: cannot stat() file!\n";
	}

	return $strStat;
}

function storeFile( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )
{
   global $db, $config, $update, $session;

   $canread = true;
   if( $update )
   {
		$sql = "SELECT * `file` WHERE FROM sessionid={$sessionid} AND parentid={$parentid} AND name=".$db->qstr( $name );
		$row = $db->getRow( $sql );
		if( count( $row ))
		{
			echo "found!\n";
			$localcopy = $row['localcopy'];
			$localfile = "{$localpath}/{$row['localcopy']}";
			if( $localpath && (!is_file( $localfile ) || filesize( $localfile ) == 0 ))
			{
						echo "copy!\n";
						$src = fopen( "{$basepath}/{$fullpath}", 'r' );
            if( $src === false ) { $canread = false; }
            else {
  						$dest = fopen( "{$localpath}/{$localcopy}", 'w' );
  						$bytes = stream_copy_to_stream($src, $dest);
  						fclose( $src );
  						fclose( $dest );
  						if( !$bytes ) {
  							unlink( "{$localpath}/{$localcopy}" );
  							$localcopy = null;
  						}
  						else chmod( "{$localpath}/{$localcopy}", 0644 );
            }
			}
			return;
		}
   }

   if( !is_file( "{$basepath}/{$fullpath}" )) throw new Exception( "{$basepath}/{$fullpath} is not a file" );

 	$strStat = doStat( "{$basepath}/{$fullpath}", $stat );
	echo $strStat;

  $localcopy = null;
	if( $localpath )
	{
		$md5 = md5( "{$sessionid}:{$fullpath}" );
		$localcopy = $md5{0}.'/'.$md5{1}.'/'.substr( $md5, 2 );

		$src = fopen( "{$basepath}/{$fullpath}", 'r' );
    if( $src === false ) {
      $canread = false;
    }
    else {
  		$dest = fopen( "{$localpath}/{$localcopy}", 'w' );
  		$bytes = stream_copy_to_stream($src, $dest);
  		fclose( $src );
  		fclose( $dest );
  		if( !$bytes ) {
  		    unlink( "{$localpath}/{$localcopy}" );
  			$localcopy = null;
        $canread = false;
  		}
  		else chmod( "{$localpath}/{$localcopy}", 0644 );
    }
	}


	$cmd = $config['sha256']." -b "._escapeshellarg( "{$basepath}/{$fullpath}" );
  $sha256 = null;
  if( $canread ) {
  	$sha256 = shell_exec( $cmd );
  	if( preg_match( "/^([0-9a-f]+) .*/", $sha256, $matches ))
  	{
  	   $sha256 = $matches[1];
       if( strlen( $sha256 ) > 64 ) $sha256 = null;
  	}
  	else
  	{
  	}
  }
	$sql = "INSERT INTO `file` (
      `sessionid`
    , `parentid`
    , `name`
    , `path`
    , `fullpath`
    , `localcopy`
    , `filetype`
    , `level`
    , `filesize`
    , `sha256`
    , `filectime`
    , `filemtime`
    , `fileatime`
    , `stat`
    , `comment`

    , relevance
    , mtime
  ) VALUES(
    {$sessionid}
    , {$parentid}
    , ".$db->qstr( iconv( $session['fscharset'], 'UTF-8', $name ))."
    , ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path ))."
    ,".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $fullpath ))."
    ,".$db->qstr( $localcopy )."
    , 'file'
    , {$level}, {$stat['size']}
    , ".$db->qstr( $sha256 )."
    , '".date( "Y-m-d H:i:s", $stat['ctime'] )."'
    , '".date( "Y-m-d H:i:s", $stat['mtime'] )."'
    , '".date( "Y-m-d H:i:s", $stat['atime'] )."'
    , ".$db->qstr( $strStat )."
    , ''

    , 50
    , NOW()
    )";
//	echo "{$sql}\n";
	$rs = $db->Execute( $sql );
	$fileid = $db->Insert_ID();

}


function storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )
{
   global $db, $update;

   echo "storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )\n";

   if( $update )
   {
		$sql = "SELECT * FROM `file` WHERE parentid={$parentid} AND name=".$db->qstr( $name );
		echo "{$sql}\n";
		$row = $db->getRow( $sql );
		if( count( $row ))
		{
		   $fileid = $row['fileid'];
			recurse( $sessionid, $basepath, $localpath, $fullpath, $fileid, $level+1 );
			return;
		}
   }


   if( !is_dir( "{$basepath}/{$fullpath}" )) throw new Exception( "{$basepath}/{$fullpath} is not a directory" );

 	$strStat = doStat( "{$basepath}/{$fullpath}", $stat );
	echo $strStat;

	$sql = "INSERT INTO `file` ( `sessionid` , `parentid`, `name` , `path`, `fullpath` , `filetype` , `level` , `filesize` , `sha256` , `filectime` , `filemtime` , `fileatime` , `stat` , `comment`)
    VALUES( {$sessionid}, {$parentid}, ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $name )).", ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path )).",".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $fullpath )).", 'dir', {$level}, {$stat['size']}, NULL, '".date( "Y-m-d H:i:s", $stat['ctime'] )."', '".date( "Y-m-d H:i:s", $stat['mtime'] )."', '".date( "Y-m-d H:i:s", $stat['atime'] )."', ".$db->qstr( $strStat ).", '' )";
	echo "{$sql}\n";
	$rs = $db->Execute( $sql );
	$fileid = $db->Insert_ID();
	//storeFileinfo( $sessionid, $basepath, $fullpath, $name, $fileid, $level );
	recurse( $sessionid, $basepath, $localpath, $fullpath, $fileid, $level+1 );
}

function storeOther( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )
{
   global $db, $update;

   if( $update )
   {
		$sql = "SELECT * FROM `file` WHERE parentid={$parentid} AND name=".$db->qstr( $name );
		$row = $db->getRow( $sql );
		if( count( $row ))
		{
			echo "found!\n";
			return;
		}
   }

	$sql = "INSERT INTO `file` ( `sessionid` , `parentid`, `name` , `path`, `fullpath` , `filetype` , `level` )
  VALUES( {$sessionid}, {$parentid}, ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $name )).", ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path )).",".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $fullpath )).", 'other', {$level} )";
	echo "{$sql}\n";
	$rs = $db->Execute( $sql );
}


function storeLink( $sessionid, $basepath, $localpath, $path, $fullpath, $name, $parentid, $level )
{
   global $db, $update;

   if( $update )
   {
		$sql = "SELECT * FROM `file` WHERE parentid={$parentid} AND name=".$db->qstr( $name );
		echo "{$sql}\n";
		$row = $db->getRow( $sql );
		if( count( $row ))
		{
			echo "found!\n";
			return;
		}
   }


   if( !is_link( "{$basepath}/{$fullpath}" )) throw new Exception( "{$basepath}/{$fullpath} is not a symlink" );

 	$strStat = doStat( "{$basepath}/{$fullpath}", $stat );
	echo $strStat;

	$target = readlink( "{$basepath}/{$fullpath}" );

	$sql = "INSERT INTO `file` ( `sessionid` , `parentid`, `name` , `path` , `fullpath` , `filetype` , `level` , `filesize` , `sha256` , `filectime` , `filemtime` , `fileatime` , `stat` , `comment`)
  VALUES( {$sessionid}, {$parentid}, ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $name )).", ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $path )).", ".$db->qstr(  iconv( $session['fscharset'], 'UTF-8', $fullpath )).", 'link', {$level}, {$stat['size']}, NULL, '".date( "Y-m-d H:i:s", $stat['ctime'] )."', '".date( "Y-m-d H:i:s", $stat['mtime'] )."', '".date( "Y-m-d H:i:s", $stat['atime'] )."', ".$db->qstr( $strStat ).", ".$db->qstr( $target )." )";
	echo "{$sql}\n";
	$rs = $db->Execute( $sql );
	$fileid = $db->Insert_ID();
	//storeFileinfo( $sessionid, $basepath, $fullpath, $name, $fileid, $level );
}

function recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )
{
   global $update, $counter, $fscharset, $session;

   echo "recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )\n";

   $d = opendir( "{$basepath}/{$path}" );
   while( $file = readdir( $d ))
   {

		if( $file == '.' || $file == '..' ) continue;

    //$file = iconv($fscharset, "UTF-8", $file);

		echo "#".($counter++)." {$sessionid}: {$basepath} // {$file} // {$localpath} ----\n";

		$fullpath = "{$path}/{$file}";
		$fullpath = trim( $fullpath, '/' );
		if( is_link( "{$basepath}/{$fullpath}" ))
		{
			echo "link: {$basepath}/{$fullpath} --> {$target}\n";
			storeLink( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		elseif( is_dir( "{$basepath}/{$fullpath}" ))
		{
			echo "dir: {$basepath}/{$fullpath}\n";
			storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		elseif( is_file( "{$basepath}/{$fullpath}" ))
		{
			echo "file: {$basepath}/{$fullpath}\n";
			storeFile( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
		else
		{
		   echo "other: {$basepath}/{$fullpath}\n";
		   storeOther( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
		}
   }
   closedir( $d );
}

if( is_numeric( $argv[1] ))
{
	$sessionid = intval( $argv[1] );
	$sql = "SELECT * FROM session WHERE sessionid=".$sessionid;
}
else
{
	$group = trim( $argv[1] );
	$sql = "SELECT * FROM session WHERE `group`=".$db->qstr($group);
	if( $group == 'all' ) $sql = "SELECT * FROM session";
}

//$row = $db->getRow( $sql );
echo "{$sql}\n";
$rs = $db->Execute( $sql );
foreach( $rs as $row )
{

  $sql = "SELECT COUNT(*) FROM file WHERE sessionid={$row['sessionid']}";
  $num = intval($db->GetOne( $sql ));
  if( $num ) continue;

  $session = $row;

	$basepath = $row['basepath'];
  $localpath = $row['localpath'];
  $datapath = $row['datapath'];
  $mountpoint = $row['mountpoint'];
  $fscharset = $row['fscharset'];
  //print_r( $session );
  $mount = null;
  $umount = null;
  if( $row['mount'] ) {
    $mount = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($mountpoint) ), $row['mount'] );
    $umount = preg_replace( array( '/\$\$IMAGE\$\$/', '/\$\$MOUNTPOINT\$\$/' ), array( _escapeshellarg($datapath), _escapeshellarg($mountpoint) ), $row['umount'] );

    echo $mount."\n";
    passthru( $mount );
  }

	recurse( $row['sessionid'], $mountpoint, $localpath, '', 0, 0 );

  if( $mount ) {
    echo $umount."\n";
    passthru( $umount );

  }
}
?>
