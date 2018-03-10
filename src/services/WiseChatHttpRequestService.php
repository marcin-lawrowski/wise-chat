<?php

/**
 * Wise Chat HTTP request utilities.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatHttpRequestService {

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function getParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}

	/**
	 * Returns full URL of the current HTTP request.
	 *
	 * @return string
	 */
	public function getCurrentURL() {
		return (isset($_SERVER['HTTPS']) ? "https" : "http") . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}

	/**
	 * Returns full URL of the current HTTP request with additional parameter attached.
	 *
	 * @param string $paramName
	 * @param string $paramValue
	 * @return string
	 */
	public function getCurrentURLWithParameter($paramName, $paramValue) {
		$url = $this->getCurrentURL();
		$connector = strpos($url, '?') === false ? '?' : '&';

		return $url.$connector.$paramName.'='.urlencode($paramValue);
	}

	/**
	 * Prepares current URL without given GET parameters.
	 *
	 * @param array $parametersToExclude Excluded GET parameters.
	 * @return string
	 */
	public function getCurrentURLWithoutParameters($parametersToExclude) {
		$resultUrl = $url = $this->getCurrentURL();
		$split = preg_split('/\?/', $url);
		if (count($split) > 1) {
			$resultUrl = $split[0];
			$passedParams = array();
			foreach ($_GET as $key => $value) {
				if (!in_array($key, $parametersToExclude)) {
					$passedParams[$key] = $value;
				}
			}

			$passedParamsQuery = http_build_query($passedParams);
			if (strlen($passedParamsQuery) > 0) {
				$resultUrl .= '?'.$passedParamsQuery;
			}
		}

		return $resultUrl;
	}
}