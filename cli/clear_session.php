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
include( 'solr.inc.php' );

if( $argc < 2 ) die( "{$argv[0]} <sessionid>\n" );

if( is_numeric( $argv[1] ))
{
	$sessionid = intval( $argv[1] );
	$sessSQL = "sessionid=".$sessionid;
}
else
{
  die( 'parameter is not numeric'."\n" );
}

$client = new \Solarium\Client($solarium_config);

echo "deleting solr index for session {$sessionid}\n";
$update = $client->createUpdate();
$update->addDeleteQuery('session.id:'.$sessionid);
$update->addCommit();
$result = $client->update($update);
echo 'Delete query executed'."\n";
echo 'Query status: ' . $result->getStatus()."\n";
echo 'Query time: ' . $result->getQueryTime()."\n";

$tables = array(
  'file'
  , 'info_antiword'
  , 'info_avconv'
  , 'info_gvfs_info'
  , 'info_imagick'
  , 'info_libmagic'
  , 'info_nsrl'
	, 'info_tika'
	, 'info_siegfried'
	, 'indexlog'
);

foreach( $tables as $table ) {
  $sql = "DELETE FROM `{$table}` WHERE sessionid={$sessionid}";
  echo "{$sql}\n";
  $db->Execute( $sql );
}
