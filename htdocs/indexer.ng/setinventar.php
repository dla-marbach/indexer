<?php

require_once( 'config.inc.php' );

include( $indexerPath.'/db.inc.php' );
include( $indexerPath.'/solr.inc.php' );

include( 'groups.inc.php' );

header( "Content-type: text/html" );

list( $bestandid, $sessionid, $fileid ) = explode( '.', $_REQUEST['id'] );
$bestandid = intval( $bestandid );
$sessionid = intval( $sessionid );
$fileid = intval( $fileid );
if( !$sessionid || !$fileid ) die( "<b>no valid id</b>" );
$inventar = $_REQUEST['inventar'];
$id = "{$bestandid}.{$sessionid}.{$fileid}";

$user = array_key_exists( 'REMOTE_USER', $_SERVER ) ? $_SERVER['REMOTE_USER'] : 'unknown';

$isAdmin = inGroup( 'admin', $user );
$isEditor = inGroup( 'editor', $user );

//$isAdmin = true;

function setInventar( $bestandid, $sessionid, $fileid, $inventar ) {
  global $config, $db, $user;

  $data = array();
  $sql = "UPDATE `file` SET `inventory`=".$db->qstr( $inventar )." WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( $user ).", ".$db->qstr( 'inventory: '.$inventar ).")";
  $data['file.inventory'] = $inventar;

  echo "<!-- $sql -->\n";
  $db->Execute( $sql );
  $db->Execute( $sql_log );
  updateDocumentSolarium( $db, $bestandid, $sessionid, $fileid, $data, $config );

  if( substr( $inventar, 0, strlen( $config['inventory']['bundleprefix'])) == $config['inventory']['bundleprefix']) {
    $sql = "SELECT fileid FROM `file` WHERE sessionid={$sessionid} AND parentid={$fileid}";
    echo "\n<!-- $sql -->\n";
    $rs = $db->Execute( $sql );
    foreach( $rs as $row ) {
      setInventar( $bestandid, $sessionid, $row['fileid'], $inventar );
    }
    $rs->Close();
  }
}

if( $isEditor || $isAdmin ) {
  setInventar( $bestandid, $sessionid, $fileid, $inventar );
/*
  $data = array();
  $sql = "UPDATE `file` SET `inventory`=".$db->qstr( $inventar )." WHERE sessionid={$sessionid} AND fileid={$fileid}";
  $sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( $user ).", ".$db->qstr( 'inventory: '.$inventar ).")";
  $data['file.inventory'] = $inventar;

  echo "<!-- $sql -->\n";
  $db->Execute( $sql );
  $db->Execute( $sql_log );
  updateDocumentSolarium( $db, $bestandid, $sessionid, $fileid, $data, $config );
*/
}
?>
<span class="label label-default">
  <a style="color:#000000;" href="javascript:updateInventar( '<?php echo $id; ?>', '<?php echo $inventar == 'none' ? '' : $inventar; ?>' );">
    #<?php echo $inventar == '' ? 'none' : $inventar; ?>
  </a>
</span>
