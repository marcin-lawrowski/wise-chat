{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}" data-chat-user-id="{{ messageChatUserId }}">
	{% if showDeleteButton %}
		<a href="javascript://" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"><img src='{{ baseDir }}/gfx/icons/x.png' class='wcIcon' /></a>
	{% endif showDeleteButton %}
	{% if showBanButton %}
		<a href="javascript://" class="wcAdminAction wcUserBanButton" data-id="{{ messageId }}" title="Ban this user"><img src='{{ baseDir }}/gfx/icons/block.png' class='wcIcon' /></a>
	{% endif showBanButton %}
	
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}"></span>
	
	<span class="wcMessageUser" {% if isTextColorSetForUserName %}style="color:{{ textColor }}"{% endif isTextColorSetForUserName %}>
		{{ renderedUserName }}: 
	</span>
	<span class="wcMessageContent" {% if isTextColorSetForMessage %}style="color:{{ textColor }}"{% endif isTextColorSetForMessage %}>
		{{ messageContent }}
	</span>
</div>