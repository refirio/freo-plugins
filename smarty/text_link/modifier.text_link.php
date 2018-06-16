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
 * Purpose: replace text from image to link
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  string
 * @return string
 */
function smarty_modifier_text_link($string, $alt = 'IMAGE')
{
	$string = preg_replace('/<img[^>]+src="([^"]+)"[^>]+alt="([^"]*)"[^>]+\/>/', '<a href="$1">' . ('$2' ? '$2' : $alt) . '</a>', $string);
	$string = preg_replace('/<img[^>]+alt="([^"]*)"[^>]+src="([^"]+)"[^>]+\/>/', '<a href="$2">' . ('$1' ? '$1' : $alt) . '</a>', $string);
	$string = preg_replace('/<img[^>]+src="([^"]+)"[^>]+\/>/', '<a href="$1">' . $alt . '</a>', $string);

	return $string;
}

?>
