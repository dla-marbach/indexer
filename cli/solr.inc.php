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

function updateDocument( $db, $bestandid, $sessionid, $fileid, $newdata, $config )
{
	static $paths = array();
	$data = array();
	$data['id'] = "{$bestandid}.{$sessionid}.{$fileid}";
	foreach( $newdata as $key=>$value )
	{
		$data[$key] = array( 'set'=>$value );
	}
	if( isset( $paths[$sessionid] )) {
		$path = $paths[$sessionid];
	}
	else {
		$sql = "SELECT solrpath FROM session WHERE sessionid={$sessionid}";
		$path = $db->getOne( $sql );
		$paths[$sessionid] = $path;
	}
	$solrurl = "{$config['solr']['hostname']}:{$config['solr']['port']}{$path}/update?commitWithin=20000";
	$curl = "curl '{$solrurl}' -H 'Content-type:application/json' -d '[".json_encode( $data )."]' -o solr.out";
	//echo $curl."\n";
	exec( $curl );
}

function updateDocumentSolarium( $db, $bestandid, $sessionid, $fileid, $newdata, $config )
{
	global $config, $solarium_config;

	static $paths = array();
	$data = array();
	$data['id'] = "{$bestandid}.{$sessionid}.{$fileid}";
	foreach( $newdata as $key=>$value )
	{
		$data[$key] = array( 'set'=>$value );
	}
	if( isset( $paths[$sessionid] )) {
		$path = $paths[$sessionid];
	}
	else {
		$sql = "SELECT solrpath FROM session WHERE sessionid={$sessionid}";
		$path = trim( $db->getOne( $sql ));
		$paths[$sessionid] = $path;
		//error_log( "SELECT solrpath FROM session WHERE sessionid={$sessionid} -> {$path}" );
	}

	//if( strlen( $path )) $solarium_config['endpoint']['localhost']['path'] = $path;
	$client = new Solarium\Client( $solarium_config );

	$update = $client->createUpdate();
	$doc = $update->createDocument();
	$doc->setKey( 'id', "{$bestandid}.{$sessionid}.{$fileid}");
	foreach( $newdata as $key=>$value )
	{
		$doc->addField( $key, $value, $doc->getBoost(), \Solarium\QueryType\Update\Query\Document\Document::MODIFIER_SET );
	}
	$update->addDocument( $doc, null, 20000 );
	// add commit command to the update query
	$update->addCommit();

	$rb = $update->getRequestBuilder();
	error_log( $rb->getRawData( $update ));

	$result = $client->update($update);


	//echo '<b>Update query executed</b><br/>';
	//echo 'Query status: ' . $result->getStatus(). '<br/>';
	//	echo 'Query time: ' . $result->getQueryTime();
	//$solrurl = "{$config['solr']['hostname']}:{$config['solr']['port']}{$path}/update?commitWithin=20000";
	//$curl = "curl '{$solrurl}' -H 'Content-type:application/json' -d '[".json_encode( $data )."]' -o solr.out";
	//exec( $curl );
}

