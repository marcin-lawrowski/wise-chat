<link rel='stylesheet' href='{{ themeStyles }}' type='text/css' media='all' />

<div class='wcContainer'>
	{% if windowTitle %}
		<div class='wcWindowTitle'>{{ windowTitle }}</div>
	{% endif windowTitle %}
	
	<div class="wcWindowContent">
		<div class='wcError {{ cssClass }}'>{{ errorMessage }}</div>
	</div>
</div>