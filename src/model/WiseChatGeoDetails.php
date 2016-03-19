<?php

/**
 * WiseChat geo details model.
 */
class WiseChatGeoDetails {

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $regionCode;

    /**
     * @var string
     */
    private $latitude;

    /**
     * @var string
     */
    private $longitude;

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $continentCode;

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getContinentCode()
    {
        return $this->continentCode;
    }

    /**
     * @param string $continentCode
     */
    public function setContinentCode($continentCode)
    {
        $this->continentCode = $continentCode;
    }

    /**
     * @return string
     */
    public function getRegionCode()
    {
        return $this->regionCode;
    }

    /**
     * @param string $regionCode
     */
    public function setRegionCode($regionCode)
    {
        $this->regionCode = $regionCode;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    public function toArray() {
        return array(
            'country' => $this->country,
            'countryCode' => $this->countryCode,
            'region' => $this->region,
            'regionCode' => $this->regionCode,
            'city' => $this->city,
            'continentCode' => $this->continentCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'currencyCode' => $this->currencyCode
        );
    }

}