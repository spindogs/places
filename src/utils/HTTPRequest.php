<?php

namespace Spindogs\Places\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HTTPRequest {

    const PLACE_API_URL = "https://maps.googleapis.com/maps/api/place/details/json";

    /**
     * Performs a HTTP request to Google for the specified place ID
     * @param string $api_key
     * @param string $place_id
     * @throws GuzzleException
     * @return mixed
     */
    public static function performGooglePlaceQuery(string $api_key, string $place_id) {
        $client = new Client();

        $http_params = http_build_query(['place_id' => $place_id, 'key' => $api_key]);
        $resource = $client->request('GET', self::PLACE_API_URL . '?' . $http_params);

        return json_decode($resource->getBody());
    }

}