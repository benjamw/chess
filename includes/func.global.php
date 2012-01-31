<?php


/** function ife
 *		if-else
 *		This function returns the value if it exists (or is optionally not empty)
 *		or a default value if it does not exist (or is empty)
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @param bool optional allow empty value
 * @param bool optional change the passed reference var
 * @return mixed $var if exists (and not empty) or default otherwise
 */
function ife( & $var, $default = null, $allow_empty = true, $change_reference = false) {
	if ( ! isset($var) || ( ! (bool) $allow_empty && empty($var))) {
		if ((bool) $change_reference) {
			$var = $default; // so it can also be used by reference
		}

		return $default;
	}

	return $var;
}



/** function ifer
 *		if-else reference
 *		This function returns the value if it exists (or is optionally not empty)
 *		or a default value if it does not exist (or is empty)
 *		It also changes the reference var
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @param bool optional allow empty value
 * @action updates/sets the reference var if needed
 * @return mixed $var if exists (and not empty) or default otherwise
 */
function ifer( & $var, $default = null, $allow_empty = true) {
	return ife($var, $default, $allow_empty, true);
}



/** function ifenr
 *		if-else non-reference
 *		This function returns the value if it is not empty
 *		or a default value if it is empty
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @return mixed $var if not empty or default otherwise
 */
function ifenr($var, $default = null) {
	if (empty($var)) {
		return $default;
	}

	return $var;
}

