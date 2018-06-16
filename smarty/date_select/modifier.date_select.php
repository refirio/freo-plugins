<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty date_select modifier plugin
 *
 * Type:    modifier
 * Name:    date_select
 * Purpose: format a number of date for select box
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  integer
 * @param  string
 * @param  string
 * @param  string
 * @param  integer
 * @param  integer
 * @return string
 */
function smarty_modifier_date_select($value, $type = '', $begin = '', $end = '', $from = 0, $to = 0)
{
	$value = intval($value);

	switch ($type) {
		case 'year':
			$from = $from ? $value - $from : $value - 10;
			$to   = $to   ? $value + $to   : $value + 10;
			break;
		case 'month':
			$from = 1;
			$to   = 12;
			break;
		case 'day':
			$from = 1;
			$to   = 31;
			break;
		case 'hour':
			$from = 0;
			$to   = 23;
			break;
		case 'minute':
			$from = 0;
			$to   = 59;
			break;
		case 'second':
			$from = 0;
			$to   = 59;
			break;
		default:
			$from = 0;
			$to   = 0;
	}

	$list = '';

	for ($i = $from; $i <= $to; $i++) {
		$list .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == $value ? ' selected="selected"' : '') . '>' . $begin . $i . $end . '</option>';
	}

    return $list;
}

?>
