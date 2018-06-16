<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty recently modifier plugin
 *
 * Type:    modifier
 * Name:    recently
 * Purpose: examine whether the recent datetime or not
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  integer
 * @param  string
 * @return boolean
 */
function smarty_modifier_recently($datetime, $span = 1, $type = 'day')
{
	if ($type == 'day') {
		$span *= 60 * 60 * 24;
	} elseif ($type == 'hour') {
		$span *= 60 * 60;
	} elseif ($type == 'minute') {
		$span *= 60;
	}

	if (time() - strtotime($datetime) < $span) {
		return true;
	} else {
		return false;
	}
}

?>
