<?php

/*********************************************************************

 サークル管理プラグイン (2013/09/24)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_profile()
{
	global $freo;

	//ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'plugins/profile_files/' . $_GET['id'] . '/');

	//サークル削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	return;
}

?>
