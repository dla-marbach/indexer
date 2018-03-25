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

$pagesize = 20000;

gc_enable(); // Enable Garbage Collector


if( $argc < 2 ) die( "{$argv[0]} <sessionid|group>\n" );

if( is_numeric( $argv[1] ))
{
	$sessionid = intval( $argv[1] );
	$sessSQL = "s.sessionid=".$sessionid;
}
else
{
	$group = trim( $argv[1] );
	$sessSQL = "s.group=".$db->qstr( $group );
	if( $group == 'all' ) $sessSQL = '1=1';
}

if( $argc > 2 ) $update = trim( strtolower( $argv[2] )) == 'update';
else $update = false;

$sql = 	"SELECT sessionid, solrpath, solrtime FROM session s WHERE ".$sessSQL;
echo "{$sql}\n";
$srs = $db->Execute( $sql );

foreach( $srs as $srow )
{
	$config['solr']['path'] = $srow['solrpath'];
	$client = new \Solarium\Client($solarium_config);

	$p = 1;
	$start = time();

	$sql = "
	SELECT count(*)
	FROM `session` s, `file` f
	LEFT JOIN `info_libmagic` `ilm` ON ((`f`.`sessionid` = `ilm`.`sessionid`) and (`f`.`fileid` = `ilm`.`fileid`))
	WHERE f.sessionid = s.sessionid
	  AND s.sessionid = {$srow['sessionid']}
	  AND ( f.mtime > f.solrtime OR f.solrtime IS NULL)";
	echo "{$sql}\n";

	$num = intval( $db->GetOne( $sql ));

	$recs = 0;

	$sql = "
	SELECT b.name AS bestandname, f.*, ilm.*, s.bestandid, s.basepath, s.name AS sessionname, s.localpath, s.group
	FROM bestand b, `session` s, `file` f
	LEFT JOIN `info_libmagic` `ilm` ON ((`f`.`sessionid` = `ilm`.`sessionid`) and (`f`.`fileid` = `ilm`.`fileid`))
	WHERE f.sessionid = s.sessionid
	AND s.bestandid = b.bestandid
	AND s.sessionid = {$srow['sessionid']}
	AND ( f.mtime > f.solrtime OR f.solrtime IS NULL)
	LIMIT 0, {$pagesize}";

	do {
		$solrtime = time();
		$rs = $db->Execute( $sql );
		echo "{$sql}\n";
		$recs = 0;
		$data = array();
		foreach( $rs as $row ) {
			$data[] = $row;
			$recs++;
		} // foreach( $rs as $row )
		$rs->Close();

		foreach( $data as $row )
		{

			$bestandid = intval( $row['bestandid'] );
			$sessionid = intval( $row['sessionid'] );
		   $fileid = intval( $row['fileid'] );
		   if( !$fileid || !$sessionid ) continue;

		   if( $p % 1000 == 0 ) gc_collect_cycles();
			$now = time();
			$percent = ($p)*100/($num);
			$elapsed = time()-$start;
			$ges = round(100*$elapsed/$percent);
			$rest = $ges - $elapsed;
			$percent = round( $percent );
			echo "{$p}/{$num} ({$percent}%) elapsed: ".sprintf('%02d:%02d:%02d', ($elapsed/3600),($elapsed/60%60), $elapsed%60).", rest: ".sprintf('%02d:%02d:%02d', ($rest/3600),($rest/60%60), $rest%60)." {$sessionid}:{$fileid} ============\n";
			$p++;

			try {
				addDocument( $db, $row, $client, $config, true );
				$sql = "UPDATE file SET solrtime=FROM_UNIXTIME({$solrtime}) WHERE sessionid={$sessionid} AND fileid={$fileid}";
				//echo "{$sql}\n";
				$db->Execute( $sql );
			}
			catch( \Exception $e ) {
				echo "addDocument failed: ".$e->getMessage();
			}
			if( $p % 1000 == 0 )
			{
				echo "> commit...\n";
				$update = $client->createUpdate();
				$update->addCommit();
				$result = $client->update($update);
			} // if( $p % 1000 == 0 )
		} // foreach( $data as $row )

		echo "recs: {$recs}\n";
	} while( $recs == $pagesize );

	echo "> commit...\n";
	$update = $client->createUpdate();
	$update->addCommit();
	$result = $client->update($update);
	//$client->commit();
	$sql = "UPDATE session SET solrtime=FROM_UNIXTIME({$start}) WHERE sessionid={$srow['sessionid']}";
	echo "{$sql}\n";
	$db->Execute($sql);
} // foreach( $srs as $srow )
$srs->Close();

?>
