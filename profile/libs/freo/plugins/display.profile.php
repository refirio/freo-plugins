<?php

/*********************************************************************

 プロフィール拡張プラグイン (2013/09/24)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';
require_once FREO_MAIN_DIR . 'freo/internals/validate_user.php';

/* メイン処理 */
function freo_display_profile()
{
	global $freo;

	//プロフィール取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles ORDER BY sort, kana, user_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profiles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profiles[$data['user_id']] = $data;
	}

	//プロフィールID取得
	$plugin_profile_keys = array_keys($plugin_profiles);

	//ファイル取得
	foreach ($plugin_profile_keys as $plugin_profile) {
		$file_dir = FREO_FILE_DIR . 'plugins/profile_files/' . $plugin_profile . '/';

		if (file_exists($file_dir)) {
			if ($dir = scandir($file_dir)) {
				foreach ($dir as $data) {
					if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
						$plugin_profiles[$plugin_profile][$matches[1]] = $data;
					}
				}
			}
		}
	}

	//プロフィールタグ取得
	$plugin_profile_tags = array();
	foreach ($plugin_profile_keys as $plugin_profile) {
		if (!$plugin_profiles[$plugin_profile]['tag']) {
			continue;
		}

		$plugin_profile_tags[$plugin_profile] = explode(',', $plugin_profiles[$plugin_profile]['tag']);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profile_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profile_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_profiles'           => $plugin_profiles,
		'plugin_profile_tags'       => $plugin_profile_tags,
		'plugin_profile_categories' => $plugin_profile_categories
	));

	return;
}

?>
