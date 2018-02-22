<?php

namespace indexer;

require_once( 'config.inc.php' );
include( 'db.inc.php' );

while( true )
{
	$sql = "SELECT f.fileid, content, localpath, localcopy FROM session s, file f, info_antiword ia WHERE ia.sessionid=f.sessionid AND ia.fileid=f.fileid AND ia.sessionid=s.sessionid AND content IS NOT NULL AND hascontent IS NULL";
	echo $sql."\n";
	$rs = $db->Execute( $sql." LIMIT 0,1000" );
	if( $rs->RecordCount() == 0 ) die( "end reached" );
	foreach( $rs as $row )
	{
		$fileid = $row['fileid'];
		$content = $row['content'];
		$localpath = $row['localpath'];
		$localcopy = $row['localcopy'];
		
		if( $content )
		{
			$content = trim( $content );
			$fname = "{$localpath}{$localcopy}{$config['antiword_file_ext']}.gz";
			if( file_exists( $fname )) unlink( $fname );
			$gz = gzopen( $fname, 'wb9' );
			echo "{$fname}\n";
			gzwrite( $gz, $content );
			gzclose( $gz );
			$sql = "UPDATE info_antiword SET hascontent=1 WHERE fileid={$fileid}";
			$db->Execute( $sql );
		}
	}
	$rs->Close();
}
?>