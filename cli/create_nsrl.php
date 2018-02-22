<?php

$db = dba_open( '/u01/indexer/hashes/nsrl/minimal/NSRLFile.db', 'c-', 'db4' );

$fp = fopen( '/u01/indexer/hashes/nsrl/minimal/NSRLFile.txt', 'r' );
$counter = 0;
while( $data = fgetcsv( $fp ))
{
	if( $data[0] === NULL ) continue;
	printf( "% 7u: %s\n", $counter++, $data[1] );
	dba_insert( $data[1], gzdeflate( json_encode( $data )), $db);
}

fclose( $fp );
dba_close( $db );

?>