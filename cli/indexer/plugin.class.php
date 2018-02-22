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

abstract class Plugin
{
	protected $sessionid;
	protected $datapath;
	protected $fullpath;
	protected $localcopy;
	protected $file;
	protected $fileid;
	protected $db;

	public function __construct()
	{
	}

	public function init( $db, $sessionid, $fileid, $datapath, $fullpath, $localcopy )
	{
		$this->db = $db;
		$this->sessionid = intval( $sessionid );
		$this->fileid = intval( $fileid );
		$this->datapath = $datapath;
		$this->fullpath = $fullpath;
		$this->localcopy = $localcopy;
		$this->file = $localcopy;
	}

	public function getSessions($db)
	{
		$sql = "SELECT sessionid FROM session";
	   echo "$sql\n";
	   $rs = $this->db->getAll( $sql );
	   $sessions = array();
	   foreach( $rs as $row ) { $sessions[] = $row['sessionid']; }
	   return $sessions;
	}

	abstract public function check();
	abstract public function index( $update );

	public static function where()
	{
		return '1=1';
	}

	public static function joins()
	{
		return array();
	}
}


?>
