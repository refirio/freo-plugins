<?php
/**
 * Smarty   plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty number_format modifier plugin
 *
 * Type:    modifier
 * Name:    number_format
 * Purpose: format a number by function of 'number_format'
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  integer
 * @param  integer
 * @param  string
 * @param  string
 * @return string
 */
function smarty_modifier_number_format($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',')
{
	return number_format($number, $decimals, $dec_point, $thousands_sep);
}

?>
