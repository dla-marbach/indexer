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
$status = $_REQUEST['status'];
$id = "{$bestandid}.{$sessionid}.{$fileid}";

$user = array_key_exists( 'REMOTE_USER', $_SERVER ) ? $_SERVER['REMOTE_USER'] : 'unknown';

$isAdmin = inGroup( 'admin', $user );
$isEditor = inGroup( 'editor', $user );

//$isAdmin = true;

$ok = false;

$data = array();
switch( $status ) {
	case 'lock':
		$data['status.locked'] = true;
		$sql = "UPDATE `file` SET `lock`=1 WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( $user ).", ".$db->qstr( 'lock' ).")";
		$ok = $isAdmin;
	   break;
	case 'unlock':
		$data['status.locked'] = false;
		$sql = "UPDATE `file` SET `lock`=0 WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( $user ).", ".$db->qstr( 'unlock' ).")";
		$ok = $isAdmin;
	   break;
	case 'red':
	case 'yellow':
	case 'green':
	case 'unknown':
		$data['status.status'] = $status;
		$sql = "UPDATE `file` SET status=".$db->qstr( $status )." WHERE sessionid={$sessionid} and fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( $user ).", ".$db->qstr( 'status: '.$status ).")";
		$ok = $isAdmin || $isEditor;
	   break;
	default:
	   die( "<b>unknown status {$argv[2]}</b>" );
}
if( $ok ) {
	echo "<!-- $sql -->\n";
	$db->Execute( $sql );
	$db->Execute( $sql_log );
	updateDocumentSolarium( $db, $bestandid, $sessionid, $fileid, $data, $config );
}
$sql = "SELECT `lock`, `status` FROM `file` WHERE sessionid={$sessionid} AND fileid={$fileid}";
echo "<!-- $sql -->\n";
$row = $db->getRow( $sql );
$locked = $row['lock'];
$status = $row['status'];

ob_start();
if( $isAdmin ) {
?>
	<a href="#" onClick="updateStatus( '<?php echo $id; ?>', '<?php echo $locked ? 'unlock' : 'lock'; ?>' );">
		<img width="24px" src="icons/ampel/<?php echo $locked ? 'locked' : 'unlocked'; ?>.png">
	</a>
<?php }	else { ?>
	<img width="24px" src="icons/ampel/<?php echo $locked ? 'locked' : 'unlocked'; ?>.png">
<?php
}
if( $isAdmin || $isEditor ) {
?>
	<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'red' );">
		<img width="24px" src="icons/ampel/red<?php echo $status == 'red' ? '' : '_disabled'; ?>.png">
	</a>
	<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'yellow' );">
		<img width="24px" src="icons/ampel/yellow<?php echo $status == 'yellow' ? '' : '_disabled'; ?>.png">
	</a>
	<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'green' );">
		<img width="24px" src="icons/ampel/green<?php echo $status == 'green' ? '' : '_disabled'; ?>.png">
	</a>
	<a href="#" onClick="updateStatus( '<?php echo $id; ?>', 'unknown' );">
		<img width="24px" src="icons/ampel/unknown<?php echo $status == 'unknown' ? '' : '_disabled'; ?>.png">
	</a>
<?php }	else { ?>
	<img width="24px" src="icons/ampel/red<?php echo $status == 'red' ? '' : '_disabled'; ?>.png">
	<img width="24px" src="icons/ampel/yellow<?php echo $status == 'yellow' ? '' : '_disabled'; ?>.png">
	<img width="24px" src="icons/ampel/green<?php echo $status == 'green' ? '' : '_disabled'; ?>.png">
	<img width="24px" src="icons/ampel/unknown<?php echo $status == 'unknown' ? '' : '_disabled'; ?>.png">
<?php } ?>
<?php
$html = ob_get_clean();
echo $html;
?>
