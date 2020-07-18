<link rel='stylesheet' href='{{ themeStyles }}' type='text/css' media='all' />

<div class='wcContainer {% if windowTitle %} wcWindowTitleIncluded {% endif windowTitle %}'>
	{% if windowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif windowTitle %}

	<div class="wcWindowContent">
		<div class="wcUserNameHint">{{ messageEnterUserName }}</div>
		
		<form method="post" class="wcUserNameForm" action="{{ formAction }}">
			<input type="text" name="wcUserName" required />
			<input type="submit" value="{{ messageLogin }}" />
		</form>
		
		{% if authenticationError %}
			<div class='wcError wcUserNameError'>{{ authenticationError }}</div>
		{% endif authenticationError %}
	</div>
</div>