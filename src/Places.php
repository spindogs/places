<?php

namespace Spindogs\Places;

use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\PostalAddress;
use Spatie\SchemaOrg\GeoCoordinates;
use Spatie\SchemaOrg\AggregateRating;
use Spatie\SchemaOrg\Graph;
use Spindogs\Places\Utils;
use Exception;

class Places {

    const ADDRESS_MAP = [
        'premise' => 'addressLocality',
        'postal_town' => 'addressRegion',
        'country' => 'addressCountry',
        'postal_code' => 'postalCode'
    ];

    const DAY_MAP = [
        1 => 'Mo',
        2 => 'Tu',
        3 => 'We',
        4 => 'Th',
        5 => 'Fr',
        6 => 'Sa',
        7 => 'Su',
    ];

    /**
     * @var null|string
     */
    private $google_api_key;

    /**
     * @var null|string
     */
    private $place_id;

    /**
     * @var $pl_name string
     * @var $pl_url string
     * @var $pl_logo string
     * @var $pl_description string
     * @var $pl_image string
     * @var $pl_telephone string
     * @var $pl_has_map string
     */
    protected $pl_name, $pl_url, $pl_logo, $pl_description, $pl_image, $pl_telephone, $pl_has_map;

    /**
     * @var $pl_address PostalAddress
     */
    protected $pl_address;

    /**
     * @var $pl_geo_coordinate GeoCoordinates
     */
    protected $pl_geo_coordinate;

    /**
     * @var $pl_social_media array
     */
    protected $pl_social_media = [];

    /**
     * @var $pl_opening_hours array
     */
    protected $pl_opening_hours = [];

    /**
     * @var $google_response null|object
     */
    protected $google_response = null;

