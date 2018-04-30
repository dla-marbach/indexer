<?php

namespace indexer;

class RecurseFS
{
  protected $update, $counter, $session, $db, $hardlink;

  public function __construct($update, $session, $db, $hardlink)
  {
      $this->update = $update;
      $this->counter = 1;
      $this->session = $session;
      $this->db = $db;
      $this->hardlink = $hardlink;
  }

  static function _escapeshellarg( $arg ) {
    return "'".str_replace("'", "'\"'\"'", $arg)."'";
  }

  static function doStat( $path, &$stat, &$isError )
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

  function storeLink( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

    //global $db

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
     comment=".$this->db->qstr( $target )."
     , readstate=".$this->db->qstr( $target===false ? 'error':'ok' )."
     , filetype=".$this->db->qstr( 'link')."
     , mtime=NOW()
     WHERE sessionid={$sessionid} AND fileid={$fileid}";
    $this->db->Execute( $sql );
  }

  function storeOther( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

    // global $db;

    $sql = "UPDATE `file` SET
      readstate=".$this->db->qstr( 'ok' )."
      , filetype=".$this->db->qstr( 'other')."
      , mtime=NOW()
      WHERE sessionid={$sessionid} AND fileid={$fileid}";
    $this->db->Execute( $sql );
  }

