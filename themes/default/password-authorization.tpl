<link rel='stylesheet' href='{{ themeStyles }}' type='text/css' media='all' />

<div class='wcContainer {% if windowTitle %} wcWindowTitleIncluded {% endif windowTitle %}'>
	{% if windowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif windowTitle %}
	
	<div class="wcWindowContent">
		<div class="wcChannelProtectionHint">{{ messageChannelPasswordAuthorizationHint }}</div>
		
		<form method="post" class="wcChannelProtectionForm" action="{{ formAction }}">
			<input type="password" name="wcChannelPassword" required />
			<input type="submit" value="{{ messageLogin }}" />
		</form>
		
		{% if authorizationError %}
			<div class='wcError wcChannelAuthorizationError'>{{ authorizationError }}</div>
		{% endif authorizationError %}
	</div>
</div>