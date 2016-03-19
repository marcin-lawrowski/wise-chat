<?php

/**
 * Wise Chat geo-targeting service class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatGeoService {

	/**
	 * Returns geo details about the IP address.
	 *
	 * @param string $ipAddress
     *
	 * @return WiseChatGeoDetails
	 */
	public function getGeoDetails($ipAddress) {
        if (!function_exists('curl_init') || strlen($ipAddress) == 0 || $ipAddress == '127.0.0.1' || $ipAddress == '::1') {
            return null;
        }
        WiseChatContainer::load('model/WiseChatGeoDetails');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'http://www.geoplugin.net/json.gp?ip='.urlencode($ipAddress));
        $data = curl_exec($curl);
        curl_close($curl);

        $rawData = json_decode($data);

        if ($rawData !== null && property_exists($rawData, 'geoplugin_status') && $rawData->geoplugin_status > 0) {
            $details = new WiseChatGeoDetails();

            if (property_exists($rawData, 'geoplugin_city')) {
                $details->setCity($rawData->geoplugin_city);
            }
            if (property_exists($rawData, 'geoplugin_regionCode')) {
                $details->setRegionCode($rawData->geoplugin_regionCode);
            }
            if (property_exists($rawData, 'geoplugin_countryCode')) {
                $details->setCountryCode($rawData->geoplugin_countryCode);
            }
            if (property_exists($rawData, 'geoplugin_countryName')) {
                $details->setCountry($rawData->geoplugin_countryName);
            }
            if (property_exists($rawData, 'geoplugin_continentCode')) {
                $details->setContinentCode($rawData->geoplugin_continentCode);
            }
            if (property_exists($rawData, 'geoplugin_latitude')) {
                $details->setLatitude($rawData->geoplugin_latitude);
            }
            if (property_exists($rawData, 'geoplugin_longitude')) {
                $details->setLongitude($rawData->geoplugin_longitude);
            }
            if (property_exists($rawData, 'geoplugin_regionName')) {
                $details->setRegion($rawData->geoplugin_regionName);
            }
            if (property_exists($rawData, 'geoplugin_currencyCode')) {
                $details->setCurrencyCode($rawData->geoplugin_currencyCode);
            }

            return $details;
        }

        return null;
    }
}