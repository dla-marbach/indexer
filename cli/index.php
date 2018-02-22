<?php

/*
TRUNCATE file;
TRUNCATE info_avconv;
TRUNCATE info_detex;
TRUNCATE info_gvfs_info;
TRUNCATE info_imagick;
TRUNCATE info_libmagic;
TRUNCATE info_tika;


*/

namespace indexer;

require_once( 'config.inc.php' );
include( 'db.inc.php' );

$pagesize = 20000;

gc_enable(); // Enable Garbage Collector

if( $argc < 3 ) die( "{$argv[0]} <plugin> <sessionid> [update]\n" );

$pluginClass = 'indexer\\'.trim( $argv[1] );
//$sessionid = intval( $argv[2] );

if( is_numeric( $argv[2] ))
{
	$sessionid = intval( $argv[2] );
	$sessSQL = "s.sessionid=".$sessionid;
}
else
{
	$group = trim( $argv[2] );
	$sessSQL = "s.group=".$db->qstr( $group );
	if( $group == 'all' ) $sessSQL = '1=1';
}

$startpage = 0;
$update = false;
if( $argc > 3 ) 
{
	$update = trim( strtolower( $argv[3] )) == 'update';
	if( intval( $argv[3] )) $startpage = intval( $argv[3] );
}

$p = 1;
$start = time();
$skip = 0;
$plugin = new $pluginClass( );


// php cannot handle large result sets. we have to page...

$sql = "
SELECT count(*)
FROM `session` s, `file` f ";
foreach( $pluginClass::joins() as $short=>$dbname )
{
	$sql .= "
	LEFT JOIN {$dbname} {$short} ON (f.sessionid={$short}.sessionid AND f.fileid={$short}.fileid) ";
}
$sql .= " 
WHERE f.sessionid = s.sessionid AND {$sessSQL} AND ".$pluginClass::where();

	$num = intval( $db->GetOne( $sql ));
$pages = ceil( $num/$pagesize );
$p = $startpage * $pagesize;
for( $page = $startpage; $page < $pages; $page++ )
{
	$startrec = $page*$pagesize;
	$sql = "
	SELECT f.*, s.basepath, s.localpath 
	FROM `session` s, `file` f";
	foreach( $pluginClass::joins() as $short=>$dbname )
	{
		$sql .= "
	LEFT JOIN {$dbname} {$short} ON (f.sessionid={$short}.sessionid AND f.fileid={$short}.fileid) ";
	}
	$sql .= "
	WHERE f.sessionid = s.sessionid AND {$sessSQL} AND ".$pluginClass::where()."
	LIMIT {$startrec}, {$pagesize}";
	echo "{$sql}\n";
	$rs = $db->Execute( $sql );
	  
	foreach( $rs as $row )
	{
		$sessionid = $row['sessionid'];
		$fileid = $row['fileid'];
		$basepath = $row['basepath'];
		$fullpath = $row['fullpath'];
		if( strlen( $row['localcopy'] )) $localfile = $row['localpath'].'/'.$row['localcopy'];
		else $localfile = null;

		if( $p % 1000 == 0 ) gc_collect_cycles();
		$now = time();
		$percent = ($p-$skip)*100/($num-$skip);
		$elapsed = time()-$start;
		$ges = $percent == 0 ? 0 : round(100*$elapsed/$percent);
		$rest = $ges - $elapsed;
		$percent = round( $percent );
		echo "{$p}/{$num} ({$percent}%) elapsed: ".sprintf('%02d:%02d:%02d', ($elapsed/3600),($elapsed/60%60), $elapsed%60).", rest: ".sprintf('%02d:%02d:%02d', ($rest/3600),($rest/60%60), $rest%60)." {$sessionid}:{$fileid} =========\n";
		$p++;

	   
		$plugin->init( $db, $sessionid, $fileid, $basepath, $fullpath, $localfile );
	   
	   
	   if( !$update  &&  $plugin->check()) { 
			$skip++; 
			continue; 
		}

	   try {
			$plugin->index( $update );
	   }
	   catch( \Exception $e )
	   {
			echo $e;
			continue;
	   }
	   $sql = "UPDATE `file` SET mtime=NOW() WHERE sessionid={$sessionid} AND fileid={$fileid}";
	   echo "{$sql}\n";
	   $db->Execute( $sql );
	}
	$rs->Close();
}	
gc_disable(); // Disable Garbage Collector
?>