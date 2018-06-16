<?php

/*********************************************************************

 投稿キープラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_postkey()
{
	global $freo;

	if ($freo->agent['type'] == 'mobile') {
		return;
	} elseif ($freo->user['id']) {
		return;
	} elseif ($_REQUEST['freo']['mode'] == 'login') {
		return;
	} elseif ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return;
	} elseif (!empty($_SESSION['plugin']['postkey']['approved'])) {
		return;
	}

	if (empty($_POST['plugin']['postkey']['key']) or empty($_SESSION['plugin']['postkey']['key']) or $_POST['plugin']['postkey']['key'] != $_SESSION['plugin']['postkey']['key']) {
		$freo->smarty->append('errors', '投稿キーの認証に失敗しました。');
	} else {
		$_SESSION['plugin']['postkey']['approved'] = true;
	}

	return;
}

?>
