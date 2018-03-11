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

//gc_enable(); // Enable Garbage Collector

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

if( $pluginClass == 'indexer\\tika' ) {
	$sql = "SELECT sessionid FROM session s WHERE {$sessSQL}";
	$rs = $db->Execute( $sql );
	foreach( $rs as $row ) {
		$sessionid = $row['sessionid'];
		$cmd = $config['tika']." ".escapeshellarg( "jdbc:mysql://{$config['db']['server']}/{$config['db']['db']}?user={$config['db']['user']}&password={$config['db']['pwd']}&useSSL=false" )." {$sessionid}";
		echo "$cmd\n";
		passthru( $cmd );
	}
	return;
}

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
	SELECT f.*, s.basepath, s.localpath, s.datapath
	FROM `session` s, `file` f";
	foreach( $pluginClass::joins() as $short=>$dbname )
	{
		$sql .= "
	LEFT JOIN {$dbname} {$short} ON (f.sessionid={$short}.sessionid AND f.fileid={$short}.fileid) ";
	}
	$sql .= "
	WHERE f.sessionid = s.sessionid AND f.localcopy IS NOT NULL AND {$sessSQL} AND ".$pluginClass::where()."
	LIMIT 0,1000";
//	die();
	$recs = 0;
	do {

		echo "{$sql}\n";
			$rs = $db->Execute( $sql );
			$recs = 0;
			$data = array();
			foreach( $rs as $row ) {
				$data[] = $row;
				$recs++;
			}
			$rs->Close();



			foreach( $data as $row )
			{
				$sessionid = $row['sessionid'];
				$fileid = $row['fileid'];
				$basepath = $row['basepath'];
				$datapath = $row['datapath'];
				$fullpath = $row['path'].'/'.$row['name'];
				$localfile = $row['localpath'].'/'.$row['localcopy'];

				//if( $p % 1000 == 0 ) gc_collect_cycles();
				$now = time();
				$percent = ($p-$skip)*100/($num-$skip);
				$elapsed = time()-$start;
				$ges = $percent == 0 ? 0 : round(100*$elapsed/$percent);
				$rest = $ges - $elapsed;
				$percent = round( $percent );
				echo "{$p}/{$num} ({$percent}%) elapsed: ".sprintf('%02d:%02d:%02d', ($elapsed/3600),($elapsed/60%60), $elapsed%60).", rest: ".sprintf('%02d:%02d:%02d', ($rest/3600),($rest/60%60), $rest%60)." {$sessionid}:{$fileid} =========\n";
				$p++;


				$plugin->init( $db, $sessionid, $fileid, $datapath, $fullpath, $localfile );


			   if( !$update  &&  $plugin->check()) {
					$skip++;
					continue;
				}

			   try {
					$plugin->index( $update );
			   }
			   catch( \Exception $e ) {
					echo $e;
					continue;
			   }
			   $sql2 = "UPDATE `file` SET mtime=NOW() WHERE sessionid={$sessionid} AND fileid={$fileid}";
			   //echo "{$sql}\n";
			   $db->Execute( $sql2 );
			}
			echo "recs: {$recs}\n";
	} while( $recs == 1000 );
}
//gc_disable(); // Disable Garbage Collector
?>
