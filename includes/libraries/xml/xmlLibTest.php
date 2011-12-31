<?php
	require( 'xmlLib.php' );
	
	$xml = new XMLToArray( 'http://www.slashdot.org/slashdot.xml', array(), array( 'story' => '_array_' ), true, false );
	print_r( $xml->getArray() );

	$array = new ArrayToXML( $xml->getArray(), $xml->getReplaced(), $xml->getAttributes() );
	print_r( $array->getXML() );
?>
