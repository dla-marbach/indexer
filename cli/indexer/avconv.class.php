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

class AVConv extends Plugin
{

	public function __construct()
	{
		parent::__construct();
	}



	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_avconv` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$thumb = $this->file.'.avconv.thumb.png';
		$db = $this->db;
//	   $cmd = "bash -c \"".$config['avconv']." -i ".escapeshellarg( "{$this->basepath}/{$this->fullpath}")." &>{$config['tempfolder']}/avconv.out; cat {$config['tempfolder']}/avconv.out\"";
	   $cmd = $config['avconv']." -i ".escapeshellarg( "{$this->file}")." 2>&1";
	   echo "$cmd\n";
       $info = shell_exec( $cmd );
	   $info = trim( preg_replace( array( "/At least one output file must be specified/", "/  built on.*/", "/  built with.*/", "/ffmpeg version.*/", "/  lib.*/", "/  configuration:.*/" ), array( '', '', '', '', '', ''), $info));
//	   $p = strpos( $info, '[' );
//	   if( $p ) $info = substr( $info, $p );
	   echo "$info\n";

		if( file_exists( $thumb )) unlink( $thumb );
		if( file_exists( $thumb.'.png' )) unlink( $thumb.'.png' );
		//if( file_exists( $thumb.'.svg' )) unlink( $thumb.'.svg' );


		if( preg_match( "/Stream #[^ ]*: Video/", $info ) )
		{
			$cmd = $config['avconv'].' -ss 10 -i '.escapeshellarg("{$this->file}").' -f image2 -vframes 1 '.escapeshellarg( $thumb.'.png' );
			echo "$cmd\n";
			shell_exec( $cmd );
			$cmd = $config['convert'].' '.escapeshellarg( "{$thumb}.png" ).' -resize 120x90 -sharpen 4 '.escapeshellarg( $thumb );
			echo "$cmd\n";
			shell_exec( $cmd );
			if( file_exists( $thumb.'.png' )) unlink( $thumb.'.png' );
		}

		$enc = \mb_detect_encoding($info, "UTF-8,ISO-8859-1");
		$info = iconv($enc, "UTF-8", $info);

		$sql = "INSERT INTO info_avconv ( sessionid, fileid, fullinfo, status )
				VALUES( {$this->sessionid}
					, {$this->fileid}
					, ".$db->qstr( $info )."
					, ".$db->qstr( preg_match( "/Duration: .*/", $info ) ? 'ok' : 'error' )."
					 )";
//			echo "{$sql}\n";
		$db->Execute( $sql );
	}

	public static function where()
	{
		return "(ilm.`mimetype` LIKE 'video/%' OR ilm.`mimetype` LIKE 'audio/%' OR
				 igi.`mimetype` LIKE 'video/%' OR igi.`mimetype` LIKE 'audio/%' OR igi.`mimetype` LIKE 'application/mxf')
				 AND iav.status IS NULL";
	}

	public static function joins()
	{
		return array( 'ilm'=>'info_libmagic',
					  'igi'=>'info_gvfs_info',
					 	'iav'=>'info_avconv',
					);
	}

}

?>
