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
{% if sidebarMode %}
	<div id="{{ chatId }}" class="wcContainer wcContainerSidebar {{ themeClassName }} {% if sidebarModeLeft %} wcSidebarLeft {% endif sidebarModeLeft %}" data-wc-config="{{ jsOptionsEncoded }}">
		<div class="wcSidebar wcDesktop">
			<div class="wcColumn">
				<div class="wcHeader">{{ title }}</div>
				<div class="wcContent">
					<div class="wcLoadingContainer">
						<div class="wcLoading">
							<div class="wcLoadingMessage">{{ loading }}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{% endif sidebarMode %}

{{ cssDefinitions }}
{{ customCssDefinitions }}