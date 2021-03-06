#!/usr/bin/php

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

require_once( 'config.inc.php' );
include( 'db.inc.php' );

if( $argc < 3 ) die( "{$argv[0]} <id> <lock|unlock|red|yellow|green|unknown>\n" );

list( $bestandid, $sessionid, $fileid ) = explode( '.', $argv[1] );
$sessionid = intval( $sessionid );
$fileid = intval( $fileid );
if( !$sessionid || !$fileid ) die( "no valid id" );

$data = array();

switch( $argv[2] ) {
	case 'lock':
		$data['status.locked'] = true;
		$sql = "UPDATE `file` SET `lock`=1, mtime=NOW() WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'lock' ).")";	   break;
	case 'unlock':
		$data['status.locked'] = false;
		$sql = "UPDATE `file` SET `lock`=0, mtime=NOW() WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'unlock' ).")";
	   break;
	case 'red':
	case 'yellow':
	case 'green':
	case 'unknown':
		$data['status.status'] = $argv[2];
		$sql = "UPDATE `file` SET status=".$db->qstr( $argv[2] ).", mtime=NOW() WHERE sessionid={$sessionid} and fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'status: '.$argv[2] ).")";
	   break;
	default:
	   die( "unknown status {$argv[2]}" );
}
echo $sql."\n";
$db->Execute( $sql );
$db->Execute( $sql_log );
//updateDocumentSolarium( $db, $sessionid, $fileid, $data, $config );

?>
