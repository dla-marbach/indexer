<?php

namespace indexer;

class Antiword extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}



	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_antiword` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$contentfile = "{$this->file}.antiword.gz";

		$cmd = $config['antiword']." ".escapeshellarg( $this->file )." | {$config['gzip']} > {$contentfile}";;
		shell_exec( $cmd );
		$hascontent = filesize( $contentfile ) > 20;
	  if( !$hascontent  ) {
				@unlink( $contentfile );
		}
		$sql = "INSERT INTO info_antiword ( sessionid, fileid, status )
					 VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( $hascontent ? 'ok' : 'error' )." )";
//	 		echo "{$sql}\n";
		 $db->Execute( $sql );
	}

	public static function where()
	{
		return 'ilm.mimetype=\'application/msword\' AND iaw.status IS NULL';
	}

	public static function joins()
	{
		return array( 'ilm'=>'info_libmagic',
	 						'iaw'=>'info_antiword',
						 );
	}

}

?>
