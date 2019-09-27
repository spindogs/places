<?php

require('vendor/autoload.php');

// TODO: Actual documentation

use Spindogs\Places\Places;

$place = new Places("ABC123", "PlaceID 123");
$place->setImage('https://maps.gstatic.com/mapfiles/place_api/icons/shopping-71.png');
echo $place->generateJSONLd();