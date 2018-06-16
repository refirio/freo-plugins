<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty text_link modifier plugin
 *
 * Type:    modifier
 * Name:    text_link
 * Purpose: get host by function of 'gethostbyaddr'
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @return string
 */
function smarty_modifier_gethostbyaddr($ip)
{
	return gethostbyaddr($ip);
}

?>
