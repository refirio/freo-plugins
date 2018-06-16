<?php

/*********************************************************************

 直接リンク防止プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_protect()
{
	global $freo;

	if ($_REQUEST['freo']['mode'] == 'default' or $_REQUEST['freo']['mode'] == 'protect') {
		return;
	}
	if (empty($_SERVER['HTTP_REFERER'])) {
		return;
	}
	if (preg_match('/^' . preg_quote(FREO_HTTP_URL, '/') . '/', $_SERVER['HTTP_REFERER'])) {
		return;
	}
	if (FREO_HTTPS_URL and preg_match('/^' . preg_quote(FREO_HTTPS_URL, '/') . '/', $_SERVER['HTTP_REFERER'])) {
		return;
	}
	if ($freo->config['plugin']['protect']['permission']) {
		$permissions = explode(',', $freo->config['plugin']['protect']['permission']);

		foreach ($permissions as $permission) {
			if (preg_match('/^' . preg_quote($permission, '/') . '/', $_SERVER['HTTP_REFERER'])) {
				return;
			}
		}
	}

	freo_redirect('protect');

	return;
}

?>
