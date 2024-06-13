<?php 

/**
 * Wise Chat admin emoticons settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatEmoticonsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'General Settings'),
			array(
				'emoticons_enabled', 'Emoticons Set', 'selectCallback', 'integer',
				'A collection of emoticons ready to insert into a message via Emoticon button (see Appearance settings).<br />
				<strong>Notice:</strong> This setting has no effect if Custom Emoticons option is enabled (see below).',
				self::getEmoticonSets()
			),
			array(
				'emoticons_size', 'Emoticon Size', 'selectCallback', 'integer', '', self::getEmoticonSize()
			),
			array('_section', 'Custom Emoticons', 'Compose and enable your own collection of emoticons.'),
			array('custom_emoticons_enabled', 'Enable Custom Emoticons', 'booleanFieldCallback', 'boolean', 'Enable custom set of emoticons. Below you can specify width of the emoticons layer and the list of emoticons.'),
			array('custom_emoticons_popup_width', 'Popup Width', 'stringFieldCallback', 'integer', 'Width of the emoticons popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_popup_height', 'Popup Height', 'stringFieldCallback', 'integer', 'Height of the emoticons popup (<strong>px</strong> unit). If the value is empty the height is set to contain all emoticons.'),
			array('custom_emoticons_emoticon_max_width_in_popup', 'Emoticon Width In Popup', 'stringFieldCallback', 'integer', 'Maximum width of a single emoticon in the popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_emoticon_width', 'Emoticon Width In Chat', 'selectCallback', 'string', 'Width of a single emoticon in the chat window.', WiseChatEmoticonsTab::getImageSizes()),
			array('custom_emoticon_add', 'New Emoticon', 'emoticonAddCallback', 'void'),
			array('custom_emoticons', 'Emoticons', 'emoticonsCallback', 'void'),
			array('_section', 'GIFs', 'Insert animated GIFs into a message. <br /><strong>Notice:</strong> This setting has no effect if emoticons are disabled (see options above). Additionally, you will need Tenor API key to enable GIFs support.'),
			array('gifs_enabled', 'Enable GIFs', 'booleanFieldCallback', 'boolean', 'Enable GIFs browser in the emoticons popup.'),
			array('gifs_api_key', 'Tenor GIFs API key', 'stringFieldCallback', 'string', 'Tenor API key <br /><a href="https://kainex.pl/projects/wp-plugins/wise-chat/documentation/features/gifs/" target="_blank">Read more</a>'),
			array('gifs_country', 'Country', 'selectCallback', 'string', 'Specify the country of origin for Tenor', self::getCountries()),
			array('gifs_language', 'Language', 'selectCallback', 'string', 'Specify the default language to interpret the search string by Tenor', self::getLanguages()),
			array('gifs_size', 'Max Width and Height', 'stringFieldCallback', 'integer', 'Maximal width and height of GIF images (<strong>px</strong> unit). If the value is empty a default width (140px) is used. Please note that the size of GIFs is limited by Tenor service'),
		);
	}

	public function getProFields() {
		return array(
			'gifs_enabled', 'gifs_api_key', 'gifs_country', 'gifs_language', 'gifs_size', 'custom_emoticons_enabled', 'custom_emoticons_popup_width',
			'custom_emoticons_popup_height', 'custom_emoticons_emoticon_max_width_in_popup', 'custom_emoticons_emoticon_width', 'emoticons_size'
		);
	}
	
	public function getDefaultValues() {
		return array(
			'emoticons_enabled' => 1,
			'emoticons_size' => 32,
			'custom_emoticons_enabled' => 0,
			'custom_emoticons_popup_width' => '',
			'custom_emoticons_popup_height' => '',
			'custom_emoticons_emoticon_max_width_in_popup' => '',
			'custom_emoticons_emoticon_width' => '',
			'gifs_enabled' => 0,
			'gifs_api_key' => '',
			'gifs_country' => 'US',
			'gifs_language' => 'en_US',
			'gifs_size' => '',

		);
	}

	public function getParentFields() {
		return array(
			'custom_emoticons_popup_width' => 'custom_emoticons_enabled',
			'custom_emoticons_popup_height' => 'custom_emoticons_enabled',
			'custom_emoticons_emoticon_max_width_in_popup' => 'custom_emoticons_enabled',
			'custom_emoticons_emoticon_width' => 'custom_emoticons_enabled',
			'gifs_api_key' => 'gifs_enabled',
			'gifs_country' => 'gifs_enabled',
			'gifs_language' => 'gifs_enabled',
			'gifs_size' => 'gifs_enabled'
		);
	}

	public static function getEmoticonSets() {
		return array(
			0 => '-- No emoticons --',
			1 => 'Set 1',
			'_DISABLED_pro_2' => 'Set 2 (available in Wise Chat Pro)',
			'_DISABLED_pro_3' => 'Set 3 (available in Wise Chat Pro)',
			'_DISABLED_pro_4' => 'Set 4 (available in Wise Chat Pro)',
		);
	}

	public static function getEmoticonSize() {
		return array(
			32 => '32',
			64 => '64',
			128 => '128',
		);
	}

	public static function getLanguages() {
		return array(
		    'af' => 'Afrikaans',
		    'sq' => 'Albanian - shqip',
		    'am' => 'Amharic - አማርኛ',
		    'ar' => 'Arabic - العربية',
		    'an' => 'Aragonese - aragonés',
		    'hy' => 'Armenian - հայերեն',
		    'ast' => 'Asturian - asturianu',
		    'az' => 'Azerbaijani - azərbaycan dili',
		    'eu' => 'Basque - euskara',
		    'be' => 'Belarusian - беларуская',
		    'bn' => 'Bengali - বাংলা',
		    'bs' => 'Bosnian - bosanski',
		    'br' => 'Breton - brezhoneg',
		    'bg' => 'Bulgarian - български',
		    'ca' => 'Catalan - català',
		    'ckb' => 'Central Kurdish - کوردی (دەستنوسی عەرەبی)',
		    'zh' => 'Chinese - 中文',
		    'zh_HK' => 'Chinese (Hong Kong) - 中文（香港）',
		    'zh_CN' => 'Chinese (Simplified) - 中文（简体）',
		    'zh_TW' => 'Chinese (Traditional) - 中文（繁體）',
		    'co' => 'Corsican',
		    'hr' => 'Croatian - hrvatski',
		    'cs' => 'Czech - čeština',
		    'da' => 'Danish - dansk',
		    'nl' => 'Dutch - Nederlands',
		    'en' => 'English',
		    'en_AU' => 'English (Australia)',
		    'en_CA' => 'English (Canada)',
		    'en_IN' => 'English (India)',
		    'en_NZ' => 'English (New Zealand)',
		    'en_ZA' => 'English (South Africa)',
		    'en_GB' => 'English (United Kingdom)',
		    'en_US' => 'English (United States)',
		    'eo' => 'Esperanto - esperanto',
		    'et' => 'Estonian - eesti',
		    'fo' => 'Faroese - føroyskt',
		    'fil' => 'Filipino',
		    'fi' => 'Finnish - suomi',
		    'fr' => 'French - français',
		    'fr_CA' => 'French (Canada) - français (Canada)',
		    'fr_FR' => 'French (France) - français (France)',
		    'fr_CH' => 'French (Switzerland) - français (Suisse)',
		    'gl' => 'Galician - galego',
		    'ka' => 'Georgian - ქართული',
		    'de' => 'German - Deutsch',
		    'de_AT' => 'German (Austria) - Deutsch (Österreich)',
		    'de_DE' => 'German (Germany) - Deutsch (Deutschland)',
		    'de_LI' => 'German (Liechtenstein) - Deutsch (Liechtenstein)',
		    'de_CH' => 'German (Switzerland) - Deutsch (Schweiz)',
		    'el' => 'Greek - Ελληνικά',
		    'gn' => 'Guarani',
		    'gu' => 'Gujarati - ગુજરાતી',
		    'ha' => 'Hausa',
		    'haw' => 'Hawaiian - ʻŌlelo Hawaiʻi',
		    'he' => 'Hebrew - עברית',
		    'hi' => 'Hindi - हिन्दी',
		    'hu' => 'Hungarian - magyar',
		    'is' => 'Icelandic - íslenska',
		    'id' => 'Indonesian - Indonesia',
		    'ia' => 'Interlingua',
		    'ga' => 'Irish - Gaeilge',
		    'it' => 'Italian - italiano',
		    'it_IT' => 'Italian (Italy) - italiano (Italia)',
		    'it_CH' => 'Italian (Switzerland) - italiano (Svizzera)',
		    'ja' => 'Japanese - 日本語',
		    'kn' => 'Kannada - ಕನ್ನಡ',
		    'kk' => 'Kazakh - қазақ тілі',
		    'km' => 'Khmer - ខ្មែរ',
		    'ko' => 'Korean - 한국어',
		    'ku' => 'Kurdish - Kurdî',
		    'ky' => 'Kyrgyz - кыргызча',
		    'lo' => 'Lao - ລາວ',
		    'la' => 'Latin',
		    'lv' => 'Latvian - latviešu',
		    'ln' => 'Lingala - lingála',
		    'lt' => 'Lithuanian - lietuvių',
		    'mk' => 'Macedonian - македонски',
		    'ms' => 'Malay - Bahasa Melayu',
		    'ml' => 'Malayalam - മലയാളം',
		    'mt' => 'Maltese - Malti',
		    'mr' => 'Marathi - मराठी',
		    'mn' => 'Mongolian - монгол',
		    'ne' => 'Nepali - नेपाली',
		    'no' => 'Norwegian - norsk',
		    'nb' => 'Norwegian Bokmål - norsk bokmål',
		    'nn' => 'Norwegian Nynorsk - nynorsk',
		    'oc' => 'Occitan',
		    'or' => 'Oriya - ଓଡ଼ିଆ',
		    'om' => 'Oromo - Oromoo',
		    'ps' => 'Pashto - پښتو',
		    'fa' => 'Persian - فارسی',
		    'pl' => 'Polish - polski',
		    'pt' => 'Portuguese - português',
		    'pt_BR' => 'Portuguese (Brazil) - português (Brasil)',
		    'pt_PT' => 'Portuguese (Portugal) - português (Portugal)',
		    'pa' => 'Punjabi - ਪੰਜਾਬੀ',
		    'qu' => 'Quechua',
		    'ro' => 'Romanian - română',
		    'mo' => 'Romanian (Moldova) - română (Moldova)',
		    'rm' => 'Romansh - rumantsch',
		    'ru' => 'Russian - русский',
		    'gd' => 'Scottish Gaelic',
		    'sr' => 'Serbian - српски',
		    'sh' => 'Serbo-Croatian - Srpskohrvatski',
		    'sn' => 'Shona - chiShona',
		    'sd' => 'Sindhi',
		    'si' => 'Sinhala - සිංහල',
		    'sk' => 'Slovak - slovenčina',
		    'sl' => 'Slovenian - slovenščina',
		    'so' => 'Somali - Soomaali',
		    'st' => 'Southern Sotho',
		    'es' => 'Spanish - español',
		    'es_AR' => 'Spanish (Argentina) - español (Argentina)',
		    'es_419' => 'Spanish (Latin America) - español (Latinoamérica)',
		    'es_MX' => 'Spanish (Mexico) - español (México)',
		    'es_ES' => 'Spanish (Spain) - español (España)',
		    'es_US' => 'Spanish (United States) - español (Estados Unidos)',
		    'su' => 'Sundanese',
		    'sw' => 'Swahili - Kiswahili',
		    'sv' => 'Swedish - svenska',
		    'tg' => 'Tajik - тоҷикӣ',
		    'ta' => 'Tamil - தமிழ்',
		    'tt' => 'Tatar',
		    'te' => 'Telugu - తెలుగు',
		    'th' => 'Thai - ไทย',
		    'ti' => 'Tigrinya - ትግርኛ',
		    'to' => 'Tongan - lea fakatonga',
		    'tr' => 'Turkish - Türkçe',
		    'tk' => 'Turkmen',
		    'tw' => 'Twi',
		    'uk' => 'Ukrainian - українська',
		    'ur' => 'Urdu - اردو',
		    'ug' => 'Uyghur',
		    'uz' => 'Uzbek - o‘zbek',
		    'vi' => 'Vietnamese - Tiếng Việt',
		    'wa' => 'Walloon - wa',
		    'cy' => 'Welsh - Cymraeg',
		    'fy' => 'Western Frisian',
		    'xh' => 'Xhosa',
		    'yi' => 'Yiddish',
		    'yo' => 'Yoruba - Èdè Yorùbá',
		    'zu' => 'Zulu - isiZulu'
		);
	}

	public static function getCountries() {
		return array(
			'AF' => 'Afghanistan',
			'AX' => 'Aland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua And Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia And Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, Democratic Republic',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Cote D\'Ivoire',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Malvinas)',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island & Mcdonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic Of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle Of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KR' => 'Korea',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia, Federated States Of',
			'MD' => 'Moldova',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territory, Occupied',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthelemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts And Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin',
			'PM' => 'Saint Pierre And Miquelon',
			'VC' => 'Saint Vincent And Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome And Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia And Sandwich Isl.',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard And Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad And Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks And Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.S.',
			'WF' => 'Wallis And Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);
	}

	public static function getImageSizes() {
		$defaultNames = array(
			'thumbnail' => __('Thumbnail'),
			'medium' => __('Medium'),
			'medium_large' => __('Medium Large'),
			'large' => __('Large'),
			'full' => __('Full Size')
		);
		$sizes = get_intermediate_image_sizes();

		$sizesOut = array(
			'' => ''
		);
		foreach ($sizes as $size) {
			if (array_key_exists($size, $defaultNames)) {
				$sizesOut[$size] = $defaultNames[$size];
			} else {
				$sizesOut[$size] = $size;
			}
		}

		return $sizesOut;
	}

	public function addEmoticonAction() {

	}

	public function deleteCustomEmoticonAction() {

	}

	public function moveUpCustomEmoticonAction() {

	}

	public function moveDownCustomEmoticonAction() {

	}

	public function emoticonAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addEmoticon");

		printf(
			'<input type="hidden" value="" id="newEmoticonId" name="newEmoticonId" />'.
			'<div id="newEmoticonImageContainerId"></div>'.
			'<button class="wc-image-picker button-secondary" data-parent-field="custom_emoticons_enabled" data-target-id="newEmoticonId" data-image-container-id="newEmoticonImageContainerId">Select Image</button>'.
			' | '.
			'<a class="button-primary new-emoticon-submit" href="%s" data-parent-field="custom_emoticons_enabled">Add Emoticon</a>'.
			'<p class="description">Select the image and click Add Emoticon button. Optionally you can choose a shortcut for the emoticon. For example - for smiley you might want to put the shortcut: <strong>:)</strong></p>',
			wp_nonce_url($url)
		);

		$this->printProFeatureNotice();
	}

	public function emoticonsCallback() {
		$emoticons = [];

		$html = "<table class='wp-list-table widefat emotstable'>";
		if (count($emoticons) == 0) {
			$html .= '<tr><td>No custom emoticons added yet. Use the form above in order to add you own emoticons.</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Image</th><th>Actions</th></tr></thead>';
		}

		$total = count($emoticons);
		foreach ($emoticons as $key => $emoticon) {
			$classes = $key % 2 == 0 ? 'alternate' : '';
			$imageUrl = wp_get_attachment_url($emoticon->getAttachmentId());

			$actions = array();
			$deleteURL = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=deleteCustomEmoticon&id=".urlencode($emoticon->getId())).'&tab=emoticons';
			$moveUpURL = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=moveUpCustomEmoticon&id=".urlencode($emoticon->getId())).'&tab=emoticons';
			$moveDownURL = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=moveDownCustomEmoticon&id=".urlencode($emoticon->getId())).'&tab=emoticons';
			if ($key > 0) {
				$actions[] = sprintf(
					'<a class="button-secondary" href="%s" title="Move up" data-parent-field="custom_emoticons_enabled">Up</a>',
					$moveUpURL
				);
			}
			if ($key < ($total - 1)) {
				$actions[] = sprintf(
					'<a class="button-secondary" href="%s" title="Move down" data-parent-field="custom_emoticons_enabled">Down</a>',
					$moveDownURL
				);
			}

			$actions[] = sprintf(
				'<a class="button-primary" href="%s" title="Delete the emoticon" onclick="return confirm(\'Are you sure?\')" data-parent-field="custom_emoticons_enabled">Delete</a>',
				$deleteURL
			);

			$imageTag = '[Image deleted]';
			if ($imageUrl !== false) {
				$imageTag = sprintf(
					'<a href="%s" target="_blank" title="Open in new window" style="outline: none;"><img src="%s" style="max-width: 100px;" alt="Emoticon Image" /></a>',
					$imageUrl, $imageUrl
				);
			}

			$html .= sprintf(
				'<tr class="%s">
					<td>%s</td><td>%s</td>
				</tr>',
				$classes, $imageTag, implode('&nbsp;|&nbsp;', $actions)
			);
		}
		$html .= "</table>";

		print($html);

		$this->printProFeatureNotice();
	}
}