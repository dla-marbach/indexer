#!/usr/bin/php

<?php

require_once( 'config.inc.php' );
include( 'db.inc.php' );
include( 'solr.inc.php' );

include( 'vendor/solarium-master/library/Solarium/Autoloader.php' );
include( 'vendor/Symfony/vendor/autoload.php' );

Solarium\Autoloader::register();

if( $argc < 3 ) die( "{$argv[0]} <id> <lock|unlock|red|yellow|green>\n" );

list( $sessionid, $fileid ) = explode( '.', $argv[1] );
$sessionid = intval( $sessionid );
$fileid = intval( $fileid );
if( !$sessionid || !$fileid ) die( "no valid id" );

$data = array();

switch( $argv[2] ) {
	case 'lock':
		$data['status.locked'] = true;
		$sql = "UPDATE `file` SET `lock`=1 WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'lock' ).")";	   break;
	case 'unlock':
		$data['status.locked'] = false;
		$sql = "UPDATE `file` SET `lock`=0 WHERE sessionid={$sessionid} AND fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'unlock' ).")";
	   break;
	case 'red':
	case 'yellow':
	case 'green':
		$data['status.status'] = $argv[2];
		$sql = "UPDATE `file` SET status=".$db->qstr( $argv[2] )." WHERE sessionid={$sessionid} and fileid={$fileid}";
		$sql_log = "INSERT INTO log VALUES ( NOW(), {$sessionid}, {$fileid}, ".$db->qstr( 'robot' ).", ".$db->qstr( 'status: '.$argv[2] ).")";
	   break;
	default:
	   die( "unknown status {$argv[2]}" );
}
$db->Execute( $sql );
$db->Execute( $sql_log );
updateDocumentSolarium( $db, $sessionid, $fileid, $data, $config );

?>