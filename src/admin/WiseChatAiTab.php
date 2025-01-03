<?php

/**
 * Wise Chat admin modes settings.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatAiTab extends WiseChatAbstractTab {

	const MODELS = [
		'gpt-4o' => 'gpt-4o',
		'gpt-4o-mini' => 'gpt-4o-mini',
		'gpt-3.5-turbo' => 'gpt-3.5-turbo'
	];

	public function getFields() {
		return array(
			array('_section', 'Open AI Integration', 'Create AI chatbots connected to ChatGPT. Your chat users can send direct messages to chatbots. There are two types of bots available: <strong>completion</strong> and <strong>assistant</strong>. 
			Completion bots can generate text from a prompt. Each request is independent and ChatGPT does not take into account previous answers. Read more about <a href="https://platform.openai.com/docs/guides/text-generation" target="_blank">chat completions</a>.
			The 2nd bot type, assistant, is a regular <strong>AI assistant</strong> that helps your chat users on a desired subject, e.g. online shop assistant, support assistant, etc. <a href="https://platform.openai.com/docs/assistants/overview" target="_blank">More about assistants</a>'
            ),
            array('ai_openai_apikey', 'Open AI API key', 'stringFieldCallback', 'string', 'Create OpenAI account, add some money to your credit balance and generate API key <a href="https://platform.openai.com/api-keys">here</a>. ChatGPT will not work with empty credit balance.'),
			array('ai_bots_create', 'Create AI Chatbot', 'botsCreateCallback', 'void'),
			array('ai_bots', 'AI Chatbots', 'botsCallback', 'void'),
		);
	}

    public function getProAiFields() {
		return array('ai_openai_apikey', 'ai_bots_create', 'ai_bots');
	}

	public function botsCreateCallback() {
		print '<button type="button" class="button-secondary wc-add-bot-button" title="Move up">Add AI Chatbot</button>';
		$this->botAddEditForm();
		$this->printProAiFeatureNotice();
	}

    public function botsCallback() {
        $this->listBots();
	}

	private function botAddEditForm($bot = null) {
        $id = $bot ? $bot->ID : 'new';
        $className = 'wc-bot-form-'.$id;
		$name = $bot ? htmlentities($bot->display_name) : '';
		$roleDescription = $bot ? htmlentities(get_user_meta($bot->ID, 'wc_ai_role_description', true)) : 'You are a helpful assistant.';
		$email = $bot ? htmlentities($bot->user_email) : '';
		$type = $bot ? htmlentities(get_user_meta($bot->ID, 'wc_ai_type', true)) : 'completion';
		$model = $bot ? htmlentities(get_user_meta($bot->ID, 'wc_ai_model', true)) : 'gpt-4o-mini';
		?>
            <div class="wc-bot-form <?php echo $className; ?>" data-id="<?php echo $id; ?>" style="display: none">
                <table class="wc-bot-form-table wp-list-table widefat">
                    <tr>
                        <td class="th-full">Name:</td>
                        <td><input type="text" name="wc-bot-name-<?php echo $id; ?>" value="<?php echo $name; ?>" style="width: 100%;" /></td>
                    </tr>
                    <tr>
                        <td class="th-full">E-mail:</td>
                        <td>
                            <input type="email" <?php echo $id !== 'new' ? 'disabled' : ''; ?> name="wc-bot-email-<?php echo $id; ?>" value="<?php echo $email; ?>" style="width: 100%;" />
                            <small>Chatbot is actually a WordPress account. E-mails must be unique. </small>
                        </td>
                    </tr>
                    <tr>
                        <td class="th-full">Type:</td>
                        <td>
                            <input type="radio" <?php echo $id !== 'new' ? 'disabled' : ''; ?> id="wc-bot-type-completion-<?php echo $id; ?>" name="wc-bot-type-<?php echo $id; ?>" <?php echo $type === 'completion' ? 'checked' : ''; ?> value="completion">
                            <label for="wc-bot-type-completion-<?php echo $id; ?>">Completion</label><br>
                            <input type="radio" <?php echo $id !== 'new' ? 'disabled' : ''; ?> id="wc-bot-type-assistant-<?php echo $id; ?>" name="wc-bot-type-<?php echo $id; ?>" <?php echo $type === 'assistant' ? 'checked' : ''; ?> value="assistant">
                            <label for="wc-bot-type-assistant-<?php echo $id; ?>">Assistant</label>
                        </td>
                    </tr>
                     <tr>
                        <td class="th-full">Model:</td>
                        <td>
                            <select name="wc-bot-model-<?php echo $id; ?>">
                                <?php foreach (self::MODELS as $modelID => $label) { ?>
                                    <option <?php echo $model === $modelID ? 'selected' : ''; ?> value="<?php echo $modelID; ?>"><?php echo $label; ?></option>
                                <?php } ?>
                            </select>
                            <br />
                            <small>Read more about <a href="https://platform.openai.com/docs/models" target="_blank">models</a> and <a href="https://openai.com/api/pricing/" target="_blank">pricing</a></small>
                        </td>
                    </tr>
                    <tr>
                        <td class="th-full">Role Description:</td>
                        <td>
                            <textarea maxlength="100000" name="wc-bot-role-description-<?php echo $id; ?>" style="width: 100%; height: 140px;"><?php echo $roleDescription; ?></textarea>
                            <small>Describe the role of the bot. e.g. You are a helpful assistant, You are a poet, You are an online shop assistant, etc.</small>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="wc-bot-nonce-<?php echo $id; ?>" value="<?php echo wp_create_nonce($className); ?>" />
                <button type="button" class="button-primary wc-bot-save-button" disabled>Save</button>
                <button type="button" class="button-secondary wc-bot-cancel-button">Cancel</button>
            </div>
		<?php
	}

	public function getDefaultValues() {
		return array(
            'ai_openai_apikey' => ''
		);
	}

	private function listBots() {
        $bots = get_users([ 'meta_key' => 'wc_ai_bot', 'meta_value' => '1' ]);
?>
        <table class='wp-list-table widefat wc-ai-bots-table'>
            <thead><tr><th>User name</th><th>Type</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($bots)) { ?>
                    <tr>
                        <td>No bots added yet.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($bots as $bot) { ?>
                    <?php
                        $type = get_user_meta($bot->ID, 'wc_ai_type', true);
                        $assistantId = get_user_meta($bot->ID, 'wc_ai_assistant_id', true);
                    ?>
                    <tr>
                        <td><?php echo $bot->display_name; ?></td>
                        <td><?php echo ucfirst(get_user_meta($bot->ID, 'wc_ai_type', true)); ?></td>
                        <td>
                            <button type="button" class="button-secondary wc-bot-edit-button" data-id="<?php echo $bot->ID; ?>">Edit</button>
                            <button type="button" class="button-secondary wc-bot-delete-button" data-id="<?php echo $bot->ID; ?>" data-nonce="<?php echo wp_create_nonce('wc-ai-bot-delete-'.$bot->ID); ?>">Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <?php echo $type === 'assistant' && !$assistantId ? 'Error: no associated Open AI assistant found' : '' ?>
                            <?php $this->botAddEditForm($bot); ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php $this->printProAiFeatureNotice(); ?>
<?php
	}

}