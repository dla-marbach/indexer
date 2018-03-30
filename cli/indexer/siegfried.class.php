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

class Siegfried extends Plugin
{

  static private function nullIfEmpty( $str ) {
    return strlen( $str ) ? $str : null;
  }

	public function __construct()
	{
		parent::__construct();
	}



	public function check()
	{
		$sql = "SELECT COUNT(*) FROM `info_siegfried` WHERE sessionid={$this->sessionid} AND fileid={$this->fileid}";
	//   echo "$sql\n";
		$num = intval( $this->db->getOne( $sql ));
		return $num > 0;
	}

	public function index( $update )
	{
		global $config;
		$db = $this->db;
		$cmd = $config['siegfried']." -json ".escapeshellarg( $this->file );
		$json = shell_exec( $cmd );
  //  echo $json;
    $data = json_decode( $json, true );
		if( json_last_error() == JSON_ERROR_NONE && count( $data['files'] ))
		{
//      print_r( $data );
      $file = $data['files'][0];
      $matches = $file['matches'];
      $num = 0;
      foreach( $matches as $match ) {
        if( $match['warning'] == 'no match') continue;
        $num++;
  			$sql = "INSERT INTO info_siegfried ( sessionid, fileid, status, ns, id, format, version, mimetype, basis, data )
  					VALUES( {$this->sessionid}, {$this->fileid}
              , ".$db->qstr( 'ok' )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['ns']) )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['id']) )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['format']) )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['version']) )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['mime']) )."
              , ".$db->qstr( Siegfried::nullIfEmpty($match['basis']) )."
              , ".$db->qstr( $json )."
            )";
        }
        if(( !$num ))
        {
          $sql = "INSERT INTO info_siegfried ( sessionid, fileid, status )
              VALUES( {$this->sessionid}, {$this->fileid}
                , ".$db->qstr( 'error' )."
              )";
        }
		}
		else
		{
      $sql = "INSERT INTO info_siegfried ( sessionid, fileid, status )
          VALUES( {$this->sessionid}, {$this->fileid}
            , ".$db->qstr( 'error' )."
          )";
		}
    //echo "{$sql}\n";
    $db->Execute( $sql );
	}
	public static function where()
	{
		return "f.localcopy IS NOT NULL AND isi.status IS NULL";
	}

	public static function joins()
	{
		return array( 'isi'=>'info_siegfried' );
	}
}

?>