    /**
     * Creates object, if both Google API key and Place ID are set, almost all fields will be pre-populated.
     * @param null|string $api_key
     * @param null|string $place_id
     */
    public function __construct(?string $api_key = null, ?string $place_id = null) {
        $this->google_api_key = $api_key;
        $this->place_id = $place_id;

        if ($this->google_api_key !== null && $this->place_id !== null) {
            $response = Utils\HTTPRequest::performGooglePlaceQuery($this->google_api_key, $this->place_id);
            //$response = json_decode('{"html_attributions":[],"result":{"address_components":[{"long_name":"Abergorki Industrial Estate","short_name":"Abergorki Industrial Estate","types":["premise"]},{"long_name":"Treorchy","short_name":"Treorchy","types":["postal_town"]},{"long_name":"Rhondda Cynon Taff","short_name":"Rhondda Cynon Taff","types":["administrative_area_level_2","political"]},{"long_name":"Wales","short_name":"Wales","types":["administrative_area_level_1","political"]},{"long_name":"United Kingdom","short_name":"GB","types":["country","political"]},{"long_name":"CF42 6DL","short_name":"CF42 6DL","types":["postal_code"]}],"adr_address":"Abergorki Industrial Estate, <span class=\"extended-address\">Treorchy<\/span> <span class=\"postal-code\">CF42 6DL<\/span>, <span class=\"country-name\">UK<\/span>","formatted_address":"Abergorki Industrial Estate, Treorchy CF42 6DL, UK","formatted_phone_number":"01443 771333","geometry":{"location":{"lat":51.663272,"lng":-3.516515},"viewport":{"northeast":{"lat":51.6645433302915,"lng":-3.515001869708499},"southwest":{"lat":51.6618453697085,"lng":-3.517699830291503}}},"icon":"https:\/\/maps.gstatic.com\/mapfiles\/place_api\/icons\/shopping-71.png","id":"41fc25033b8b2294d9880c18b652b651440e5732","international_phone_number":"+44 1443 771333","name":"Thomas Lloyd Furniture","opening_hours":{"open_now":true,"periods":[{"close":{"day":1,"time":"1700"},"open":{"day":1,"time":"0900"}},{"close":{"day":2,"time":"1700"},"open":{"day":2,"time":"0900"}},{"close":{"day":3,"time":"1700"},"open":{"day":3,"time":"0900"}},{"close":{"day":4,"time":"1700"},"open":{"day":4,"time":"0900"}},{"close":{"day":5,"time":"1700"},"open":{"day":5,"time":"0900"}}],"weekday_text":["Monday: 9:00 AM \u2013 5:00 PM","Tuesday: 9:00 AM \u2013 5:00 PM","Wednesday: 9:00 AM \u2013 5:00 PM","Thursday: 9:00 AM \u2013 5:00 PM","Friday: 9:00 AM \u2013 5:00 PM","Saturday: Closed","Sunday: Closed"]},"photos":[{"height":1267,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAoAcpUhMkgZge_kayob0VPco-qJ5_sP6dPM1U0ATC7GzJOrPGFNPcVr3BnxsjMkfwdlyn0IycvB6nSi0VGcdgvbuTVFL4lz8D_VGjEQrt49kdIZnQT92Q_vpqzNmJUbCAEhBt6365caZYy-lxyGsNASBuGhTNEY2QGmThWzMc6ZUbl9A3l3wogA","width":1920},{"height":777,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAlR9wM1FKgs4V3hrTtnNAK8wwnyFF1EHDIbnXIA1_CcIMQ6NnuXI1ocTSp1DYfRlXJvweAEDZNzWarZWAoc22SlrezciGoypHd73KM2OTuimVp9Q3sBn4eZa2GcWDlFNaEhAZ3dISt3vzkzPLzNYa_DcgGhSRLkDq_H7G03qTdhWaA2QG7JU69g","width":1380},{"height":1333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAIL5n3XX325xCLc2U2kWfUrMa8z-eqgSAiNoAZ--lcf27nWfnwM4HN--EoW5B2UXJqxwktqsIqyHeQuJo1tH8cdset2JwJwOLk4-NPz7crH7hzRRV8OlzvbcQavTzQ5lFEhBiSIFTXFkT1R0z_spvIu0eGhRBWIJqjGxJvUINxZdGWZqh-sfnbg","width":2000},{"height":1500,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAO2EN5eKPs8OvyeQZXOCbRhU0aSel5NO2GrClVg-qjA-N9tZLxULjUAnNAsSZTnMTgO_KBGxWfuATKoXsDvGTaZU_QYJ07tBRlM5GPiFd3gIh1XCg8gDrgmBLdfGxYkxQEhC_x9xoYLwqs3vHlUEFK3VCGhTAUkIo0zJm7Exac_Tzf4oDUclmwA","width":1000},{"height":1333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAEC0ATxT_mij35aRWGOVB27kQ-Tg0wp4XKILZb3TdE13WLLCvIQkk2oOr-WDcsFXs0rAegzcb8n3ptNOLQV1JFx4c0lSCfRDg4yJtVfydXS2kLGkPfjJK3lHVoslRyJzbEhCZaxDpdw-nIpk6FfO4ZLhxGhQVXoGrx_cCSKKNwC1NLj6Hw_AsUg","width":2000},{"height":1333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAWdgplkHyxPQqSrLGdXYy-BgMT2P9xzeagp7sXsxqrJhEs8bZKloKQ8LwjsEe9f7-INM6il-HKhP8ldSYPGb0MWidZzK-PaFptB8FVZI3J2kq3dAg9VzcK-_NUw9gyCEwEhDrSsuFBG-LznqNgWkOYV7iGhTnDotVqgFQA9_wGY5MiO7f_LpPuA","width":2000},{"height":1333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAOv5mEau4Zvo7raRrlduI_vHr5nccBwLqFNL4P2vW8SMUbskA22c0TPlrxvVoNEHK3LsxIUS1ZhdcGYx6sf1PuWmXvxnqvEHeQzDDF9JkEHg3gIdxZOMI-_tXRtC7hMCzEhDr2ShAolok92qJB-MPPujuGhTLSAfL8UN8fwcPWv9QwhPIIVnpLA","width":2000},{"height":540,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/103515612885096880859\/photos\">Kathleen Phythian<\/a>"],"photo_reference":"CmRaAAAAhPsLcVUwCCbedVP4piF-b24XYmWHhxO1_mQsN2NPI0CEk9c23YlsHYZKhTwzfz_8v-MhwhOg5VfFLBMk-2WVtnG7Lj9HrGCMzPmZJ2AL_9RmEBMmNQgBA9oedk4nE-T2EhDIdSzJd_q_b59JjQb4Ft_dGhSG2Pmg_VIyCAArEgXOdkGMLG8j2A","width":960},{"height":1333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAAWXIxSOx0_BfHdBMjyf9FuIAMF-z3kWABw_BEOV_t5wQ2x5kWec1e66_NPupQUsbd8Nn8jOuaw2HNWkLjb84p-dtpc8faocxvXRu8P-LE2OxofF4Asg1a4NFW3W3LhLnZEhCFonk1MGlmnEIU_g1wQjpvGhRjKc0PLzeI8Hqt01euc1-ksazmPw","width":2000},{"height":1190,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/108276740430737755426\/photos\">Thomas Lloyd Furniture<\/a>"],"photo_reference":"CmRaAAAATqGKZGHcGU9qzdRlnDbF-FqiLeC8cXsvx4-pkL_TrqEaNmbIxwNyMLwS8A0sWUz40T0NQ-do3EqftFH2dvpztcej014gDdKO_agWVT8mjBqxLKcRNQYFl-tk7HKOK-dtEhB5Dz6IiqWXBKWvYvVq0o3pGhQyDM4dMU2MJHx9CIaD4oxEpd0zgQ","width":1920}],"place_id":"ChIJh-bJ9Ic_bkgRB6JOTduF8SI","plus_code":{"compound_code":"MF7M+89 Treorchy, United Kingdom","global_code":"9C3RMF7M+89"},"rating":4.5,"reference":"ChIJh-bJ9Ic_bkgRB6JOTduF8SI","reviews":[{"author_name":"Stephen Todd","author_url":"https:\/\/www.google.com\/maps\/contrib\/118304673239082974065\/reviews","language":"en","profile_photo_url":"https:\/\/lh4.ggpht.com\/-HMSr6OzLjOw\/AAAAAAAAAAI\/AAAAAAAAAAA\/sBzBunSKzx0\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"3 months ago","text":"Just had delivery of a Vintage Chesterfield Leather Sofa by two very helpful members of staff. Fantastic quality, cannot recommend highly enough.\n\nThank you.\n\nMr & Mrs Todd","time":1561751110},{"author_name":"Judith Ladd","author_url":"https:\/\/www.google.com\/maps\/contrib\/108032138910852496755\/reviews","language":"en","profile_photo_url":"https:\/\/lh5.ggpht.com\/-5xIg0ezcEKM\/AAAAAAAAAAI\/AAAAAAAAAAA\/LqVoau_iZFk\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"6 months ago","text":"We have had our 2 Aspen sofas for 6 years.. they were perfect the day they arrived and are still perfect today. 2 kids and a heavy handed husband have done their worst and the sofas still look great and are super comfortable. About to buy a footstool to match. Cannot recommend highly enough!","time":1553462356},{"author_name":"Jim Bo\u00dfo\u00dfoyo","author_url":"https:\/\/www.google.com\/maps\/contrib\/100150497706175445709\/reviews","language":"en","profile_photo_url":"https:\/\/lh3.ggpht.com\/-qroeXSWDFuo\/AAAAAAAAAAI\/AAAAAAAAAAA\/cyVMlSqkL2A\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"2 months ago","text":"Hot day vejjy nice smile. (:","time":1563890335},{"author_name":"frank baxter","author_url":"https:\/\/www.google.com\/maps\/contrib\/109454977703815922009\/reviews","language":"en","profile_photo_url":"https:\/\/lh3.ggpht.com\/-lov9Qpypgnw\/AAAAAAAAAAI\/AAAAAAAAAAA\/40oP9S9pE0M\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"5 months ago","text":"Our new Chesterfields, a three seater and a two seater have just arrived at our home in Spain and we are delighted with them....beautiful antique blue, so comfortable, brilliant quality.\nEven the carrier (Hardys International) who brought them to Spain was impressed by their quality....they have collected new sofas from all over the UK.\nThe customer service has been first class,  coordinated by Beverley.\nWe bought our first two Chesterfields from Thomas Lloyd almost 20 years ago and we have just given them to our daughter, still looking brand new.\nAbsolutely delighted !!!","time":1555840048},{"author_name":"Emma Cooper","author_url":"https:\/\/www.google.com\/maps\/contrib\/103598296513340506810\/reviews","language":"en","profile_photo_url":"https:\/\/lh4.ggpht.com\/-ibty2oF75y8\/AAAAAAAAAAI\/AAAAAAAAAAA\/QXvar4eOUOE\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"a year ago","text":"Had my suite for 14 years now and still love it and it\'s still as strong and comfy as the day it arrived. \nExcellent company and the delivery was amazing, it was as though it glided through my awkward shaped hallway and into my lounge.\nHighly recommended","time":1535589369}],"scope":"GOOGLE","types":["furniture_store","home_goods_store","store","point_of_interest","establishment"],"url":"https:\/\/maps.google.com\/?cid=2517940843618148871","user_ratings_total":30,"utc_offset":60,"vicinity":"Abergorki Industrial Estate, Treorchy","website":"https:\/\/www.thomaslloyd.com\/"},"status":"OK"}');
            if ($response && $response->status == 'OK') {
                $this->google_response = $response->result;
                $this->useGoogleResponse();
            }
        }
    }

