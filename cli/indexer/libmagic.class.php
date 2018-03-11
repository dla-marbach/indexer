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

class LibMagic extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}


	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_libmagic` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		static $finfo;

		$db = $this->db;

		try {
			if( !isset( $finfo )) $finfo = new \finfo();
			$text = $finfo->file( $this->file );
			$mime_type = $finfo->file( $this->file, FILEINFO_MIME_TYPE );
			$mime_encoding = $finfo->file( $this->file, FILEINFO_MIME_ENCODING );
			echo  "  {$text} // {$mime_type} // {$mime_encoding}\n";
			$sql = "INSERT INTO info_libmagic( sessionid, fileid, mimetype, mimeencoding, description, status )
					VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( $mime_type ).", ".$db->qstr( $mime_encoding ).", ".$db->qstr( $text ).", ".$db->qstr( 'ok' )." )";
			//echo "{$sql}\n";
			$db->Execute( $sql );
		}
		catch( \Exception $e ) {
			$sql = "INSERT INTO info_libmagic( sessionid, fileid, description, status )
					VALUES( {$this->sessionid}, {$this->fileid}, ".$db->qstr( $e ).", ".$db->qstr( 'error' )." )";
			//echo "{$sql}\n";
			$db->Execute( $sql );
		}
		unset( $text );
	}

	public static function where()
	{
		return "f.filetype<>'other' AND ilm.status IS NULL";
	}

	public static function joins()
	{
		return array( 'ilm'=>'info_libmagic' );
	}

}

?>
