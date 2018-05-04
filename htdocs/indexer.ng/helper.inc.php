<?php

function sizeStrToInt( $str )
{
	$size = 0;
	if( is_numeric( $str )) return intval( $str );
	if( preg_match( "/([0-9]+)([kmgbKMGB])/", $str, $matches ))
	{
		$size = intval( $matches[1] );
		switch( strtolower( $matches[2] ))
		{
			case 'g':
				$size *= 1024;
			case 'm':
				$size *= 1024;
			case 'k':
				$size *= 1024;
			case 'b':
				//$size *= 1024;
				break;
			default:
				$size = 0;
		}
	}
	return $size;
}

function _format_bytes($a_bytes)
{
    if ($a_bytes < 1024) {
        return $a_bytes .' B';
    } elseif ($a_bytes < 1048576) {
        return round($a_bytes / 1024, 2) .' KiB';
    } elseif ($a_bytes < 1073741824) {
        return round($a_bytes / 1048576, 2) . ' MiB';
    } elseif ($a_bytes < 1099511627776) {
        return round($a_bytes / 1073741824, 2) . ' GiB';
    } elseif ($a_bytes < 1125899906842624) {
        return round($a_bytes / 1099511627776, 2) .' TiB';
    } elseif ($a_bytes < 1152921504606846976) {
        return round($a_bytes / 1125899906842624, 2) .' PiB';
    } elseif ($a_bytes < 1180591620717411303424) {
        return round($a_bytes / 1152921504606846976, 2) .' EiB';
    } elseif ($a_bytes < 1208925819614629174706176) {
        return round($a_bytes / 1180591620717411303424, 2) .' ZiB';
    } else {
        return round($a_bytes / 1208925819614629174706176, 2) .' YiB';
    }
}