function addDocument( $db, $row, $client, $config, $echo = true )
{
	$bestandid = intval( $row['bestandid'] );
   $sessionid = intval( $row['sessionid'] );
	 $fileid = intval( $row['fileid'] );
   if( !$fileid || !$sessionid ) return;

//		   $counter++;
//		   if( $echo ) echo "{$counter}/{$num}\n";

   $suggest = '';
   $thumb = null;
   $tikacontent = false;

   $lock = $row['lock'];
   $status = $row['status'];
   $aclmeta = array();
   $aclpreview = array();
   $aclcontent = array();

   $aclmeta[] = "{$bestandid}/locked";
   $aclmeta[] = "dla/locked";
   $aclpreview[] = "{$bestandid}/locked";
   $aclpreview[] = "dla/locked";
   $acldata[] = "{$bestandid}/locked";
   $acldata[] = "dla/locked";
   if( !$lock ) {
     $aclmeta[] = "{$bestandid}/{$status}";
     $aclpreview[] = "{$bestandid}/{$status}";
     $acldata[] = "{$bestandid}/{$status}";
   }
   $id = "{$bestandid}.{$sessionid}.{$fileid}";

	 // get an update query instance
	 $update = $client->createUpdate();

   $doc = $update->createDocument();

   $doc->addField('id', $id);

   $doc->addField('status.locked', $row['lock']);
   $doc->addField('status.status', $row['status']);

	 foreach( $aclmeta as $acl ) {
		 $doc->addField( 'acl_meta', $acl );
	 }
	 foreach( $aclpreview as $acl ) {
		 $doc->addField( 'acl_preview', $acl );
	 }
	 foreach( $acldata as $acl ) {
		 $doc->addField( 'acl_data', $acl );
	 }

	 $doc->addField('bestand.id', $bestandid);
   $doc->addField('bestand.name', $row['bestandname']);
	 $doc->addField('session.id', $sessionid);
   $doc->addField('session.name', $row['sessionname']);
   $doc->addField('session.basepath', $row['basepath']);
   $doc->addField('session.localpath', $row['localpath']);
   $doc->addField('session.group', $row['group']);

   $doc->addField('file.id', $fileid);
   $doc->addField('file.parentid', $row['parentid']);
   $doc->addField('file.localcopy', $row['localcopy']);
   $doc->addField('file.level', $row['level']);
   $doc->addField('file.name', $row['name']);
   $doc->addField('file.path', $row['path']);
   $doc->addField('file.fullpath', $row['path'].'/'.$row['name']);
   $doc->addField('file.extension', pathinfo( $row['name'], PATHINFO_EXTENSION ));
   $doc->addField('file.filetype', $row['filetype']);
   if( strlen( $row['sha256'] )) $doc->addField('file.sha256', $row['sha256']);
   $doc->addField('file.filesize', $row['filesize']);
   $datetime = new DateTime( $row['filectime'] );
   $doc->addField('file.filectime', $datetime->format( "Y-m-d\TH:i:s" ).'Z');
   $datetime = new DateTime( $row['filemtime'] );
   $doc->addField('file.filemtime', $datetime->format( "Y-m-d\TH:i:s" ).'Z');
   $datetime = new DateTime( $row['fileatime'] );
   $doc->addField('file.fileatime', $datetime->format( "Y-m-d\TH:i:s" ).'Z');
   $doc->addField('file.stat', $row['stat']);
   $doc->addField('file.comment', $row['comment']);
   $datetime = new DateTime( $row['archivetime'] );
   $doc->addField('file.archivetime', $datetime->format( "Y-m-d\TH:i:s" ).'Z');
   $doc->addField('file.access', $row['access']);
   $doc->addField('file.relevance', $row['relevance']);

//   $doc->addField('status.status', $row['status']);
//   $doc->addField('status.locked', $row['lock']);


   $doc->addField('libmagic.mimetype', $row['mimetype']);
   $doc->addField('libmagic.mimeencoding', $row['mimeencoding']);
   $doc->addField('libmagic.description', $row['description']);

   $sql = "SELECT * FROM info_gvfs_info WHERE sessionid={$sessionid} AND fileid={$fileid}";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $row2['mimetype'] ) $doc->addField('gvfs_info.mimetype', $row2['mimetype']);
		if( $row2['fullinfo'] ) $doc->addField('gvfs_info.fullinfo', $row2['fullinfo']);
   }

   $sql = "SELECT * FROM info_tika WHERE sessionid={$sessionid} AND fileid={$fileid}";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $row2['mimetype'] ) $doc->addField('tika.mimetype', $row2['mimetype']);
		if( $row2['mimeencoding'] ) $doc->addField('tika.mimeencoding', $row2['mimeencoding']);
		if( $row2['fullinfo'] ) $doc->addField('tika.fullinfo', iconv("UTF-8", "UTF-8//IGNORE", $row2['fullinfo']));
		if( intval( $row2['hascontent'] ))
		{
			$fname = "{$row['localpath']}/{$row['localcopy']}{$config['tika_file_ext']}.gz";
			if( file_exists( $fname ) && filesize( $fname ) < $config['tika_gz_max_size'])
			{
				if( $echo ) echo "tika...";
				$fid = gzopen( $fname, 'r' );
				$content = gzread( $fid, $config['tika_max_size'] );
				gzclose( $fid );
//						ob_start();
//						readgzfile( $fname );
//						$content = ob_get_clean();
				$content = iconv("UTF-8", "UTF-8//IGNORE", $content);
				$doc->addField('tika.content', $content );
				$suggest = $content;
				$tikacontent = true;
			}
		}
   }

/*
   $sql = "SELECT * FROM info_detex WHERE sessionid={$sessionid} AND fileid={$fileid}";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {

		if( $row2['content'] ) {
			$suggest = iconv("UTF-8", "UTF-8//IGNORE", $row2['content']);
			$doc->addField('detex.content', $suggest);
		}
		//$suggest = $row2['content'];
   }
*/
   if( !$suggest )
   {
	   $sql = "SELECT * FROM info_antiword WHERE sessionid={$sessionid} AND fileid={$fileid} AND status='ok'";
	   $row2 = $db->getRow( $sql );
	   if( $row2 && is_array( $row2 ) && count( $row2 ))
	   {
			$fname = "{$row['localpath']}/{$row['localcopy']}.antiword.gz";
			if( file_exists( $fname ))
			{
				ob_start();
				readgzfile( $fname );
				$content = ob_get_clean();
				$content = iconv("UTF-8", "UTF-8//IGNORE", $content);
				$doc->addField('antiword.content', $content );
				$suggest = $content;
				//$tikacontent = true;
			}
	   }
   }

