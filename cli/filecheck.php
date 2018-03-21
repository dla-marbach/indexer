<?php

namespace indexer;

require_once( 'config.inc.php' );
include( 'db.inc.php' );
$ADODB_COUNTRECS = true;

$sql = "SELECT ms.sessionid FROM indexer_main.session ms, indexer_production.session ps WHERE ms.sessionid=ps.sessionid AND ms.ignore=0 AND ms.name=ps.name";
$rs = $db->Execute( $sql );
foreach( $rs as $row ) {
  $sessionid = intval( $row['sessionid'] );
/*
  $sql = "SELECT pf.fileid
    , pf.name
    , pf.path
    , pf.filesize
    , pf.filetype
    , pf.sha256
    , mf.name as newname
    , mf.path as newpath
    , mf.fileid as newfileid
    , mf.filesize as newfilesize
    , mf.filetype as newfiletype
    , mf.sha256 as newsha256
    FROM indexer_production.`file` pf
    LEFT JOIN indexer_main.`file` mf
      ON (mf.sessionid=pf.sessionid AND mf.name = pf.name AND mf.path = pf.path )
    WHERE pf.sessionid={$sessionid}";
  echo $sql."\n";
*/
  $sql = "SELECT pf.fileid
    , pf.name
    , pf.path
    , pf.filesize
    , pf.filetype
    , pf.sha256
    FROM indexer_production.`file` pf
    WHERE pf.sessionid={$sessionid}";
  echo $sql."\n";
  $rs2 = $db->Execute( $sql );
  foreach( $rs2 as $row2 ) {
    $sql = "SELECT mf.name as newname
      , mf.path as newpath, mf.fileid as newfileid
      , mf.filesize as newfilesize, mf.filetype as newfiletype
      , mf.sha256 as newsha256
      FROM indexer_main.`file` mf
      WHERE mf.name = ".$db->qstr( $row2['name'])."
         AND mf.path = ".$db->qstr( $row2['path'])."
         AND mf.sessionid={$sessionid}";
    echo $sql."\n";
    $rs3 = $db->Execute( $sql );
    $cnt = 0;
    foreach( $rs3 as $row3 ) {
      $cnt++;
      $newfileid = $row3['newfileid'];
      $status = 'ok';
      if( $row2['sha256'] != $row3['newsha256'] ) $status = 'warn';
      elseif( $row2['filetype'] != $row3['newfiletype'] ) $status = 'warn';
      elseif( $row2['name'] != $row3['newname'] ) $status = 'warn';
      elseif( $row2['path'] != $row3['newpath'] ) $status = 'warn';

      $sql = "INSERT INTO filecheck VALUES( {$sessionid}, {$row2['fileid']}, {$newfileid}, ".$db->qstr( $status )." )";
      echo $sql."\n";
      $db->Execute( $sql );
    }
    $rs3->Close();
    if( $cnt == 0 ) {
      $sql = "INSERT INTO filecheck VALUES( {$sessionid}, {$row2['fileid']}, NULL, ".$db->qstr( 'error' )." )";
      echo $sql."\n";
      $db->Execute( $sql );
    }
  }
  $rs2->Close();
}
$rs->Close();
?>
