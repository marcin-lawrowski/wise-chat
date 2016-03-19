<link rel='stylesheet' href='{{ themeStyles }}' type='text/css' media='all' />

<div class='wcContainer'>
	{% if windowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif windowTitle %}

	<div class="wcWindowContent">
		<div class="wcUserNameHint">{{ messageEnterUserName }}</div>
		
		<form method="post" class="wcUserNameForm">
			<input type="hidden" value="1" name="wcUserNameSelection" />
			<input type="text" name="wcUserName" required />
			<input type="submit" value="{{ messageLogin }}" />
		</form>
		
		{% if errorMessage %}
			<div class='wcError wcUserNameError'>{{ errorMessage }}</div>
		{% endif errorMessage %}
	</div>
</div>