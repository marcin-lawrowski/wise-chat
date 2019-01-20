{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}" data-chat-user-id="{{ messageChatUserId }}">
	{% if showDeleteButton %}
		<a href="javascript://" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"></a>
	{% endif showDeleteButton %}
	{% if showBanButton %}
		<a href="javascript://" class="wcAdminAction wcUserBanButton" data-id="{{ messageId }}" title="Ban this user"></a>
	{% endif showBanButton %}
	{% if showKickButton %}
		<a href="javascript://" class="wcAdminAction wcUserKickButton" data-id="{{ messageId }}" title="Kick this user"></a>
	{% endif showKickButton %}
	{% if showSpamButton %}
		<a href="javascript://" class="wcAdminAction wcSpamReportButton" data-id="{{ messageId }}" title="Report spam"></a>
	{% endif showSpamButton %}
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}"></span>
	
	<span class="wcMessageUser" {% if isTextColorSetForUserName %}style="color:{{ textColor }}"{% endif isTextColorSetForUserName %}>
		{{ renderedUserName }}: 
	</span>
	<span class="wcMessageContent" {% if isTextColorSetForMessage %}style="color:{{ textColor }}"{% endif isTextColorSetForMessage %}>
		{{ messageContent }}
	</span>
</div>