/*
   $sql = "SELECT * FROM info_xscc WHERE sessionid={$sessionid} AND fileid={$fileid} AND status='ok'";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $row2['content'] )
		{
			$content = iconv("UTF-8", "UTF-8//IGNORE", $row2['content']);
			$doc->addField('xscc.content', $content );
			if( !$suggest ) $suggest = $content;
		}
   }
*/

   $sql = "SELECT * FROM info_imagick WHERE sessionid={$sessionid} AND fileid={$fileid} AND status='ok'";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $row2['width'] ) $doc->addField('imagick.width', $row2['width']);
		if( $row2['height'] ) $doc->addField('imagick.height', $row2['height']);
		if( $row2['fullinfo'] ) $doc->addField('imagick.fullinfo', $row2['fullinfo']);
		$fname = "{$row['localpath']}/{$row['localcopy']}".'.imagick.thumb.png';
		if( file_exists( $fname ))
		{
			$thumb = base64_encode( file_get_contents( $fname ));
		}
		//if( strlen( $row2['thumb'] )) $thumb = base64_encode( $row2['thumb'] );
   }

   $sql = "SELECT * FROM info_avconv WHERE sessionid={$sessionid} AND fileid={$fileid} AND status='ok'";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $row2['fullinfo'] ) $doc->addField('avconv.fullinfo', $row2['fullinfo']);
		$fname = "{$row['localpath']}/{$row['localcopy']}".'.avconv.thumb.png';
		if( file_exists( $fname ))
		{
			$thumb = base64_encode( file_get_contents( $fname ));
		}
//		if( strlen( $row2['thumb'] )) $thumb = base64_encode( $row2['thumb'] );
   }

   $sql = "SELECT info.ProductCode, np.ProductName, np.ProductVersion, np.ApplicationType, np.Language, nm.MfgName, no.OpSystemName, no.OpSystemVersion, nm2.MfgName AS OpMfgName FROM info_nsrl info, NSRLProd np, NSRLOS no, NSRLMfg nm, NSRLMfg nm2 WHERE info.ProductCode=np.ProductCode AND np.OpSystemCode=no.OpSystemCode AND np.MfgCode=nm.MfgCode AND no.MfgCode=nm2.MfgCode AND sessionid={$sessionid} AND fileid={$fileid} AND info.ProductCode IS NOT NULL";
   $row2 = $db->getRow( $sql );
   if( $row2 && is_array( $row2 ) && count( $row2 ))
   {
		if( $echo ) echo "nsrl...";
		$doc->addField('nsrl.found', true);
		if( strlen( $row2['ProductCode'] )) $doc->addField('nsrl.ProductCode', $row2['ProductCode']);
		if( strlen( $row2['ProductName'] )) $doc->addField('nsrl.ProductName', $row2['ProductName']);
		if( strlen( $row2['ProductVersion'] )) $doc->addField('nsrl.ProductVersion', $row2['ProductVersion']);
		if( strlen( $row2['ApplicationType'] )) $doc->addField('nsrl.ApplicationType', $row2['ApplicationType']);
		if( strlen( $row2['Language'] )) $doc->addField('nsrl.Language', $row2['Language']);
		if( strlen( $row2['MfgName'] )) $doc->addField('nsrl.MfgName', $row2['MfgName']);
		if( strlen( $row2['OpSystemName'] )) $doc->addField('nsrl.OpSystemName', $row2['OpSystemName']);
		if( strlen( $row2['OpSystemVersion'] )) $doc->addField('nsrl.OpSystemVersion', $row2['OpSystemVersion']);
		if( strlen( $row2['OpMfgName'] )) $doc->addField('nsrl.OpMfgName', $row2['OpMfgName']);
   }
   else
   {
		$doc->addField('nsrl.found', false);
   }


   if( $thumb ) $doc->addField('thumb', $thumb );
   if( strlen( trim( $suggest )))
   {
	if( $echo ) echo "suggest...";
	$suggest = iconv("UTF-8", "UTF-8//IGNORE", trim( $suggest));
	$doc->addField('suggest', $suggest );
	$doc->addField('shorttext', @iconv("UTF-8", "UTF-8//IGNORE", substr( $suggest, 0, 1024 )));
	}

   try {
	if( $echo ) echo "add...";
	$updateResponse = $update->addDocument($doc, true, 10000);
	$result = $client->update($update);
	}
	catch( \Exception $e )
	{
		if( $echo ) echo $e;
		//print_r( $doc->toArray() );
		die();
	}

	echo 'Update query executed'."\n";
	echo 'Query status: ' . $result->getStatus()."\n";
	echo 'Query time: ' . $result->getQueryTime()."\n";


   if( $echo ) echo "\n";

   //print_r( $row );
   //print_r( $doc->toArray());
//   if( $counter > 100 ) break;
}
?>
