<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty parse_url modifier plugin
 *
 * Type:    modifier
 * Name:    parse_url
 * Purpose: parse a url and return its value by function of 'parse_url'
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  string
 * @return string
 */
function smarty_modifier_parse_url($url, $type)
{
	$info = parse_url($url);

	return $info[$type];
}

?>
