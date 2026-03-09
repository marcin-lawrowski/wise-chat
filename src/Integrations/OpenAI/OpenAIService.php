<?php

namespace Kainex\WiseChat\Integrations\OpenAI;

use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Options;

class OpenAIService {

	const BASE_URL = 'https://api.openai.com';
	const ALLOWED_IMAGES = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

	private UserService $userService;
	private MessagesService $messagesService;
	private Options $options;
	private AuthenticationService $authentication;
	private ChannelsService $channelsService;

	/**
	 * @param UserService $userService
	 * @param Options $options
	 * @param MessagesService $messagesService
	 * @param AuthenticationService $authentication
	 * @param ChannelsService $channelsService
	 */
	public function __construct(UserService $userService, Options $options, MessagesService $messagesService, \Kainex\WiseChat\Services\User\AuthenticationService $authentication, \Kainex\WiseChat\Services\ChannelsService $channelsService) {
		$this->userService = $userService;
		$this->options = $options;
		$this->messagesService = $messagesService;
		$this->authentication = $authentication;
		$this->channelsService = $channelsService;
	}

	public function onMessageAdded(Message $message, array $attachmentIds, Channel $channel, User $user) {
		if ($channel->getType() !== Channel::TYPE_DIRECT) {
			return;
		}
		$members = $this->channelsService->getChannelMembers($channel, true);
		$recipient = null;
		foreach ($members as $member) {
			if ($member->getUserId() !== $user->getId()) {
				$recipient = $member->getUser();
				break;
			}
		}
		if (!$recipient) {
			return;
		}

		if (get_user_meta($recipient->getWordPressId(), 'wc_ai_bot', true) !== '1') {
			return;
		}

		$type = get_user_meta($recipient->getWordPressId(), 'wc_ai_type', true);
		if ($type === 'completion') {
			$this->requestMessageCompletion($message, $attachmentIds, $channel, $user, $recipient);
		}
	}

	private function requestMessageCompletion(Message $message, array $attachmentIds, Channel $channel, User $user, User $recipient) {
		$role = get_user_meta($recipient->getWordPressId(), 'wc_ai_role_description', true);
		$imagesEnabled = get_user_meta($recipient->getWordPressId(), 'wc_ai_images', true) === '1';

		$requestData = [
			"model" => get_user_meta($recipient->getWordPressId(), 'wc_ai_model', true),
	        "messages" => [
	            [
	                "role" => "system",
	                "content" => $role ?: "You are a helpful assistant."
	            ],
	            [
	                "role" => "user",
	                "content" => [[
						'type' => 'text',
						"text" => $this->removeShortcodes($message->getText())
	                ]]
	            ]
	        ]
		];

		if ($imagesEnabled) {
			$imagesRequest = $this->attachmentsToRequest($attachmentIds);
			$requestData['messages'][1]['content'] = array_merge($requestData['messages'][1]['content'], $imagesRequest);
		}

		$result = $this->apiCall('/v1/chat/completions', $requestData);

		if (isset($result['choices'])) {
			foreach ($result['choices'] as $choice) {
				if ($choice['message']['role'] === 'assistant') {
					$this->messagesService->addMessage($recipient, $channel, $choice['message']['content'], []);
					return;
				}
			}
			throw new \Exception('Open AI error: no answer from the assistant');
		} else {
			throw new \Exception('Open AI error: no choices found');
		}
	}

	private function attachmentsToRequest(array $attachmentIds): array {
		$result = [];

		foreach ($attachmentIds as $attachmentId) {
			$url = wp_get_attachment_url($attachmentId);
			if ($url === false) {
				continue;
			}
			$extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
			if (!in_array($extension, self::ALLOWED_IMAGES)) {
				continue;
			}

			$result[] = [
				"type" => "image_url",
				"image_url" => [
					"url" => $url
				]
			];
		}

		return $result;
	}

	private function apiCall(string $url, array $content, array $additionalHeaders = []): array {
		$ch = curl_init();

		try {
			$headers = array_merge([
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->options->getOption('ai_openai_apikey')
			], $additionalHeaders);

			curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			$output = curl_exec($ch);
			$responseCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
			$parsed = json_decode($output, true);
			if ($responseCode !== 200) {
				if (is_array($parsed)) {
					throw new \Exception($parsed['error']['message']);
				} else {
					throw new \Exception('Open AI API error: '.$responseCode.', '.$output);
				}
			} else if (!is_array($parsed)) {
				throw new \Exception('Open AI API empty response error');
			}

			return $parsed;
		} finally {
			curl_close($ch);
		}
	}

	private function apiGetCall(string $url, array $queryParams = [], array $additionalHeaders = []): array {
		$ch = curl_init();

		try {
			$headers = array_merge([
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->options->getOption('ai_openai_apikey')
			], $additionalHeaders);

			curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $url .(!empty($queryParams) ? '?' : '').http_build_query($queryParams));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			$output = curl_exec($ch);
			$responseCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
			$parsed = json_decode($output, true);
			if ($responseCode !== 200) {
				if (is_array($parsed)) {
					throw new \Exception($parsed['error']['message']);
				} else {
					throw new \Exception('Open AI API error: '.$responseCode);
				}
			} else if (!is_array($parsed)) {
				throw new \Exception('Open AI API empty response error');
			}

			return $parsed;
		} finally {
			curl_close($ch);
		}
	}

	private function removeShortcodes(string $message): string {
		return preg_replace('/\[([a-z-]+?)(\s[^]]*?)?]/', ' ', $message);
	}

}