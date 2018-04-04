<?php
require_once( 'config.inc.php' );

// facet categories => solr fields
$facets = array(
	'mime'		=>'gvfs_info.mimetype',
	'group'		=>'session.group',
	'session'	=>'session.id',
	'type'		=>'file.filetype',
	'name'		=>'file.name',
	'nsrl'		=>'nsrl.found',
	'nsrl_apptype'=>'nsrl.ApplicationType',
	'pronom' => 'siegfried.id',
	'format' => 'siegfried.format',
	'ext'		=>'file.extension',
	'status'	=>'status.status',
	'locked'	=>'status.locked',
);

header( "Content-type: text/json" );

$category = trim( $_REQUEST['category'] );
$searchTerm = trim( strtolower( $_REQUEST['searchTerm'] ));
$limit = intval( $_REQUEST['limit'] );
$query = $_REQUEST['query'];

$solarium = new Solarium\Client( $solarium_config );


if( $category == 'text' )
{
	if( !strlen( $searchTerm ))
	{
		echo json_encode( null );
		exit;
	}
	$query = $solarium->createSuggester();
	$query->setQuery( $searchTerm );
	$query->setDictionary( 'suggest' );
	$query->setCount( 10 );
	$query->setCollate( true );

	$resultset = $solarium->suggester($query);

	$result = array();
	foreach( $resultset as $term => $termresult )
	{
		foreach( $termresult as $r ) $result[] = $r;
	}
	echo json_encode( $result );
}
else
{
	$select = $solarium->createSelect();
	$helper = $select->getHelper();
	$facetSet = $select->getFacetSet();

	$_q = buildQuery( $query, $hlq, $helper );

	//$filterQuery = new Solarium\QueryType\Select\Query\FilterQuery();
	//$facet = new Solarium\QueryType\Select\Query\Component\Facet\Field();


	if( !strlen( $_q )) $_q = '*:*';

	$select->setQuery( $_q );
	$select->setRows( 0 )->setStart( 0 );
	$field = $facetSet->createFacetField( 'daFacet' );
	//$field->setSort( Solarium\QueryType\Select\Query\Component\Facet\Field::SORT_INDEX );
	$field->setSort( get_class( $field )::SORT_COUNT );


	if( strlen( $searchTerm )) {
		$field->setPrefix( $searchTerm );
	}

	// unknown category return nothing
	if( !array_key_exists( $category, $facets ))
	{
		echo json_encode( null );
		exit;
	}
	switch( $category )
	{
		case 'ext':
			$field->setSort(get_class( $field )::SORT_COUNT );
			break;
	}
	$field->setField( $facets[$category] );

	$result = $solarium->select( $select );
	$fresult = $result->getFacetSet()->getFacet('daFacet');
	$return = array();
	foreach( $fresult AS $value => $count)
	{
		if( !intval( $count )) continue;
		$value = preg_replace( array( '/pronom:/'), array(''), $value );
		$return[] = array( 'value'=>str_replace( ' ', '+', $value), 'label'=>"{$value} ({$count})" );
	}
	echo json_encode( $return );
}
//print_r( $facet_data );

?>
