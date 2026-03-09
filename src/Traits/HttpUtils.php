<?php

namespace Kainex\WiseChat\Traits;

use Exception;

/**
 * @author Kainex <contact@kainex.pl>
 */
trait HttpUtils {

	protected function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? stripslashes_deep($_POST[$name]) : $default;
	}

	protected function getGetParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? stripslashes_deep($_GET[$name]) : $default;
	}

	protected function getParam($name, $default = null) {
		$getParam = $this->getGetParam($name);
		if ($getParam === null) {
			return $this->getPostParam($name, $default);
		}

		return $getParam;
	}

	/**
	 * @param array $params
	 * @throws Exception
	 */
	protected function checkPostParams($params) {
		foreach ($params as $param) {
			if ($this->getPostParam($param) === null) {
				throw new Exception('Required parameters are missing');
			}
		}
	}

	/**
	 * @param array $params
	 * @throws Exception
	 */
	protected function checkGetParams($params) {
		foreach ($params as $param) {
			if ($this->getGetParam($param) === null) {
				throw new Exception('Required parameters are missing');
			}
		}
	}

	protected function sendBadRequestStatus() {
		header('HTTP/1.0 400 Bad Request', true, 400);
	}

	protected function sendUnauthorizedStatus() {
		header('HTTP/1.0 401 Unauthorized', true, 401);
	}

	protected function sendNotFoundStatus() {
		header('HTTP/1.0 404 Not Found', true, 404);
	}

	protected function jsonContentType() {
		header('Content-Type: application/json; charset='.get_option('blog_charset'));
	}

	protected function verifyXhrRequest() {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			return;
		} else {
			$this->sendNotFoundStatus();
			$this->endRequest('XMLHttpRequest check failed');
		}
	}

	protected function endRequest($error = 'Request terminated') {
		header("X-".str_replace(' ', '-', WISE_CHAT_NAME).": $error");
		die();
	}

}