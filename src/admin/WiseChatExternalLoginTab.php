<?php

/**
 * Wise Chat admin external login settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatExternalLoginTab extends WiseChatAbstractTab {

    public function getFields() {
        return array(
            array('_section', 'Anonymous Login'),
            array('anonymous_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Shows a button for anonymous login.'),

            array('_section', 'Facebook Login', 'In order to setup Facebook authentication you need to register an Application with Facebook. Then you will be able to get your Application ID and Secret. More details <a href="https://developers.facebook.com/docs/apps/register">here</a>.'),
            array('facebook_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables login through Facebook API. Every user that enters the chat must pass authorization through Facebook.'),
            array('facebook_login_app_id', 'Application ID', 'stringFieldCallback', 'string', 'Required application ID.'),
            array('facebook_login_app_secret', 'Application Secret', 'stringFieldCallback', 'string', 'Required application secret key'),

            array('_section', 'Twitter Login', 'In order to setup Twitter authentication you need to register an Application with Twitter. Then you will be able to get your Consumer Key (API Key) and Consumer Secret (API Secret). More details <a href="https://dev.twitter.com/">here</a>.'),
            array('twitter_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables login through Twitter API. Every user that enters the chat must pass authorization through Twitter.'),
            array('twitter_login_api_key', 'API Key', 'stringFieldCallback', 'string', 'Required API Key.'),
            array('twitter_login_api_secret', 'API Secret', 'stringFieldCallback', 'string', 'Required API Secret'),

            array('_section', 'Google Login', 'In order to setup Google authentication you need to create new project with Google. Then you will be able to get your Client ID and Client Secret. More details <a href="https://developers.google.com">here</a>.'),
            array('google_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables login through Google API. Every user that enters the chat must pass authorization through Google.'),
            array('google_login_client_id', 'Client ID', 'stringFieldCallback', 'string', 'Required Client ID.'),
            array('google_login_client_secret', 'Client Secret', 'stringFieldCallback', 'string', 'Required Client Secret'),
        );
    }

    public function getProFields() {
        return array(
            'anonymous_login_enabled', 'facebook_login_enabled', 'facebook_login_app_id', 'facebook_login_app_secret', 'twitter_login_enabled',
            'twitter_login_api_key', 'twitter_login_api_secret', 'google_login_enabled', 'google_login_client_id', 'google_login_client_secret'
        );
    }

    public function getDefaultValues() {
        return array(
            'anonymous_login_enabled' => 0,

            'facebook_login_enabled' => 0,
            'facebook_login_app_id' => '',
            'facebook_login_app_secret' => '',

            'twitter_login_enabled' => 0,
            'twitter_login_api_key' => '',
            'twitter_login_api_secret' => '',

            'google_login_enabled' => 0,
            'google_login_client_id' => '',
            'google_login_client_secret' => '',
        );
    }

    public function getParentFields() {
        return array(
            'facebook_login_app_id' => 'facebook_login_enabled',
            'facebook_login_app_secret' => 'facebook_login_enabled',

            'twitter_login_api_key' => 'twitter_login_enabled',
            'twitter_login_api_secret' => 'twitter_login_enabled',

            'google_login_client_id' => 'google_login_enabled',
            'google_login_client_secret' => 'google_login_enabled',
        );
    }
}