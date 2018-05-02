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

$dirs = "0123456789abcdef";


$sql = "SELECT DISTINCT localpath FROM session";
$rs = $db->Execute( $sql );
foreach( $rs as $row )
{

	$basepath = $row['localpath'];

	if( !is_dir( $basepath )) mkdir( $basepath, 0755, true );
	if( !is_dir( $basepath.'/archive' )) mkdir( $basepath.'/archive', 0755, true );


	for( $i = 0; $i < strlen( $dirs ); $i++ )
	{
		$dir = $basepath.'/'.$dirs{$i};
		if( !is_dir( $dir )) mkdir( $dir, 0755 );
		for( $j = 0; $j < strlen( $dirs ); $j++ )
		{
			$dir2 = $basepath.'/'.$dirs{$i}.'/'.$dirs{$j};
			echo "{$dir2}\n";
			if( !is_dir( $dir2 )) mkdir( $dir2, 0777 );
			chmod( $dir2, 0777 );
		}
	}
}
?>
