<?php

global $db, $update;

$sql = "UPDATE `file` SET
  readstate=".$db->qstr( 'ok' )."
  , filetype=".$db->qstr( 'other')."
  WHERE sessionid={$sessionid} AND fileid={$fileid}";
$db->Execute( $sql );

 ?>
