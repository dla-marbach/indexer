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
namespace indexer;
require_once( 'config.inc.php' );
include( 'db.inc.php' );
include( 'zotero.inc.php' );

function syncToZotero( $inventoryno, $url, $data, $notes, $zoterogroup ) {
  global $db;

  $tmplDocument = getZoteroTemplate('document');
  $tmplNote = getZoteroTemplate('note');
  $tmplLinkAttch = getZoteroTemplate('attachment&linkMode=linked_url');

  $isNew = false;
  $objZotero = null;
  $itemkey = '';

  $sql = "SELECT * FROM inventoryno WHERE inventoryno=".$db->qstr( $inventoryno );
  //echo "{$sql}\n";
  $inventory = $db->GetRow( $sql );

  // falls es noch keinen eintrag gibt, dann das handle erzeugen
  if( !$inventory['ref'] ) {
    $sql = "REPLACE INTO mediathek_handle.handles_handle( handle, type, data )
      VALUES( ".$db->qstr( "20.500.11806/mediathek/inventory/".$inventoryno )."
        , ".$db->qstr( "URL" )."
        , ".$db->qstr( $url ).")";
    //echo "{$sql}\n";
    $db->Execute( $sql );

    $objZotero = clone $tmplDocument;
    $isNew = true;
  }else{
    $itemkey = ltrim($inventory['ref'],"zotero://");
  }


  if($isNew){
      $objZotero->title = $data['title'];
      if(!is_null($data['datum'])) $objZotero->date = $data['datum']->format('Y-m-d H:i:s');
      $objZotero->url = $data['url'];
      $objZotero->archive = $data['archive'];
      $objZotero->archiveLocation = $data['standort_archive'];
      $objZotero->callNumber = $data['signatur'];
      $itemkey = postItem($objZotero, $zoterogroup);
  }


  foreach( $notes as $note ) {
    // falls es noch keinen eintrag gibt, dann das handle erzeugen
    $sql = "REPLACE INTO mediathek_handle.handles_handle( handle, type, data )
      VALUES( ".$db->qstr( "20.500.11806/mediathek/inventory/{$inventoryno}/{$note['id']}" )."
        , ".$db->qstr( "URL" )."
        , ".$db->qstr( $note['url'] ).")";
    //echo "{$sql}\n";
    $db->Execute( $sql );
    $hdl = "http://hdl.handle.net/20.500.11806/mediathek/inventory/{$inventoryno}/{$note['id']}";
    if($isNew || !link_exists($zoterogroup, $itemkey, $hdl)){
      $objLinkAttch = clone $tmplLinkAttch;
      $objLinkAttch->title = $note['title'];
      $objLinkAttch->url = $hdl;
      $objLinkAttch->note = nl2br($note['text']);
      $objLinkAttch->parentItem = $itemkey;
      postNote($objLinkAttch,$zoterogroup);
    }
  }
  //print_r( $data );
  //print_r( $notes );
  //print_r( $zoterogroup );




  $sql = "UPDATE inventoryno
    SET synctime=NOW(), ref=".$db->qstr( "zotero://$itemkey" )."
    WHERE inventoryno=".$db->qstr( $inventoryno );
  //echo "{$sql}\n";
  $db->Execute( $sql );

}

if( $argc < 2 ) die( "{$argv[0]} <sessionid>\n" );

$p2 = strtolower(trim( $argv[1] ));
if( preg_match( '/^[0-9]+$/', $p2 ))
{
	$sessionid = intval( $p2 );
	$sessSQL = "s.sessionid=".$sessionid;
}
elseif( preg_match( '/^b[0-9]+$/', $p2 ))
{
	$bestandid = intval( substr( $p2, 1 ));
	$sessSQL = "s.bestandid=".$bestandid;
}
else
{
	$group = $p2;
	$sessSQL = "s.group=".$db->qstr( $group );
	if( $group == 'all' ) $sessSQL = '1=1';
}


$sql = "SELECT DISTINCT f.inventory
  FROM `file` f, session s, inventoryno i, bestand b
  WHERE b.zoterogroup IS NOT NULL AND b.bestandid=s.bestandid AND f.sessionid=s.sessionid AND f.inventory=i.inventoryno AND {$sessSQL} AND (i.synctime IS NULL OR i.synctime <= f.mtime)";

