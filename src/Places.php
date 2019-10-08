<?php

namespace Spindogs\Places;

use Spatie\SchemaOrg\ContactPoint;
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
	 * @var $pl_contact_point ContactPoint
	 */
    protected $pl_contact_point;

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
	 *
	 * An example response is commented out for testing
	 *
     * @param null|string $api_key
     * @param null|string $place_id
     */
    public function __construct(?string $api_key = null, ?string $place_id = null) {
        $this->google_api_key = $api_key;
        $this->place_id = $place_id;

        if ($this->google_api_key !== null && $this->place_id !== null) {
            $response = Utils\HTTPRequest::performGooglePlaceQuery($this->google_api_key, $this->place_id);
            //$response = json_decode('{"html_attributions":[],"result":{"address_components":[{"long_name":"54","short_name":"54","types":["street_number"]},{"long_name":"Bute Street","short_name":"Bute St","types":["route"]},{"long_name":"Cardiff","short_name":"Cardiff","types":["postal_town"]},{"long_name":"Cardiff","short_name":"Cardiff","types":["administrative_area_level_2","political"]},{"long_name":"Wales","short_name":"Wales","types":["administrative_area_level_1","political"]},{"long_name":"United Kingdom","short_name":"GB","types":["country","political"]},{"long_name":"CF10 5AF","short_name":"CF10 5AF","types":["postal_code"]}],"adr_address":"<span class=\"street-address\">54 Bute St<\/span>, <span class=\"locality\">Cardiff<\/span> <span class=\"postal-code\">CF10 5AF<\/span>, <span class=\"country-name\">UK<\/span>","formatted_address":"54 Bute St, Cardiff CF10 5AF, UK","formatted_phone_number":"029 2048 0720","geometry":{"location":{"lat":51.466411,"lng":-3.1660516},"viewport":{"northeast":{"lat":51.46773733029149,"lng":-3.164800719708498},"southwest":{"lat":51.4650393697085,"lng":-3.167498680291502}}},"icon":"https:\/\/maps.gstatic.com\/mapfiles\/place_api\/icons\/generic_business-71.png","id":"61a0bdf343b9f5610d144906d8ab3675f9847d89","international_phone_number":"+44 29 2048 0720","name":"Spindogs","opening_hours":{"open_now":true,"periods":[{"close":{"day":1,"time":"1730"},"open":{"day":1,"time":"0900"}},{"close":{"day":2,"time":"1730"},"open":{"day":2,"time":"0900"}},{"close":{"day":3,"time":"1730"},"open":{"day":3,"time":"0900"}},{"close":{"day":4,"time":"1730"},"open":{"day":4,"time":"0900"}},{"close":{"day":5,"time":"1700"},"open":{"day":5,"time":"0900"}}],"weekday_text":["Monday: 9:00 AM \u2013 5:30 PM","Tuesday: 9:00 AM \u2013 5:30 PM","Wednesday: 9:00 AM \u2013 5:30 PM","Thursday: 9:00 AM \u2013 5:30 PM","Friday: 9:00 AM \u2013 5:00 PM","Saturday: Closed","Sunday: Closed"]},"photos":[{"height":3840,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAdfC4XQWosL87Zd9DuTVbRosK2OttqCEU98ffGHedQ33gDPqulVf1Ke1YV1iad15S7EvRQpSa81Fznc6IaF3F-HPR0t1xZrfCZhNdg0GThLFtrHfxwi5q474E95d8UABxEhC6oSsb5ShadZzNgLIiPINxGhSfvPonGuqHoSt1g_i4us_LVjeu1Q","width":5760},{"height":3333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAzETJ6ROMv-9roL-y29MeRMyDTW8DyiVZpgCX5j-p1zviXsEHnjovCX79safTL58fNvxr0UeLDNAHjyAiFkKnQ5IyR_wyHjpFaxIbAjstF30QXxXOe5JXCbQ974iiU2bbEhB0Bo6dMZVicju0FTqcJ7K_GhQAWCq1NVYdjTJluyly6gS7IzVH3Q","width":5000},{"height":3840,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAt0gZYuLOlctRK_o7yII7R7Vo61RvGvJvosdqN9s5-hb6LVTEqY-Wd4vVHKgTeTWECDc768MnD6DOmKjQeGKgGjlMadjS4Ac3IT6nwdn8UwVgD8Q6NO3jonD3eKswlTKAEhDCcC3eCMK_sMYzacTaCEzFGhT5gMa2kT6E_xGz4TgcL-O0W9MrZg","width":5760},{"height":3333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAcx9zQB3zKe33f7SmxmY9A57k8aPotqfJCQt6_AeidaAz3-vUjfP3mQvakZHPxA6coOy0t31McxyWps8ADGv54gLr_hYRNkIvKzjq3lYrTvG8uEKyw1lM7vsYhnlrsJUIEhCJSHsBblp0h-EHtmrpgfjgGhSj0-ELX6ORCYceWlVkaYNj5OZ_lg","width":5000},{"height":437,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAcju4hMzaknY8m2QWmOPfOdgojYuywFPzUZlzxJY2xhC5fsRnVMAOprlCfUew2wMeSzYDZcSIGnB9yxARGTNO-BBuU_qvWk3v3f_zrpTJ4EWUL9kET1Nk0xcoctcpx3fTEhBeRkVXEEAJlGq9j9bPmO4DGhQKAt6cYXXfBLt9QcHAT1VYbXpJqQ","width":960},{"height":3840,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/116369362047103291012\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAFYqM2NC6RYBDOvpsA4wxC7eWuhAVon4eZxrGp0pvr_PS4MLdeisWE9R_tq6dqjEOZglquDzIySq-BwVzYvvUZjixxCQ4xQ1bfWkUUVhzLJEb7Yg6IqGXhhZwBnTxzobeEhB1zYKec4FhjGMeSTJeKF9pGhTaOFTALC5I3RYLXknujpyH5FiYgg","width":5760},{"height":3840,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/116369362047103291012\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAenm-KU2cCT5PoAhCa2Sm0bKpopEv4dYawoD5lFC45udEQhH2Z16vaI47-st1TByKs29OEcCFkz1Vr5yqrI34UPWW6I4JwZQcPuFYwAfdrgqp_q2knJVxIULG-Dx5TFFSEhD46DVYOw9o_kIgbG-E_KCdGhQUOmN-1AZmIIgEMn_-GoHPKIWzIQ","width":5760},{"height":3709,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/116369362047103291012\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAA6FyJAEUsw_LicsYJj1LQNFBu1ONBIysRib4ZuXyw_Qi03sy9e-H_hZjupX_9872akZ18GHfYDyKyzZ8Z0Kf0CLex-Y4X326RHO0PuY3V9o8etjYgK1FAlSsgJfV1sjBjEhDDWJedM48i6jDDZQQO4h9oGhTLzMIqKzfxHfHgr-iwgJdPbMPuMg","width":5564},{"height":3333,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAARnqzGgDq3RGaeqz0kp2SYlG5Y1diceYwM7rRJOcjqC3tD_ov8P_Anmeg8KyznN3VvCYhzuahCKEbGl8WlZW3oGmbRIjACRGQA9Jd2w6TEfbuvpepMFP_4Ndssr6feIJ5EhCRvUOgfH7pFNHLZS2bhwA7GhQ8D6TzyLqs9di_IWxeZtbakGWZmA","width":5000},{"height":3840,"html_attributions":["<a href=\"https:\/\/maps.google.com\/maps\/contrib\/110229124428129862960\/photos\">Spindogs<\/a>"],"photo_reference":"CmRaAAAAJ4exQ6ifZXjHcBfgjrInfNr1EE4T5XBh5KcIN_mGgYwXe4_KXieUpxwg382ePJGMm6HkYi-K1z-N685IWYWeBdselM7TOyDt4uGspzo0Y6a-tCq5oGVLR8HXbYn_b5MAEhCGXIYg_RPON6Ym5XtgFWdeGhRcq8iLby6xGNderLqhLURrsjC2Yg","width":5760}],"place_id":"ChIJK7_8bDYDbkgREXPRuCnmM3A","plus_code":{"compound_code":"FR8M+HH Cardiff, United Kingdom","global_code":"9C3RFR8M+HH"},"rating":5,"reference":"ChIJK7_8bDYDbkgREXPRuCnmM3A","reviews":[{"author_name":"Liam Darkside","author_url":"https:\/\/www.google.com\/maps\/contrib\/103708721103535619018\/reviews","language":"en","profile_photo_url":"https:\/\/lh5.ggpht.com\/-Sv1Ytpn_4ew\/AAAAAAAAAAI\/AAAAAAAAAAA\/0D9gviKPKZM\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"4 months ago","text":"Fantastic company, offered great advice, especially from Dave Morgan.  That guy is a legend, I wish they had 10 of him.  He really knows his stuff on a technical level but he is also just a top bloke.","time":1559145211},{"author_name":"Houston Mapstone","author_url":"https:\/\/www.google.com\/maps\/contrib\/107455956974572253438\/reviews","language":"en","profile_photo_url":"https:\/\/lh6.ggpht.com\/-ZfP_oBfcUVU\/AAAAAAAAAAI\/AAAAAAAAAAA\/hCQWr0ftQmo\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"5 months ago","text":"Chelsea delivered an excellent SEO training session, very informative and helpful.","time":1556197557},{"author_name":"Tara Peters","author_url":"https:\/\/www.google.com\/maps\/contrib\/106517777175719144692\/reviews","language":"en","profile_photo_url":"https:\/\/lh4.ggpht.com\/-Yqx7-NFne2Y\/AAAAAAAAAAI\/AAAAAAAAAAA\/t2o4DsLO7yo\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"a year ago","text":"Spindogs are a lovely company to be involved with. Each member of the team is incredibly friendly and welcoming. I have had the privilege to work here for a few weeks as an intern. The whole team have made my experience thoroughly enjoyable, and I have really felt like part of the crew. It's so important to work in an environment where you feel comfortable and happy, which is exactly the vibes that you get in and out of the office here. Thank you guys! :)","time":1516111149},{"author_name":"Ian Jolly","author_url":"https:\/\/www.google.com\/maps\/contrib\/111718589181691770368\/reviews","language":"en","profile_photo_url":"https:\/\/lh3.ggpht.com\/-zIKaCkj7Dlo\/AAAAAAAAAAI\/AAAAAAAAAAA\/55tfxr99ZCo\/s128-c0x00000000-cc-rp-mo-ba3\/photo.jpg","rating":5,"relative_time_description":"a year ago","text":"Great guys who do some exiting stuff. Such a fun company to work with and really get the creative juices flowing. Will definitely add value to your business with them on side.","time":1512647759},{"author_name":"Laura Lowe","author_url":"https:\/\/www.google.com\/maps\/contrib\/116024385364385977687\/reviews","language":"en","profile_photo_url":"https:\/\/lh5.ggpht.com\/-b0Pk9ek2mVQ\/AAAAAAAAAAI\/AAAAAAAAAAA\/GTxpbhOh9bM\/s128-c0x00000000-cc-rp-mo\/photo.jpg","rating":5,"relative_time_description":"a year ago","text":"THE most energised, forward thinking yet down to earth company I have ever worked for! It\u2019s a privilege to work with such a great team.","time":1516112718}],"scope":"GOOGLE","types":["point_of_interest","establishment"],"url":"https:\/\/maps.google.com\/?cid=8085058822928954129","user_ratings_total":21,"utc_offset":60,"vicinity":"54 Bute Street, Cardiff","website":"https:\/\/www.spindogs.co.uk\/"},"status":"OK"}');
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
     * Adds a contact point to the business
     * @param ContactPoint $contact_point
     */
    public function setContactPoint(ContactPoint $contact_point): void {
        $this->pl_contact_point = $contact_point;
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

        if (!empty($this->pl_contact_point)) {
        	$local_business->contactPoint($this->pl_contact_point);
		}

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