  function storeFile( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {
    // global $db, $config, $update, $session, $hardlink;

    global $config;

    $canread = true;

    if( !is_file( "{$basepath}/{$fullpath}" )) {
      log( 'recurse.php', $sessionid, $fileid, 'error', "not a file" );
      return;
     }

    $localcopy = null;
    if( $localpath )
    {
     $md5 = md5( "{$sessionid}:{$fullpath}" );
     $localcopy = $md5{0}.'/'.$md5{1}.'/'.substr( $md5, 2 );

     if( $this->hardlink ) {
       $ret = link( "{$basepath}/{$fullpath}", "{$localpath}/{$localcopy}" );
       if( !$ret ) {
         log( 'recurse.php', $sessionid, $fileid, 'error', 'cannot create hardlink to cache file' );
         //return;
       }
     }
     else {
       $src = fopen( "{$basepath}/{$fullpath}", 'r' );
       if( $src === false ) {
         log( 'recurse.php', $sessionid, $fileid, 'error', 'cannot original open file' );
         return;
       }
         $dest = fopen( "{$localpath}/{$localcopy}", 'w' );
         if( $dest === false ) {
           fclose( $src );
           log( 'recurse.php', $sessionid, $fileid, 'error', 'cannot create cache file' );
           return;
         }

         $bytes = stream_copy_to_stream($src, $dest);
         fclose( $src );
         fclose( $dest );
         if( $bytes === false ) {
           unlink( "{$localpath}/{$localcopy}" );
           log( 'recurse.php', $sessionid, $fileid, 'error', 'stream to stream copy error' );
           return;
         }
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

    $cmd = $config['sha256']." -b ".RecurseFS::_escapeshellarg( "{$basepath}/{$fullpath}" );
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
        log( 'recurse.php', $sessionid, $fileid, 'error', 'error generating sha256 checksum' );
        return;
       }
    }

    $sql = "UPDATE `file` SET
     ext=".$this->db->qstr( pathinfo( $fullpath, PATHINFO_EXTENSION ) )."
     , sha256=".$this->db->qstr( $sha256 )."
     , localcopy=".$this->db->qstr( $localcopy )."
     , readstate=".$this->db->qstr( 'ok' )."
     , filetype=".$this->db->qstr( 'file')."
     , mtime=NOW()
     WHERE sessionid={$sessionid} AND fileid={$fileid}";
    $this->db->Execute( $sql );

  }

  function storeDir( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level ) {

    // global $db, $update, $session;

    echo "storeDir( $sessionid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level )\n";

    if( !is_dir( "{$basepath}/{$fullpath}" )) {
      log( 'recurse.php',  $sessionid, $fileid, 'error', "not a directory" );
      return;
    }

    $sql = "UPDATE `file` SET
      readstate=".$this->db->qstr( 'ok' )."
      , filetype=".$this->db->qstr( 'dir')."
      , mtime=NOW()
      WHERE sessionid={$sessionid} AND fileid={$fileid}";
    $this->db->Execute( $sql );

    $this->recurse( $sessionid, $basepath, $localpath, $fullpath, $fileid, $level+1 );
  }

  public function recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )
  {
     echo "recurse( $sessionid, $basepath, $localpath, $path, $parentid, $level )\n";

     $d = opendir( "{$basepath}/{$path}" );
     while( $file = readdir( $d ))
     {

  		if( $file == '.' || $file == '..' ) continue;

      //$file = iconv($this->fscharset, "UTF-8", $file);
      $fullpath = "{$path}/{$file}";
  		$fullpath = trim( $fullpath, '/' );

  		echo "#".($this->counter++)." {$sessionid}: {$basepath} // {$file} // {$fullpath} ----\n";


      $fileid = null;

      // check for database
      $sql = "SELECT fileid, readstate, filetype FROM file WHERE sessionid={$sessionid} AND path=".$this->db->qstr( $path )." AND name=".$this->db->qstr( $file ) ;
      $row = $this->db->GetRow( $sql );
      if( count( $row )) {
        $fileid = $row['fileid'];
        // if entry exists but is not ok, then delete it and retry
        if(( $row['readstate'] == 'ok' ||  $row['readstate'] == 'skip' ) && $row['filetype'] != 'dir' ) {
          echo "   --- skipping\n";
          continue;
        }
      }

      // get status and permissions.
      $strStat = RecurseFS::doStat( "{$basepath}/{$fullpath}", $stat, $statError );

      // if fileid is set, we must not create a new entry...
      if( $fileid ) {
        $sql = "UPDATE `file` SET
          parentid={$parentid}
          , name=".$this->db->qstr(  iconv( $this->session['fscharset'], 'UTF-8', $file ))."
          , path=".$this->db->qstr(  iconv( $this->session['fscharset'], 'UTF-8', $path ))."
          , `level`={$level}
          , `filesize`=".($statError ? 0 : intval($stat['size']))."
          , `filectime`='".date( "Y-m-d H:i:s", $stat['ctime'] )."'
          , `filemtime`='".date( "Y-m-d H:i:s", $stat['mtime'] )."'
          , `fileatime`='".date( "Y-m-d H:i:s", $stat['atime'] )."'
          , `stat`=".$this->db->qstr( $strStat )."
          , readstate=".$this->db->qstr( $statError ? 'error':'start' )."
          WHERE sessionid={$sessionid} AND fileid={$fileid}";

        $this->db->Execute( $sql );
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
            , ".$this->db->qstr(  iconv( $this->session['fscharset'], 'UTF-8', $file ))."
            , ".$this->db->qstr(  iconv( $this->session['fscharset'], 'UTF-8', $path ))."
            , {$level}
            , ".($statError ? 0 : intval($stat['size']))."
            , '".date( "Y-m-d H:i:s", $stat['ctime'] )."'
            , '".date( "Y-m-d H:i:s", $stat['mtime'] )."'
            , '".date( "Y-m-d H:i:s", $stat['atime'] )."'
            , ".$this->db->qstr( $strStat )."
            , ".$this->db->qstr( $statError ? 'error':'start' )."
            )";

        $this->db->Execute( $sql );
        $fileid = $this->db->Insert_ID();
      }

      // logentry on stat error
      if( $statError ) {
        log( 'recurse.php',  $sessionid, $fileid, 'error', 'cannot stat file' );
        continue;
      }
      if( is_link( "{$basepath}/{$fullpath}" ))
  		{
  			echo "link: {$basepath}/{$fullpath} --> {$target}\n";
  			$this->storeLink( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
  		}
  		elseif( is_dir( "{$basepath}/{$fullpath}" ))
  		{
  			echo "dir: {$basepath}/{$fullpath}\n";
        $this->storeDir( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
  		}
  		elseif( is_file( "{$basepath}/{$fullpath}" ))
  		{
  			echo "file: {$basepath}/{$fullpath}\n";
        $this->storeFile( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
  		}
  		else
  		{
  		   echo "other: {$basepath}/{$fullpath}\n";
         $this->storeOther( $sessionid, $fileid, $basepath, $localpath, $path, $fullpath, $file, $parentid, $level );
  		}
     }
     closedir( $d );
  }


}

?>
