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

function setarchive( $fileid, $archiveid ) {
  global $db;

  $sql = "SELECT fileid FROM file WHERE parentid={$fileid}";
  echo "{$sql}\n";
  $rs = $db->Execute( $sql );
  foreach( $rs as $row ) {
    $sql = "UPDATE file SET archiveid={$archiveid} WHERE parentid={$row['fileid']}";
    echo "{$sql}\n";
    $db->Execute( $sql );
    $sql = "SELECT fileid FROM file WHERE parentid={$row['fileid']}";
    echo "{$sql}\n";
    $rs2 = $db->Execute( $sql );
    foreach( $rs2 as $row2 ) {
        setArchive( $row2['fileid'], $archiveid );
    }
    $rs2->Close();
  }
  $rs->Close();
}

$sql = "SELECT fileid FROM file WHERE filetype=".$db->qstr( 'archive' );
$rs = $db->Execute( $sql );
foreach( $rs as $row ) {
  setArchive( $row['fileid'], $row['fileid'] );
}
$rs->Close();
