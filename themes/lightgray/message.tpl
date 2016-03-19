{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}" data-chat-user-id="{{ messageChatUserId }}">
	<span class="wcMessageUser" {% if isTextColorSet %}style="color:{{ textColor }}"{% endif isTextColorSet %}>
		{{ renderedUserName }}
	</span>
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}" {% if isTextColorSet %}style="color:{{ textColor }}"{% endif isTextColorSet %}></span>
	
	<br class='wcClear' />
	<span class="wcMessageContent" {% if isTextColorSet %}style="color:{{ textColor }}"{% endif isTextColorSet %}>
		{{ messageContent }}
		
		{% if showDeleteButton %}
			<a href="javascript://" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"><img src='{{ baseDir }}/gfx/icons/x.png' class='wcIcon' /></a>
		{% endif showDeleteButton %}
		{% if showBanButton %}
			<a href="javascript://" class="wcAdminAction wcUserBanButton" data-id="{{ messageId }}" title="Ban this user"><img src='{{ baseDir }}/gfx/icons/block.png' class='wcIcon' /></a>
		{% endif showBanButton %}
		<br class='wcClear' />
	</span>
</div>