<?php

/**
 * Wise Chat simple template engine.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatTemplater {
	const REGEXP_VARIABLE = '/\{\{\s*([\w\d]+)\s*\}\}/is';
	const REGEXP_IF_OPEN = '/\{\%\s*if\s+(!?[\w\d]+)\s*\%\}/is';
	const REGEXP_IF_BLOCK_TEMPLATE = '/\{\%%\s*if\s+%s\s*\%%\}(.*?)\{\%%\s*endif\s+%s\s*\%%\}/is';
	const REGEXP_VARIABLE_BLOCK = '/\{\%\s*variable\s+([\w\d]+)\s*\%\}(.*?)\{\%\s*endvariable\s+\1\s*\%\}/is';

	/**
	* @var string Base directory
	*/
	private $baseDir;
	
	/**
	* @var string Template contents
	*/
	private $template;
	
	public function __construct($baseDir) {
		$this->baseDir = $baseDir;
	}
	
	/**
	* Loads template file.
	*
	* @param string $templateFile
	*
	* @throws Exception If template file does not exist
	*/
	public function setTemplateFile($templateFile) {
		$templateFilePath = $this->baseDir.'/'.$templateFile;
		
		if (!file_exists($templateFilePath)) {
			throw new Exception('Template file does not exist.');
		}
		
		$this->template = file_get_contents($templateFilePath);
	}
	
	/**
	* Sets template.
	*
	* @param string $template
	*
	* @return null
	*/
	public function setTemplate($template) {
		$this->template = $template;
	}
	
	/**
	* Returns rendered template.
	*
	* @param array $data
	*
	* @return string
	*/
	public function render($data) {
		$template = $this->renderVariables($this->template, $data);
		$template = $this->renderIfBlocks($template, $data);
		
		$template = $this->detectVariablesCreation($template, $data);
		$template = $this->renderVariables($template, $data);
	
		return $template;
	}
	
	/**
	* Renders variables, usage example: {{ variableName }}
	*
	* @param string $template
	* @param array $data
	*
	* @return string
    * @throws Exception If a variable referenced by given variable name is an array or an object
	*/
	private function renderVariables($template, $data) {
		$matchedVariables = array();
		preg_match_all(self::REGEXP_VARIABLE, $template, $matchedVariables);
		foreach ($matchedVariables[1] as $key => $variable) {
			$fullVariableMatch = $matchedVariables[0][$key];
			if (array_key_exists($variable, $data)) {
				$value = $data[$variable];
				if (is_array($value) || is_object($value)) {
					throw new Exception('Rendering variables cannot be arrays and objects.');
				}
				$template = str_replace($fullVariableMatch, $value, $template);
			}
		}
		
		return $template;
	}
	
	/**
	* Renders IF statements, usage example: {% if variableName %} some content {% endif variableName %}
	*
	* @param string $template
	* @param array $data
	*
	* @return string
	*/
	private function renderIfBlocks($template, $data) {
		$matchedIfOpenings = array();
		preg_match_all(self::REGEXP_IF_OPEN, $template, $matchedIfOpenings);
		
		foreach ($matchedIfOpenings[1] as $ifVariable) {
			$positiveCondition = strpos($ifVariable, '!') === false;
			$ifVariableOpen = $ifVariable;
			$ifVariable = str_replace('!', '', $ifVariable);
			
			$matchedIfBlocks = array();
			$blockRegExp = sprintf(self::REGEXP_IF_BLOCK_TEMPLATE, $ifVariableOpen, $ifVariable);
			preg_match_all($blockRegExp, $template, $matchedIfBlocks);
			foreach ($matchedIfBlocks[0] as $key => $ifBlock) {
				$replacement = '';

                if (array_key_exists($ifVariable, $data)) {
                    $variableValue = $data[$ifVariable];

                    if (
                        (($variableValue === null) && !$positiveCondition) ||
                        (is_bool($variableValue) && $variableValue === $positiveCondition) ||
                        (is_string($variableValue) && (strlen($variableValue) > 0) === $positiveCondition) ||
                        ((is_float($variableValue) || is_int($variableValue)) && ($variableValue > 0) === $positiveCondition) ||
                        (is_array($variableValue) && (count($variableValue) > 0) === $positiveCondition)
                    ) {
                        $replacement = trim($matchedIfBlocks[1][$key]);
                    }
                }

                $template = str_replace($ifBlock, $replacement, $template);
            }
		}
	
		return $template;
	}
	
	/**
	* Detects VARIABLE statements, usage example: {% variable variableName %} some content {% endvariable variableName %}
	*
	* @param string $template
	* @param array $data
	*
	* @return string
	*/
	private function detectVariablesCreation($template, &$data) {
		$matchedVariableBlocks = array();
		preg_match_all(self::REGEXP_VARIABLE_BLOCK, $template, $matchedVariableBlocks);
		foreach ($matchedVariableBlocks[0] as $key => $variableBlock) {
			$template = str_replace($variableBlock, '', $template);
			$data[$matchedVariableBlocks[1][$key]] = trim($matchedVariableBlocks[2][$key]);
		}
	
		return $template;
	}
}