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


$asql = "SELECT fileid FROM `file` WHERE archiveid IS NOT NULL";

$doTables = false;
$doSOLR = false;
$doCache = false;

$sql = "SELECT COUNT(*) FROM ({$asql}) AS su";
$num = $db->GetOne( $sql );

if( $doTables ) {
  $tables = array(
   'info_antiword'
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
    $sql = "DELETE FROM `{$table}` WHERE fileid IN ({$asql})";
    echo "{$sql}\n";
    $db->Execute( $sql );
  }
}

if( $doSOLR ) {
  $client = new \Solarium\Client($solarium_config);

  $sql = $asql;
  echo "{$sql}\n";
  $rs = $db->Execute( $sql );
  $cnt = 0;
  foreach( $rs as $row ) {
    $cnt++;
    $update = $client->createUpdate();
    echo "{$cnt}/{$num} Query: file.id: {$row['fileid']}\n";
    $update->addDeleteQuery('file.id:'.$row['fileid']);
  //  $update->addCommit();
    $result = $client->update($update);
    echo 'Delete query executed'."\n";
    echo 'Query status: ' . $result->getStatus()."\n";
    echo 'Query time: ' . $result->getQueryTime()."\n";
  }
  $rs->Close();

  $update = $client->createUpdate();
  $update->addCommit();
  $result = $client->update($update);
  echo 'Commit query executed'."\n";
  echo 'Query status: ' . $result->getStatus()."\n";
  echo 'Query time: ' . $result->getQueryTime()."\n";
}

if( $doCache ) {
  $sql = "SELECT CONCAT( s.localpath, '/', f.localcopy ) AS cache FROM file f, session s WHERE f.sessionid=s.sessionid AND f.fileid IN ({$asql})";
  echo "{$sql}\n";
  $rs = $db->Execute( $sql );
  $cnt = 0;
  foreach( $rs as $row ) {
    $cnt++;
    $cache = $row['cache'];
    echo "{$cnt}/{$num} delete {$cache}\n";
    @unlink( "{$cache}.avconv.thumb.png" );
    @unlink( "{$cache}.imagick.thumb.png" );
    @unlink( "{$cache}.tika.txt.gz" );
    @unlink( "{$cache}" );
  }
  $rs->Close();
}
