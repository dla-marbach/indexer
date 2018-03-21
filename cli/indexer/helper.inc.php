<?php
namespace indexer;


function is_mounted( $dir ) {
  global $config;

  $cmd = $config['mountpoint']." -q ".escapeshellarg( $dir );
  exec( $cmd, $output, $return_var );
  return ($return_var == 0);
}

function _escapeshellarg( $arg ) {
  return "'.addslashes($arg).'";
}

function log( $sessionid, $fileid, $status, $message ) {
  global $db;

  $sql = "INSERT INTO indexlog (sessionid, fileid, status, message )
  VALUES( {$sessionid}
    , ".($fileid ? $fileid : 'null')."
    , ".$db->qstr( $status )."
    , ".$db->qstr( $message )."
    )";
  $db->Execute( $sql );

  if( $fileid ) {
    $sql = "UPDATE `file` SET
      readstate=".$db->qstr( $status )."
      WHERE sessionid={$sessionid} AND fileid={$fileid}";
    $db->Execute( $sql );
  }
}

function doStat( $path, &$stat, &$isError )
{
   $isError = false;
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
     $isError = true;
	}

	return $strStat;
}


 ?>
