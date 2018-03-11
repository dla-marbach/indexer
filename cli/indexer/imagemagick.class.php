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

class ImageMagick extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}



	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_imagick` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
	   $cmd = $config['identify']."  -format 'Format: %m; Geometry: %wx%h; xres: %x; yres: %y;' ".escapeshellarg( "{$this->file}" ).' 2>&1';
	   //echo "$cmd\n";
	   $info = shell_exec( $cmd );
	   echo "$info\n";

		if( preg_match( "/.*Format: (\S+);.*Geometry: ([0-9]+)x([0-9]+);.*xres: ([^;]*);.*yres: ([^;]*);.*/", $info, $matches ))
		{
			$magick = $matches[1];
			$width = intval( $matches[2] );
			$height = intval( $matches[3] );
			$xres = $matches[4];
			$yres = $matches[5];
			$thumb = $this->file.'.imagick.thumb.png';

			$sql = "INSERT INTO info_imagick ( sessionid, fileid, magick, width, height, xres, yres, fullinfo, status )
					VALUES( {$this->sessionid}
						, {$this->fileid}
						, ".$db->qstr( $magick )."
						, {$width}
						, {$height}
						, ".$db->qstr( $xres )."
						, ".$db->qstr( $yres )."
						, ".$db->qstr( $info )."
						, ".$db->qstr( 'ok' )."
						 )";
//			echo "{$sql}\n";
			$db->Execute( $sql );

			if( file_exists( $thumb )) unlink( $thumb );
			$cmd = $config['convert'].' '.escapeshellarg( "{$this->file}" ).'[0] -resize 120x90 -sharpen 4 '.escapeshellarg( $thumb );
			echo "$cmd\n";
			shell_exec( $cmd );

		}
		else
		{
			$sql = "INSERT INTO info_imagick ( sessionid, fileid, fullinfo, status )
					VALUES( {$this->sessionid}
						, {$this->fileid}
						, ".$db->qstr( $info )."
						, ".$db->qstr( 'error' )."
						 )";
//			echo "{$sql}\n";
			$db->Execute( $sql );
			echo "Error identifying image: {$cmd}\n";
		}
	}

	public static function where()
	{
		return "(ilm.`mimetype` LIKE 'image/%' OR ilm.`mimetype` LIKE 'application/pdf' OR
				 igi.`mimetype` LIKE 'image/%' OR igi.`mimetype` LIKE 'application/pdf' )
				 AND iim.status IS NULL";
	}

	public static function joins()
	{
		return array( 'ilm'=>'info_libmagic',
					  'igi'=>'info_gvfs_info',
						'iim'=>'info_imagick',
					);
	}

}

?>
