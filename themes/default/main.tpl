{% variable containerClasses %}
	wcContainer 
	{% if showMessageSubmitButton %} wcControlsButtonsIncluded {% endif showMessageSubmitButton %}
	{% if enableImagesUploader %} wcControlsButtonsIncluded {% endif enableImagesUploader %}
	{% if enableAttachmentsUploader %} wcControlsButtonsIncluded {% endif enableAttachmentsUploader %}
    {% if showEmoticonInsertButton %} wcControlsButtonsIncluded {% endif showEmoticonInsertButton %}
	{% if showUsersList %} wcUsersListIncluded {% endif showUsersList %}
{% endvariable containerClasses %}

<link rel='stylesheet' id='wise_chat_theme_{{ chatId }}-css' href='{{ themeStyles }}' type='text/css' media='all' />

<div id='{{ chatId }}' class='{{ containerClasses }}' data-wc-config="{{ jsOptionsEncoded }}">
	{% if windowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif windowTitle %}
	
	{% if inputControlsBottomLocation %}
		<div class='wcMessages {% if messagesInline %}wcMessagesInline{% endif messagesInline %}'>{{ messages }}</div>
		
		{% if showUsersList %}
			<div class='wcUsersList'>{{ usersList }}</div>
		{% endif showUsersList %}
		
		{% if showUsersCounter %}
			<div class='wcUsersCounter'>
				{{ messageTotalUsers }}: <span>{{ totalUsers }}{% if channelUsersLimit %}&nbsp;/&nbsp;{{ channelUsersLimit }} {% endif channelUsersLimit %}</span>
			</div>
			<br class='wcClear' />
		{% endif showUsersCounter %}
	{% endif inputControlsBottomLocation %}

    {% if allowToSendMessages %}
        <div class="wcControls">
            {% if showUserName %}
                <span class='wcCurrentUserName'>{{ currentUserName }}{% if isCurrentUserNameNotEmpty %}:{% endif isCurrentUserNameNotEmpty %}</span>
            {% endif showUserName %}

            {% if showMessageSubmitButton %}
                <input type='button' class='wcSubmitButton' value='{{ messageSubmitButtonCaption }}' />
            {% endif showMessageSubmitButton %}

            <div class="wcToolButtonWrapper">
                {% if enableAttachmentsUploader %}
                    <a href="#" class="wcToolButton wcAddFileAttachment" title="{{ messageAttachFileHint }}"><input type="file" accept="{{ attachmentsExtensionsList }}" class="wcFileUploadFile" title="{{ messageAttachFileHint }}" /></a>
                {% endif enableAttachmentsUploader %}

                {% if enableImagesUploader %}
                    <a href="#" class="wcToolButton wcAddImageAttachment" title="{{ messagePictureUploadHint }}"><input type="file" accept="image/*;capture=camera" class="wcImageUploadFile" title="{{ messagePictureUploadHint }}" /></a>
                {% endif enableImagesUploader %}

                {% if showEmoticonInsertButton %}
                    <a href="#" class="wcToolButton wcInsertEmoticonButton" title="{{ messageInsertEmoticon }}"></a>
                {% endif showEmoticonInsertButton %}
            </div>

            <div class='wcInputContainer'>
                {% if multilineSupport %}
                    <textarea class='wcInput' maxlength='{{ messageMaxLength }}' placeholder='{{ hintMessage }}'></textarea>
                {% endif multilineSupport %}
                {% if !multilineSupport %}
                    <input class='wcInput' type='text' maxlength='{{ messageMaxLength }}' placeholder='{{ hintMessage }}' title="{{ messageInputTitle }} " />
                {% endif multilineSupport %}

                <progress class="wcMainProgressBar" max="100" value="0" style="display: none;"> </progress>
            </div>

            {% if enableAttachmentsPanel %}
                <div class="wcMessageAttachments" style="display: none;">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=" class="wcImageUploadPreview" alt="Images preview" style="display: none;" />
                    <span class="wcFileUploadNamePreview" style="display: none;"></span>
                    <a href="#" class="wcAttachmentClear"><img src='{{ baseDir }}/gfx/icons/x.png' alt="Delete attachment" class='wcIcon' /></a>
                </div>
            {% endif enableAttachmentsPanel %}

            {% if showCustomizationsPanel %}
                <div class='wcCustomizations'>
                    <a href='#' class='wcCustomizeButton'>{{ messageCustomize }}</a>
                    <div class='wcCustomizationsPanel' style='display:none;'>
                        {% if allowChangeUserName %}
                            <div class="wcCustomizationsProperty">
                                <label>{{ messageName }}: <input class='wcUserName' type='text' {% if userNameLengthLimit %}maxlength='{{ userNameLengthLimit }}'{% endif userNameLengthLimit %} value='{{ currentUserName }}' required /></label>
                                <input class='wcUserNameApprove' type='button' value='{{ messageSave }}' />
                            </div>
                        {% endif allowChangeUserName %}
                        {% if allowMuteSound %}
                            <div class="wcCustomizationsProperty">
                                <label>{{ messageMuteSounds }} <input class='wcMuteSound' type='checkbox' value='1' {% if muteSounds %} checked="1" {% endif muteSounds %} /></label>
                            </div>
                        {% endif allowMuteSound %}
                        {% if allowChangeTextColor %}
                            <div class="wcCustomizationsProperty">
                                <label>{{ messageTextColor }}: <input class='wcTextColor' type='text' value="{{ textColor }}" /></label>
                                <input class='wcTextColorReset' type='button' value='{{ messageReset }}' />
                            </div>
                        {% endif allowChangeTextColor %}
                    </div>
                </div>
            {% endif showCustomizationsPanel %}

        </div>
    {% endif allowToSendMessages %}

	{% if inputControlsTopLocation %}
		<div class='wcMessages {% if messagesInline %}wcMessagesInline{% endif messagesInline %}'>{{ messages }}</div>
		
		{% if showUsersList %}
			<div class='wcUsersList'>{{ usersList }}</div>
		{% endif showUsersList %}
		{% if showUsersCounter %}
			<div class='wcUsersCounter'>
				{{ messageTotalUsers }}: <span>{{ totalUsers }}{% if channelUsersLimit %}&nbsp;/&nbsp;{{ channelUsersLimit }} {% endif channelUsersLimit %}</span>
			</div>
			<br class='wcClear' />
		{% endif showUsersCounter %}
	{% endif inputControlsTopLocation %}
</div>

{{ cssDefinitions }}
{{ customCssDefinitions }}

<script>
    {% if redirectURL %}
        window.location.href = '{{ redirectURL }}';
    {% endif redirectURL %}
</script>
