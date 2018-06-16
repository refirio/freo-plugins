<?php

/*********************************************************************

 タイトル設定プラグイン (2010/11/05)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_title()
{
	global $freo;

	if (empty($freo->parameters)) {
		return;
	} else {
		$parameters = $freo->parameters;
	}

	$titles = explode("\n", $freo->config['plugin']['title']['titles']);

	while (!empty($parameters)) {
		$path = implode('/', $parameters);

		foreach ($titles as $title) {
			if (!$title) {
				continue;
			}

			list($id, $text) = explode(',', $title, 2);

			if ($path == $id) {
				$freo->smarty->assign(array(
					'plugin_title' => $text
				));

				return;
			}
		}

		array_pop($parameters);
	}

	return;
}

?>
