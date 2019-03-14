<link rel='stylesheet' id='wise_chat_theme_{{ chatId }}-css' href='{{ themeStyles }}' type='text/css' media='all' />

<div id='{{ chatId }}' class='wcContainer wcChannelUsersWidget {% if title %} wcWindowTitleIncluded {% endif title %}'>
    {% if title %}
        <div class='wcWindowTitle'>{{ title }}</div>
    {% endif title %}

    <div class='wcUsersList'>
        {{ usersList }}
        {% if !usersList %}
            <div class='wcUsersListEmpty'>{{ messageUsersListEmpty }}</div>
        {% endif usersList %}
    </div>
</div>

{{ cssDefinitions }}
{{ customCssDefinitions }}
