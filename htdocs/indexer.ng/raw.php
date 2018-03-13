<?php

// TODO: Sicherheit fuer Pfad 

$name = trim( $_REQUEST['name'] );
$file = trim( $_REQUEST['file'] );
$mime = trim( $_REQUEST['mime'] );



if( !file_exists( $file ))
{
	header("HTTP/1.0 404 Not Found");
	
	echo "file not found: {$file}";
	exit;
}

header( "Content-type: {$mime}" );
if( strncmp( $mime, 'application/', 12 ) == 0 && $mime != 'application/pdf' )
{
	header("Content-Disposition: attachment; filename=\"{$name}\"");
}

readfile( $file );

?>