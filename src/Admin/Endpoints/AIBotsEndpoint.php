<?php

namespace Kainex\WiseChat\Admin\Endpoints;

use Kainex\WiseChat\Integrations\OpenAI\OpenAIService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Settings;
use WP_Error;

class AIBotsEndpoint {

	private UserService $userService;
	private OpenAIService $openAIService;

	/**
	 * @param UserService $userService
	 * @param OpenAIService $openAIService
	 */
	public function __construct(UserService $userService, OpenAIService $openAIService) {
		$this->userService = $userService;
		$this->openAIService = $openAIService;
	}

	public function createBot() {
		try {
			$bots = get_users([ 'meta_key' => 'wc_ai_bot', 'meta_value' => '1' ]);
			if (count($bots) > 0) {
				throw new \Exception('In Wise Chat free you may only create one chat bot. Please check our Wise Chat Pro with AI plugin.');
			}

			if (!current_user_can(Settings::CAPABILITY)) {
				throw new \Exception('Access denied');
			}
			if (!wp_verify_nonce($_POST["nonce"], 'wc-bot-form-new')) {
				throw new \Exception("Invalid nonce");
			}

			$name = sanitize_text_field($_POST["name"]);
			$email = sanitize_email($_POST["email"]);
			$type = sanitize_text_field($_POST["type"]);
			$images = sanitize_text_field($_POST["images"]);
			$model = sanitize_text_field($_POST["model"]);
			$roleDescription = sanitize_text_field($_POST["roleDescription"]);

			if (!$name) {
				throw new \Exception("Name is required");
			}
			if (!$email) {
				throw new \Exception("Email is required");
			}
			if (!$roleDescription) {
				throw new \Exception("Role description is required");
			}

			if (!in_array($type, ["completion"])) {
				throw new \Exception("Invalid type");
			}

			if (!in_array($model, ["gpt-4o", "gpt-4o-mini", 'gpt-3.5-turbo'])) {
				throw new \Exception("Invalid model");
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \Exception("Invalid e-mail");
			}

			$user_id = username_exists($name);
			if (!$user_id && !email_exists($email)) {
				$random_password = wp_generate_password();
				$user_id = wp_create_user($name, $random_password, $email);
				if ($user_id instanceof WP_Error) {
					throw new \Exception("Error creating user: ".$user_id->get_error_message());
				}
				add_user_meta($user_id, 'wc_ai_bot', '1');
				add_user_meta($user_id, 'wc_ai_type', $type);
				add_user_meta($user_id, 'wc_ai_model', $model);
				add_user_meta($user_id, 'wc_ai_images', $images === 'true' ? '1' : '0');
				add_user_meta($user_id, 'wc_ai_role_description', $roleDescription);

				// create Wise Chat user:
				$this->userService->createOrGetBasedOnWordPressUserId($user_id);
			} else {
				throw new \Exception("User already exists");
			}

			set_transient('wise_chat_pro_wp_users_cache_reset', 'yes', 1000);
			set_transient("wc_admin_settings_message", 'AI chatbot has been created', 10);
			die(json_encode(array('status' => 'OK')));
		} catch (\Exception $exception) {
			die(json_encode(array('error' => $exception->getMessage())));
		}
	}

	public function deleteBot() {
		try {
			if (!current_user_can(Settings::CAPABILITY)) {
				throw new \Exception('Access denied');
			}

			$id = (int) sanitize_text_field($_POST["id"]);
			if (!wp_verify_nonce($_POST["nonce"], 'wc-ai-bot-delete-'.$id)) {
				throw new \Exception("Invalid nonce");
			}

			if (get_user_by('ID', $id) === false) {
				throw new \Exception("User not found");
			}

			if (!wp_delete_user($id)) {
				throw new \Exception("Could not delete user");
			}

			set_transient('wise_chat_pro_wp_users_cache_reset', 'yes', 1000);
			set_transient("wc_admin_settings_message", 'AI chatbot has been deleted', 10);
			die(json_encode(array('status' => 'OK')));
		} catch (\Exception $exception) {
			die(json_encode(array('error' => $exception->getMessage())));
		}
	}

	public function saveBot() {
		try {
			if (!current_user_can(Settings::CAPABILITY)) {
				throw new \Exception('Access denied');
			}
			$id = intval($_POST["id"]);
			if (!wp_verify_nonce($_POST["nonce"], 'wc-bot-form-'.$id)) {
				throw new \Exception("Invalid nonce");
			}

			$name = sanitize_text_field($_POST["name"]);
			$model = sanitize_text_field($_POST["model"]);
			$roleDescription = sanitize_text_field($_POST["roleDescription"]);
			$images = sanitize_text_field($_POST["images"]);

			if (!$name) {
				throw new \Exception("Name is required");
			}
			if (!$model) {
				throw new \Exception("Model is required");
			}
			if (!$roleDescription) {
				throw new \Exception("Role description is required");
			}

			$user = get_user_by('ID', $id);
			if ($user === false) {
				throw new \Exception("User not found");
			}

			wp_update_user([
				'ID' => $id,
				'display_name' => $name
			]);
			update_user_meta($id, 'wc_ai_role_description', $roleDescription);
			update_user_meta($id, 'wc_ai_model', $model);
			update_user_meta($id, 'wc_ai_images', $images === 'true' ? '1' : '0');

			$type = get_user_meta($id, 'wc_ai_type', true);

			set_transient('wise_chat_pro_wp_users_cache_reset', 'yes', 1000);
			set_transient("wc_admin_settings_message", 'AI chatbot has been updated', 10);
			die(json_encode(array('status' => 'OK')));
		} catch (\Exception $exception) {
			die(json_encode(array('error' => $exception->getMessage())));
		}
	}

}