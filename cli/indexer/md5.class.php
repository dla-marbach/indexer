<?php

namespace indexer;

class MD5 extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}

	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `file` WHERE localcopy IS NOT NULL AND md5 IS NULL AND sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num == 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$cmd = $config['md5']." -b ".escapeshellarg( "{$this->file}" );
		echo "{$cmd}\n";
		$content = trim( shell_exec( $cmd ));
		if( preg_match( "/([0-9a-f]+) .*/", $content, $matches ))
		{
			$sql = "UPDATE file SET md5=".$db->qstr( $matches[1] )." WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
			//echo "{$sql}\n";
			$db->Execute( $sql );
		}
	}

	public static function where()
	{
		return "filetype='file' AND md5 IS NULL";
		// return 'f.localcopy IS NOT NULL AND f.md5 IS NULL';

	}

}

?>
