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

require_once( 'config.inc.php' );
include( 'db.inc.php' );

if( $argc < 2 ) die( "{$argv[0]} <sessionid>\n" );

$p2 = strtolower(trim( $argv[1] ));
if( preg_match( '/^[0-9]+$/', $p2 ))
{
	$sessionid = intval( $p2 );
	$sessSQL = "s.sessionid=".$sessionid;
}
elseif( preg_match( '/^b[0-9]+$/', $p2 ))
{
	$bestandid = intval( substr( $p2, 1 ));
	$sessSQL = "s.bestandid=".$bestandid;
}
else
{
	$group = $p2;
	$sessSQL = "s.group=".$db->qstr( $group );
	if( $group == 'all' ) $sessSQL = '1=1';
}


$sql = "SELECT DISTINCT f.inventory, b.bagitpath
  FROM `file` f, session s, inventoryno i, bestand b
  WHERE b.zoterogroup IS NOT NULL AND b.bestandid=s.sessionid AND f.sessionid=s.sessionid AND f.inventory=i.inventoryno AND {$sessSQL} AND (i.synctime IS NULL OR i.synctime <= f.mtime)";
