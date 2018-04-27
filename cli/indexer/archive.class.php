<?php

namespace indexer;

class Archive extends Plugin
{
  private $archive_heuristicid = 3;

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
		return 'ilm.mimetype IN ("application/x-gzip",
"application/x-tar",
"application/zip",
"application/x-compressed-tar",
"application/x-compress",
"application/x-bzip",
"application/x-bzip-compressed-tar") AND ia.status IS NULL';
	}

	public static function joins()
	{
		return array( 'ilm'=>'info_libmagic',
    'ia'=>'info_archive',
						 );
	}

}

?>
