<?php 

/**
 * Wise Chat admin messages filters tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatFiltersTab extends WiseChatAbstractTab {
	
	public function getFields() {
		return array(
			array('_section', 'Bad Words Filter', 'Uses a dictionary to filter bad words (supported languages: English and Polish)'),
			array('filter_bad_words', 'Enable Filter', 'booleanFieldCallback', 'boolean'),
			array('bad_words_replacement_text', 'Replacement Text', 'stringFieldCallback', 'string', 'A text that is used to replace a bad word. Empty field means that asterisk is used.'),
			array('_section', 'Custom Filters', 'Filters are rules that are applied to every message that is posted'),
			array('filters', 'Filters', 'filterListCallback', 'void'),
			array('filter_add', 'New Filter', 'filterAddCallback', 'void'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'filter_bad_words' => 1,
			'filters' => null,
			'filter_add' => null,
			'bad_words_replacement_text' => ''
		);
	}
	
	public function getParentFields() {
		return array(
			'bad_words_replacement_text' => 'filter_bad_words'
		);
	}
	
	public function addFilterAction() {
		$type = $_GET['type'];
		$replace = stripslashes($_GET['replace']);
		$replaceWith = stripslashes($_GET['replaceWith']);
		
		try {
			$this->filtersDAO->addFilter($type, $replace, $replaceWith);
			$this->addMessage('Filter has been added');
		} catch (Exception $ex) {
			$this->addErrorMessage($ex->getMessage());
		}
	}
	
	public function deleteFilterAction() {
		$id = intval($_GET['id']);
		
		$this->filtersDAO->deleteById($id);
		$this->addMessage('Filter has been deleted');
	}
	
	public function filterListCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		
		$summary = $this->filtersDAO->getAll(true);
		
		$html = "<table class='wp-list-table widefat'>";
		if (count($summary) == 0) {
			$html .= '<tr><td>No filters created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Replace</th><th>With</th><th>Actions</th></tr></thead>';
		}
		
		foreach ($summary as $key => $filter) {
			$deleteURL = $url.'&wc_action=deleteFilter&id='.intval($filter['id']);
			$deleteLink = "<a href='{$deleteURL}' title='Removes the filter' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
			
			$html .= sprintf(
				'<tr class="%s"><td>%s</td><td>%s</td><td>%s</td></tr>', 
				($key % 2 == 0 ? 'alternate' : ''),
				$filter['label'], $filter['with'], $deleteLink
			);
		}
		$html .= '</table>';
		$html .= '<p class="description">Notice: every message posted to a channel will be processed by each filter in the defined order.</p>';
		
		print($html);
	}
	
	public function filterAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addFilter");
		
		$replaceOptions = array_merge(array('' => '-- what to replace --'), $this->filtersDAO->getAllTypes());
		$replaceOptionsHtml = '';
		foreach ($replaceOptions as $key => $option) {
			$replaceOptionsHtml .= sprintf('<option value="%s">%s</option>', $key, $option);
		}
		
		printf("
			<script>
				function onNewFilterReplaceChanged(selectElement) {
					var replaceRow = jQuery('#newFilterCustomReplace');
					var type = jQuery(selectElement).val();
					var replace = jQuery('#newFilterReplace');
					
					if (type == 'regexp') {
						replace.attr('placeholder', 'Regular expression');
						replaceRow.show();
					} else if (type == 'text') {
						replace.attr('placeholder', 'Text');
						replaceRow.show();
					} else {
						replaceRow.hide();
					}
					
					replace.val('');
				}
				
				function newFilterAdd(addLink) {
					var filterType = jQuery('#newFilterType').val();
					var replaceSource = jQuery('#newFilterReplace').val();
					var replaceWith = jQuery('#newFilterReplaceWith').val();
					
					if (filterType.length == 0) {
						alert('Please select what to replace');
						return false;
					} else if (filterType == 'regexp' && replaceSource.length == 0) {
						alert('Please type a regular expression');
						return false;
					} else if (filterType == 'text' && replaceSource.length == 0) {
						alert('Please type a text');
						return false;
					} else {
						var href = jQuery(addLink).attr('href') + 
								'&type=' + encodeURIComponent(filterType) + 
								'&replace=' + encodeURIComponent(replaceSource) + 
								'&replaceWith=' + encodeURIComponent(replaceWith);
						jQuery(addLink).attr('href', href);
					}
				
					return true;
				}
			</script>
		");
		
		printf(
			'<table class="wp-list-table widefat">'.
				'<tr>'.
					'<td class="th-full">Replace:</td>'.
					'<td><select id="newFilterType" onchange="%s">%s</select></td>'.
				'</tr>'.
				'<tr id="newFilterCustomReplace" style="display:none;">'.
					'<td class="th-full"></td>'.
					'<td><input type="text" id="newFilterReplace" style="width: 400px;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">With:</td>'.
					'<td><input type="text" placeholder="With what to replace" id="newFilterReplaceWith" style="width: 400px;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td colspan="2"><a class="button-secondary" href="%s" title="Adds a new filter" onclick="%s">Add Filter</a></td>'.
				'</tr>'.
			'</table>',
			
			'onNewFilterReplaceChanged(this)',
			$replaceOptionsHtml,
			wp_nonce_url($url),
			'return newFilterAdd(this)'
		);
	}
}