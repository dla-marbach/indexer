<?php
header( "Content-type: text/plain\n" );

require_once( '/home/indexer/bin/config.inc.php' );

include( 'vendor/solarium-master/library/Solarium/Autoloader.php' );
include( 'vendor/Symfony/vendor/autoload.php' );

Solarium\Autoloader::register();

$client = new Solarium\Client( $solarium_config );
$update = $client->createUpdate();
//echo $update->getHandler();
$doc = $update->createDocument();
//$doc->setVersion( 4 );
$doc->setKey( 'id', "4274.1756982" );
$doc->addField( 'limit', 42, $doc->getBoost(), \Solarium\QueryType\Update\Query\Document\Document::MODIFIER_SET );
$update->addDocument( $doc );
$rb = $update->getRequestBuilder();
echo $rb->getRawData( $update );

?>