    /**
     * Function to handle the Google place response parsing
     * @return void
     */
    private function useGoogleResponse(): void {
        $result = $this->google_response;

        // Set name
        $this->setName($result->name);
        $this->setUrl($result->website);
        $this->setTelephoneNumber($result->international_phone_number);

        // Assign address elements
        $address = new PostalAddress();

        $address->streetAddress($result->name);
        foreach (self::ADDRESS_MAP as $identifier => $method) {
            foreach ($result->address_components as $address_item) {
                if (in_array($identifier, $address_item->types)) {
                    $address->{$method}($address_item->long_name);
                }
            }
        }
        $this->setAddress($address);

        // Assign geo elements
        $geo = new GeoCoordinates();
        $geo->latitude($result->geometry->location->lat);
        $geo->longitude($result->geometry->location->lng);
        $this->setGeoCoordinates($geo);

        // Generate hours
        $hours = [];

        foreach ($result->opening_hours->periods as $period) {
            // Tentative, the data isn't totally clear on the json response and both open/close include days which is confusing
            $day = self::DAY_MAP[$period->open->day];
            $period->open->time = substr_replace((string)$period->open->time, ':', 2, 0);
            $period->close->time = substr_replace((string)$period->close->time, ':', 2, 0);
            $hours[] = $day.' '.$period->open->time.'-'.$period->close->time;
        }
        $this->setOpeningHours($hours);

        // Set Ratings
        $rating = new AggregateRating();
        $rating->bestRating(5);
        $rating->ratingCount($result->user_ratings_total);
        $rating->ratingValue($result->rating);
        $this->setAggregateRating($rating);

        // Set Map URL
        $this->setHasMapURL($result->url);
    }

