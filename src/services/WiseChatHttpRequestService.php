<?php

/**
 * Wise Chat HTTP request utilities.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatHttpRequestService {

	private $requestParams = array();

	/**
	 * Redirects to given URL (302 HTTP status code).
	 * @param string $url
	 */
	public function redirect($url) {
		wp_redirect($url, 302, 'Wise Chat Pro');
		exit;
	}

	/**
	 * Reloads the page (302 HTTP status code) without given parameters.
	 *
	 * @param array $excludeParameters
	 */
	public function reload($excludeParameters = array()) {
		wp_redirect($this->getCurrentURLWithoutParameters($excludeParameters), 302, 'Wise Chat Pro');
		exit;
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function getParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}

	/**
	 * @param string $name
	 * @param $value
	 */
	public function setRequestParam($name, $value) {
		$this->requestParams[$name] = $value;
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function getRequestParam($name, $default = null) {
		return array_key_exists($name, $this->requestParams) ? $this->requestParams[$name] : $default;
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
	 * Returns the full URL of the current HTTP request with additional parameter attached.
	 *
	 * @param string $paramName
	 * @param string $paramValue
	 * @param array $excludeParameters
	 * @return string
	 */
	public function getCurrentURLWithParameter($paramName, $paramValue, $excludeParameters = array()) {
		$url = count($excludeParameters) > 0 ? $this->getCurrentURLWithoutParameters($excludeParameters) : $this->getCurrentURL();
		$connector = strpos($url, '?') === false ? '?' : '&';

		return $url.$connector.$paramName.'='.urlencode($paramValue);
	}

	/**
	 * Returns full URL of the current HTTP request with additional parameters attached.
	 *
	 * @param array $parameters
	 * @return string
	 */
	public function getCurrentURLWithParameters($parameters) {
		$url = $this->getCurrentURLWithoutParameters(array_keys($parameters));
		$connector = strpos($url, '?') === false ? '?' : '&';

		return $url.$connector.http_build_query($parameters);
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

	/**
	 * Returns the remote address.
	 *
	 * @return string
	 */
	public function getRemoteAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}
}