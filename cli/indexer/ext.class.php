<?php

namespace indexer;

class Ext extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}

	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `file` WHERE filetype='file' AND ext IS NULL AND sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num == 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$sql = "UPDATE file SET ext=".$db->qstr( pathinfo( $this->fullpath, PATHINFO_EXTENSION ) )." WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
			//echo "{$sql}\n";
			$db->Execute( $sql );
	}

	public static function where()
	{
		return "filetype='file' AND ext IS NULL";
		// return 'f.localcopy IS NOT NULL AND f.md5 IS NULL';

	}

}

?>