    /**
     * Sets the businesses name
     * @param string $name
     */
    public function setName(string $name): void {
        $this->pl_name = $name;
    }

    /**
     * Sets the businesses URL
     * @param string $url
     */
    public function setUrl(string $url): void {
        $this->pl_url = $url;
    }

    /**
     * Sets the businesses logo
     * @param string $url
     */
    public function setLogo(string $url): void {
        $this->pl_logo = $url;
    }

    /**
     * Sets the description of the business
     * @param string $description
     */
    public function setDescription(string $description): void {
        $this->pl_description = $description;
    }

    /**
     * Sets the businesses social media (each item should be a URL to a social media page)
     * @param array $urls
     */
    public function setSocialMedia(array $urls): void {
        $this->pl_social_media = $urls;
    }

    /**
     * Sets the image to be used for the rich media card
     * @param string $url
     */
    public function setImage(string $url): void {
        $this->pl_image = $url;
    }

    /**
     * Sets the phone number for the business (must be in international format)
     * @param string $number
     */
    public function setTelephoneNumber(string $number): void {
        $this->pl_telephone = $number;
    }

    /**
     * Sets the address of the business
     * @param PostalAddress $addresses
     */
    public function setAddress(PostalAddress $addresses): void {
        $this->pl_address = $addresses;
    }