$pagesize = 1000;
$num = 0;
// repeat until there are no more newer files
do {
  echo "{$sql}\n";
  $rows = $db->GetAll( $sql." LIMIT 0, {$pagesize}" );
  $num = count( $rows );
  foreach( $rows as $row ) {
    $inventoryno = $row['inventory'];
    // get all files for inventory number
    echo "{$sql}\n";
    $sql = "SELECT b.zoterogroup, b.bestandid AS bestandid, b.name AS bestandname, s.name AS sessionname, b.zoterogroup, f.* 
        FROM `file` f, session s, bestand b 
        WHERE b.bestandid=s.bestandid AND s.sessionid=f.sessionid AND f.filetype in ('file', 'archive') AND f.inventory=".$db->qstr( $inventoryno ).'
         ORDER BY fileid ASC';
    $files = $db->GetAll( $sql );


    $bestandid = $files[0]['bestandid'];
    $bestandname = $files[0]['bestandname'];
    $sessionid = $files[0]['sessionid'];
    $fileid = $files[0]['fileid'];
    $zoterogroup = $files[0]['zoterogroup'];
    $id = "{$bestandid}.{$sessionid}.{$fileid}";
    $url = "https://ba14ns21408.adm.ds.fhnw.ch/indexer.ng/#inventory%3A%20{$inventoryno}\$\$1";

    $data = array();
    $data['title'] = "$inventoryno";
    if( $inventoryno{0} == 'S' ){
      $data['title'] .= " {$files[0]['name']}";
      $data['datum'] = new \DateTime( $files[0]['filemtime']);
    }
    $data['url'] = "http://hdl.handle.net/20.500.11806/mediathek/inventory/".$inventoryno;
    $data['archive'] = "Mediathek HGK";
    $data['standort_archive'] = $bestandname;
    $data['signatur'] = $inventoryno;
    //$data['abstract'] = count( $files )." items";

    $notes = array();
    $p = 0;
    $anz = count( $files );
    foreach( $files as $file ) {
      $p++;
      $furl = "https://ba14ns21408.adm.ds.fhnw.ch/indexer.ng/#checksum%3A%20{$file['sha256']}\$\$1";
      $ftitle = sprintf( "%03u", $p )."/".sprintf( "%03u" , $anz)." [#{$file['bestandid']}.{$file['sessionid']}.{$file['fileid']}] {$file['name']}";
      $fid = "{$file['bestandid']}.{$file['sessionid']}.{$file['fileid']}";
      $text = "Bestand {$file['bestandid']}: {$file['bestandname']}\n";
      $text .= "Session {$file['sessionid']}: {$file['sessionname']}\n";
      $text .= "File {$file['fileid']}: {$file['name']}\n";
      $text .= "Path: {$file['path']}\n";


      $sql = "SELECT * FROM info_libmagic WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']}";
      $row = $db->GetRow( $sql );
      if( is_array( $row ) && count( $row )) {
        $text .= "\nlibmagic:\n";
        $text .= "Mimetype: {$row['mimetype']}\n";
        $text .= "Encoding: {$row['mimeencoding']}\n";
        $text .= "Description: {$row['description']}\n";
      }
      $sql = "SELECT * FROM info_gvfs_info WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']}";
      if( is_array( $row ) && count( $row )) {
        $row = $db->GetRow( $sql );
        $text .= "\nGVFS-Info:\n";
        $text .= "Mimetype: {$row['mimetype']}\n";
      }
      $sql = "SELECT * FROM info_siegfried WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']} AND status='ok'";
      $row = $db->GetRow( $sql );
      if( is_array( $row ) && count( $row )) {
        $text .= "\nSiegfried:\n";
        if( $row['id']) $text .= "Pronom-ID: {$row['id']}\n";
        if( $row['format']) $text .= "Format: {$row['format']}\n";
        if( $row['version']) $text .= "Version: {$row['version']}\n";
        if( $row['mimetype']) $text .= "Mimetype: {$row['mimetype']}\n";
      }

      $sql = "SELECT * FROM info_imagick WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']} AND status='ok'";
      $row = $db->GetRow( $sql );
      if( is_array( $row ) && count( $row )) {
        $text .= "\nImage Magick:\n";
        $text .= substr( "{$row['fullinfo']}\n", 0, 2000 );
      }

      $sql = "SELECT * FROM info_tika WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']} AND status='ok'";
      $row = $db->GetRow( $sql );
      if( is_array( $row ) && count( $row )) {
        $text .= "\nApache Tika:\n";
        $text .= "Mimetype: {$row['mimetype']}\n";
        $text .= substr( "{$row['fullinfo']}\n", 0, 2000 );
      }

      $sql = "SELECT * FROM info_avconv WHERE sessionid={$file['sessionid']} AND fileid={$file['fileid']} AND status='ok'";
      $row = $db->GetRow( $sql );
      if( is_array( $row ) && count( $row )) {
        $text .= "\nFFMPEG:\n";
        $text .= substr( "{$row['fullinfo']}\n", 0, 2000 );
      }

      $notes[] = array( 'url'=>$furl, 'title'=>$ftitle, 'text'=>$text, 'id'=>$fid );
    }

    syncToZotero( $inventoryno, $url, $data, $notes, $zoterogroup );

  }
} while( $pagesize < $num );
