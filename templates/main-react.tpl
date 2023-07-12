{% if classicMode %}
	<div id="{{ chatId }}" class="wcContainer wcContainerClassic {{ themeClassName }}" data-wc-config="{{ jsOptionsEncoded }}">
		<div class="wcClassic">
			<div class="wcLoadingContainer">
				<div class="wcTitle">{{ title }}</div>
				<div class="wcLoading">
					<div class="wcLoadingMessage">{{ loading }}</div>
				</div>
			</div>
		</div>
	</div>
{% endif classicMode %}

{{ cssDefinitions }}
{{ customCssDefinitions }}