    /**
     * Sets the actual coordinates of the business
     * @param GeoCoordinates $geo
     */
    public function setGeoCoordinates(GeoCoordinates $geo): void {
        $this->pl_geo_coordinate = $geo;
    }

    /**
     * An array of opening hours, needs to be in a format of https://schema.org/openingHoursSpecification
     * @param array $hours
     */
    public function setOpeningHours(array $hours): void {
        $this->pl_opening_hours = $hours;
    }

    /**
     * Sets a direct URL to the map of the business
     * @param string $url
     */
    public function setHasMapURL(string $url): void {
        $this->pl_has_map = $url;
    }

    /**
     * Sets the average ratings of the business
     * @param AggregateRating $rating
     */
    public function setAggregateRating(AggregateRating $rating): void {
        $this->pl_rating = $rating;
    }

    /**
     * Validator to make sure the required fields have been populated
     * @throws Exception
     */
    private function preProcessValidation(): void {
        if (empty($this->pl_name))
            throw new Exception("Place name is unset, this is a required field");
        if (empty($this->pl_url))
            throw new Exception("Place URL is unset, this is a required field");
        if (empty($this->pl_address))
            throw new Exception("Place address is unset, this is a required field");
        if (empty($this->pl_telephone))
            throw new Exception("Place telephone number is unset, this is a required field");
        if (empty($this->pl_image))
            throw new Exception("Place image is unset, this is a required field");
    }

    /**
     * Generates the JSONLd output to be embedded in the <head> of the page
     * @throws Exception
     * @return string
     */
    public function generateJSONLd(): string {
        $this->preProcessValidation();

        $graph = new Graph();

        $org = Schema::organization();
        $org->name($this->pl_name);
        $org->url($this->pl_url);

        $local_business = Schema::localBusiness();
        $local_business->name($this->pl_name);
        $local_business->url($this->pl_url);
        $local_business->telephone($this->pl_telephone);
        $local_business->address($this->pl_address);
        $local_business->image($this->pl_image);

        if (!empty($this->pl_social_media))
            $org->sameAs($this->pl_social_media);

        if (!empty($this->pl_logo)) {
            $org->logo($this->pl_logo);
            $local_business->logo($this->pl_logo);
        }

        if (!empty($this->pl_description)) {
            $org->description($this->pl_description);
            $local_business->description($this->pl_description);
        }

        if (!empty($this->pl_geo_coordinate))
            $local_business->geo($this->pl_geo_coordinate);

        if (!empty($this->pl_has_map))
            $local_business->hasMap($this->pl_has_map);

        if (!empty($this->pl_opening_hours))
            $local_business->openingHours($this->pl_opening_hours);

        if (!empty($this->pl_rating))
            $local_business->aggregateRating($this->pl_rating);

        $graph->add($org);
        $graph->add($local_business);

        return $graph->toScript();
    }

}