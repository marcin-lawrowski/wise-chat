{% variable messageClasses %}
	wcMessage {% if isAuthorWpUser %} wcWpMessage {% endif isAuthorWpUser %} {% if isAuthorCurrentUser %} wcCurrentUserMessage {% endif isAuthorCurrentUser %}
{% endvariable messageClasses %}

<div class="{{ messageClasses }}" data-id="{{ messageId }}" data-chat-user-id="{{ messageChatUserId }}">
	<span class="wcMessageUser" {% if isTextColorSetForUserName %}style="color:{{ textColor }}"{% endif isTextColorSetForUserName %}>
		{{ renderedUserName }}
	</span>
	<span class="wcMessageTime" data-utc="{{ messageTimeUTC }}"></span>
	<div class="wcActionWrapper">	
		{% if showDeleteButton %}
			<a href="#" class="wcAdminAction wcMessageDeleteButton" data-id="{{ messageId }}" title="Delete the message"></a>
		{% endif showDeleteButton %}
		{% if showBanButton %}
			<a href="#" class="wcAdminAction wcUserBanButton" data-id="{{ messageId }}" title="Ban this user"></a>
		{% endif showBanButton %}
		{% if showKickButton %}
			<a href="#" class="wcAdminAction wcUserKickButton" data-id="{{ messageId }}" title="Kick this user"></a>
		{% endif showKickButton %}
		{% if showSpamButton %}
			<a href="#" class="wcAdminAction wcSpamReportButton" data-id="{{ messageId }}" title="Report spam"></a>
		{% endif showSpamButton %}
	</div>
	<span class="wcMessageContent" {% if isTextColorSetForMessage %}style="color:{{ textColor }}"{% endif isTextColorSetForMessage %}>
		{{ messageContent }}
	</span>
</div>