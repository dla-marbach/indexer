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

class GVFSInfo extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}



	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_gvfs_info` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$cmd = $config['gvfs-info']." ".escapeshellarg( $this->file );
		$info = shell_exec( $cmd );
		$mime_type = null;
		if( preg_match( "/standard::content-type: (.*\/.*)/", $info, $matches ))
		{
			$mime_type = $matches[1];
		}
		if( strlen( $info ))
		{
			$sql = ($update ? 'REPLACE':'INSERT')." INTO info_gvfs_info ( sessionid, fileid, mimetype, fullinfo )
					VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( $mime_type ).", ".$db->qstr( $info )." )";
			//echo "{$sql}\n";
			$db->Execute( $sql );
		}
		else
		{
			 throw new \Exception( "Error gettin info of {$this->fullpath}\n" );
		}
	}
	public static function where()
	{
		return "1=1";
	}

	public static function joins()
	{
		return array( ); // 'igi'=>'info_gvfs_info' );
	}
}

?>
