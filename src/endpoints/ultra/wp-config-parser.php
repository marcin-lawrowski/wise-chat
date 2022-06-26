<?php

$tokens = array();

/**
 * Tokenize standard WP config file:
 *
 * @return bool
 */
function loadConfigFile() {
    global $tokens;

    $filePath = '../../../../../../wp-config.php';
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return false;
    }

    $tokens = token_get_all(file_get_contents($filePath));

    return true;
}

function clearConfigFileData() {
    global $tokens;

    $tokens = array();
}

/**
 * Returns all constants defined using 'define' keyword.
 *
 * @return array
 */
function getConfigConstants() {
    global $tokens;

    $constants = array();
    $isConstant = false;
    $constantName = null;
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $tokenName = token_name($token[0]);

            if ($tokenName === 'T_STRING' && strtolower($token[1]) === 'define') {
                $isConstant = true;
                $constantName = null;
                continue;
            }

            if ($isConstant === true && $constantName === null && $tokenName === 'T_CONSTANT_ENCAPSED_STRING') {
                $constantName = trim($token[1], '"\'');
                continue;
            }

            if ($isConstant === true && $constantName !== null && $tokenName !== 'T_WHITESPACE') {
                $constants[$constantName] = trim($token[1], '"\'');
                $isConstant = false;
                $constantName = null;
                continue;
            }
        }
    }

    return $constants;
}

/**
 * Returns all variables.
 *
 * @return array
 */
function getConfigVariables() {
    global $tokens;

    $variables = array();
    $isVariable = false;
    $variableName = null;
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $tokenName = token_name($token[0]);

            if ($tokenName === 'T_VARIABLE') {
                $isVariable = true;
                $variableName = trim($token[1], '$');;
                continue;
            }

            if ($isVariable === true && $variableName !== null && $tokenName !== 'T_WHITESPACE') {
                $variables[$variableName] = trim($token[1], '"\'');
                $isVariable = false;
                $variableName = null;
                continue;
            }
        }
    }

    return $variables;
}