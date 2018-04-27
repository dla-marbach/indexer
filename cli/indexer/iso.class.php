<?php

namespace indexer;

class ISO extends Plugin
{
  private $archive_heuristicid = 1;

	public function __construct()
	{
		parent::__construct();
  }

	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_archive` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
    $sql = "INSERT INTO info_archive ( sessionid, fileid, status, archive_heuristicid )
					 VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( 'ok' ).", {$this->archive_heuristicid} )";
	 		//echo "{$sql}\n";
		 $db->Execute( $sql );
     $sql = "UPDATE `file` SET filetype=".$db->qstr( 'archive' )." WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	 		//echo "{$sql}\n";
 		 $db->Execute( $sql );
	}

	public static function where()
	{
		return 'isf.id="fmt/468" AND ia.status IS NULL';
	}

	public static function joins()
	{
		return array( 'isf'=>'info_siegfried',
      'ia'=>'info_archive'
						 );
	}

}

?>
