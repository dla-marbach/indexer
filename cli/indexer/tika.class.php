<?php

namespace indexer;

class Tika extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}


	public function getSessions( $db )
	{
		$sql = "SELECT DISTINCT f.sessionid AS sessionid
FROM `file` f
LEFT JOIN info_libmagic ilm ON ( f.sessionid = ilm.sessionid AND f.fileid = ilm.fileid )
LEFT JOIN info_tika it ON ( f.sessionid = it.sessionid AND f.fileid = it.fileid )
WHERE it.sessionid IS NULL
AND (".Tika::where().")";
	   echo "$sql\n";
	   $rs = $db->getAll( $sql );
	   $sessions = array();
	   foreach( $rs as $row ) { $sessions[] = $row['sessionid']; }
	   return $sessions;
	}

	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_tika` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;

		$db = $this->db;
	   $sql = "INSERT INTO info_tika ( sessionid, fileid, status )
				VALUES( {$this->sessionid}, {$this->fileid}, 'start' )";
		echo "{$sql}\n";
		$db->Execute( $sql );

//	   $cmd = $config['tika']." -m ".escapeshellarg( "{$this->basepath}/{$this->fullpath}" );
	   $cmd = $config['tika']." ".escapeshellarg( "jdbc:mysql://{$config['db']['server']}/{$config['db']['db']}?user={$config['db']['user']}&password={$config['db']['pwd']}&useSSL=false" );
	   echo "$cmd\n";
	   $info = shell_exec( $cmd );
	   echo "$info\n";
	   $mime_type = null;
	   $mime_encoding = null;
	   if( preg_match( "/Content-Type: (.*\/.*); charset=(.*)/", $info, $matches ))
	   {
		  $mime_type = $matches[1];
		  $mime_encoding = $matches[2];
	   }
	   elseif( preg_match( "/Content-Type: (.*\/.*)/", $info, $matches ))
	   {
		  $mime_type = $matches[1];
	   }
	   elseif( !strlen( trim( $info )))
	   {
			$err = file_get_contents( '/tmp/tika.err' );
			if( preg_match( "/connect: Connection refused/", $err )) die( "TIKA crashed\n" );
	   }
	   if( $mime_type != 'application/x-executable'
			&& !preg_match( "/image\\/.*/", $mime_type )
			&& !preg_match( "/video\\/.*/", $mime_type )
			&& filesize( $this->file ) <= $config['tika_max_size']
	   )
	   {
			$contentfile = "{$this->localfile}{$config['tika_file_ext']}.gz";
	//		   $cmd = $config['tika']." -t ".escapeshellarg( "{$this->basepath}/{$this->fullpath}" );
		   $cmd = $config['tika']." -t ".escapeshellarg( $this->file )." | {$config['gzip']} > {$contentfile}";
		   echo "$cmd\n";
		   shell_exec( $cmd );
		   // 20 byte gzip header
		   if(!($hascontent = filesize( $contentfile ) > 20 ))
		   {
				unlink( $contentfile );
			}
	   }
	   else
	   {
			$hascontent = false;
	   }
	   //   $sql = ($update ? 'REPLACE':'INSERT')." INTO info_tika ( sessionid, fileid, mimetype, mimeencoding, fullinfo )
	   //	        VALUES( {$sessionid}, {$fileid}, ".$db->qstr( $mime_type ).", ".$db->qstr( $mime_encoding ).", ".$db->qstr( $info )." )";
	   $sql = "REPLACE INTO info_tika ( sessionid, fileid, mimetype, mimeencoding, fullinfo, hascontent, status )
				VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( $mime_type ).", ".$db->qstr( $mime_encoding ).", ".$db->qstr( $info ).", ".intval( $hascontent ).", 'done' )";
		echo "{$sql}\n";
		$db->Execute( $sql );
	}

	public static function where()
	{
		//return "ilm.mimetype NOT LIKE 'audio/%' AND f.name NOT LIKE '%.mp3' AND f.filetype='file'";
		return "f.filetype IN ( 'file', 'archive' )";
	}

	public static function joins()
	{
		return array(
//					  'it'=>'info_tika',
//					  'ilm'=>'info_libmagic'
		);
	}
}

?>