function buildQuery( $_query, &$highlightQuery, $helper = null )
{
	$highlightQuery = null;
	$qArr = array();
	foreach( $_query as $q )
	{
	   foreach( $q as $name=>$value )
	   {
	     $value = str_replace( '##bs##', "\\",
					str_replace( '##plus##', '+',
						str_replace( '+', ' ',
							str_replace( "\\+", '##plus##',
								str_replace( "\\\\", "##bs##", trim( $value ))))));
		 if( !array_key_exists( $name, $qArr ))
		 {
			$qArr[$name] = array();
		 }
		 if( strlen( $value )) $qArr[$name][] = $value;
	   }
	}

	$solrQuery = '';
	foreach( $qArr as $name=>$values )
	{
		switch( $name )
		{
		   case 'group':
		      $field = 'session.group';
			  $andor = ' OR ';
			  break;
		   case 'mime':
		      $field = 'gvfs_info.mimetype';
			  $andor = ' OR ';
			  break;
		   case 'path':
		      $field = 'file.path';
			  $andor = ' AND ';
			  break;
			case 'ext':
		      $field = 'file.extension';
			  $andor = ' OR ';
			  break;
			case 'inventory':
		      $field = 'file.inventory';
			  $andor = ' OR ';
			  break;
		   case 'name':
		      $field = 'file.name';
			  $andor = ' OR ';
			  break;
		   case 'tikainfo':
		      $field = 'tika.fullinfo';
			  $andor = ' AND ';
			  break;
			case 'archiveid':
 		      $field = 'archive.id';
	 			  $andor = ' OR ';
	 			  break;
			case 'archivename':
 		      $field = 'archive.name';
 			  $andor = ' OR ';
 			  break;
		   case 'nsrl':
		      $field = 'nsrl.found';
			  $andor = ' AND ';
			  break;
		   case 'nsrl_apptype':
		      $field = 'nsrl.ApplicationType';
			  $andor = ' OR ';
			  break;
				case 'type':
 		      $field = 'file.filetype';
 			  $andor = ' OR ';
 			  break;
				case 'pronom':
			      $field = 'siegfried.id';
						$values = array_map( function ( $var ) { return 'pronom:'.$var; }, $values );
				  $andor = ' OR ';
				  break;
				case 'format':
			      $field = 'siegfried.format';
				  $andor = ' OR ';
				  break;
		   case 'session':
		      $field = 'session.id';
			  $andor = ' OR ';
			  break;
			case 'bestand':
		      $field = 'bestand.id';
			  $andor = ' OR ';
			  break;
		   case 'id':
		      $field = 'id';
			  $andor = ' OR ';
			  break;
		   case 'checksum':
		      $field = 'file.sha256';
			  $andor = ' OR ';
			  break;
		   case 'size':
		      $field = 'file.filesize';
			  $andor = ' OR ';
			  break;
		   case 'mtime':
		   case 'ctime':
		   case 'atime':
		      $field = 'file.file'.$name;
			  $andor = ' OR ';
			  break;
			case 'itime':
		      $field = 'file.archivetime';
 			  $andor = ' AND ';
 			  break;
		   case 'text':
		      $field = 'suggest';
			  $andor = ' AND ';
			  break;
		   case 'status':
		      $field = 'status.status';
			  $andor = ' OR ';
			  break;
		   case 'locked':
		      $field = 'status.locked';
			  $andor = ' OR ';
			  break;
		   default:
			  $field = 'all';
			  $andor = ' OR ';
		}
	   $or = '';
	   foreach( $values as $value )
	   {
	      if( !strlen( $value )) continue;

		  $value = trim( $value );

	      if( !strlen( $or )) $or = '(';
		  else $or .= $andor;

		  switch( $name )
		  {
			case 'id':
			case 'archiveid':
				if( $value{0} == '#' ) $value = substr( $value, 1 );
				$v = $helper ? $helper->escapePhrase( $value ) : str_replace( ' ', "\\ ", $value );
				$or .= "{$field}:".$v;
			   break;
			case 'path':
				if( $value == '/' )
				{
					$value =  '\/';
				}
				$v = $helper ? $helper->escapePhrase( $value ) : str_replace( ' ', "\\ ", $value );
				$or .= "{$field}:".$v;
				break;
			case 'mime':
				$v = $helper ? $helper->escapePhrase( $value ) : str_replace( ' ', "\\ ", $value );
				$or .= "( gvfs_info.mimetype:".$v;
				$or .= " OR libmagic.mimetype:".$v;
				$or .= " )";
				break;
			case 'size':
				if( preg_match( "/([0-9kmgbKMGB]*)-([0-9kmgbKMGB]*)/", $value, $matches ))
				{
					$from = sizeStrToInt( $matches[1] );
					$to = sizeStrToInt( $matches[2] );
					$or .= "{$field}:[{$from} TO {$to}]";
				}
				else
				{
					$or .= "{$field}:".sizeStrToInt( $value );
				}
				break;
			case 'mtime':
			case 'ctime':
			case 'atime':
			case 'itime':
				if( preg_match( "/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2}T[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2}T[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2})/", $value, $matches ))
				{
					$from = new DateTime( $matches[1] );
					$to = new DateTime( $matches[2] );
					$or .= "{$field}:[".($from->format( "Y-m-d\TH:i:s\Z"  ))." TO {".($to->format( "Y-m-d\TH:i:s\Z"  ))."]";
				}
				elseif( preg_match( "/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})\/([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})/", $value, $matches ))
				{
					$from = new DateTime( $matches[1] );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( $matches[2] );
					$to->setTime( 23, 59, 59 );
					$or .= "{$field}:[".($from->format( "Y-m-d\TH:i:s\Z"  ))." TO ".($to->format( "Y-m-d\TH:i:s\Z"  ))."]";
				}
				elseif( preg_match( "/^([0-9]{4}-[0-9]{1,2}-[0-9]{1,2})$/", $value, $matches ))
				{
					$from = new DateTime( $matches[1] );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( $matches[1] );
					$to->setTime( 23, 59, 59 );
					$or .= "{$field}:[".($from->format( "Y-m-d\TH:i:s\Z"  ))." TO ".($to->format( "Y-m-d\TH:i:s\Z"  ))."]";
				}
				elseif( preg_match( "/^([0-9]{4})$/", $value, $matches ))
				{
					$from = new DateTime( $matches[1].'-1-1' );
					$from->setTime( 0, 0, 0 );
					$to = new DateTime( $matches[1].'-12-31' );
					$to->setTime( 23, 59, 59 );
					$or .= "{$field}:[".($from->format( "Y-m-d\TH:i:s\Z"  ))." TO ".($to->format( "Y-m-d\TH:i:s\Z"  ))."]";
				}
				else $or .= "$value";
				break;
			case 'text':
				if( preg_match( "/#([0-9]+)\.([0-9]+)/", $value, $matches ))
				{
					$or .= "(session.id:{$matches[1]} AND file.id:{$matches[2]})";
				}
				elseif( $value{0} == '"' || $value{0} == "'" )
				{
					$or .= "{$field}:".str_replace( ' ', "\\ ", $value );
				}
				else
				{
					$value = str_replace( '\ ', ' ', $value );
					$values = explode( ' ', $value );
					$or .= '(';
					foreach( $values as $val )
					{
						$or .= "+{$field}:{$val} ";
					}
					$or .= ')';
				}
				break;
			default:
				$v = $helper ? $helper->escapePhrase( $value ) : str_replace( ' ', "\\ ", $value );
				$or .= "{$field}:".$v;
		  }


	   }
	   if( strlen( $or ))
	   {
		   $or .= ')';
		   if( strlen( $solrQuery )) $solrQuery .= ' AND ';
		   $solrQuery .= $or;
		   if( $field == 'suggest' ) $highlightQuery = $or;
	   }
	}
	return $solrQuery;
}


?>
