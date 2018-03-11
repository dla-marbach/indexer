<?php

namespace indexer;

/**
dba

DBA support => enabled
Supported handlers => cdb cdb_make db4 inifile flatfile

*/

class NSRL extends Plugin
{
	protected $nsrldb = null;

	public function __construct()
	{
		parent::__construct();
	}

	public function init( $db, $sessionid, $fileid, $basepath, $fullpath, $localfile )
	{
		global $config;
		if( !$this->nsrldb ) $this->nsrldb = dba_open( $config['nsrl_dbfile'], 'r-', $config['nsrl_dbtype'] );
		parent::init( $db, $sessionid, $fileid, $basepath, $fullpath, $localfile );
	}


	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_nsrl` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$sql = "SELECT UPPER(md5) FROM `file` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
		$md5 = $db->getOne( $sql );
		$nsrl = dba_fetch( $md5, $this->nsrldb );
		if( $nsrl )
		{
			$nl = json_decode( gzinflate( $nsrl ));
			if( count( $nl ) == 8 )
			{
				$sql = "INSERT INTO info_nsrl( sessionid,  `fileid` , `FileName` , `FileSize` , `ProductCode` , `OpSystemcode` , `Specialcode`, status )
					VALUES( {$this->sessionid}
						, {$this->fileid}
						, ".$db->qstr( $nl[3] )."
						, ".intval( $nl[4] )."
						, ".intval( $nl[5] )."
						, ".$db->qstr( $nl[6] )."
						, ".$db->qstr( $nl[7] )."
						, ".$db->qstr( 'ok' )."
						 )";
					//echo "{$sql}\n";
					$db->Execute( $sql );
			}
			else {
				$sql = "INSERT INTO info_nsrl( sessionid,  `fileid`, status )
					VALUES( {$this->sessionid}
						, {$this->fileid}
						, ".$db->qstr( 'ok' )."
						 )";
					//echo "{$sql}\n";
					$db->Execute( $sql );
		}

		}
		else {
			$sql = "INSERT INTO info_nsrl( sessionid,  `fileid`, status )
				VALUES( {$this->sessionid}
					, {$this->fileid}
					, ".$db->qstr( 'ok' )."
					 )";
				//echo "{$sql}\n";
				$db->Execute( $sql );
	}

	}

	public static function where()
	{
		return "f.md5 IS NOT NULL AND insrl.status IS NULL";

	}

	public static function joins()
	{
		return array( 'insrl'=>'info_nsrl',
					);
	}
}

?>
