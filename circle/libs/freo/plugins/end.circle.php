<?php

/*********************************************************************

 サークル管理プラグイン (2013/01/12)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_circle()
{
	global $freo;

	//添付ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'plugins/circle_files/' . $_GET['id'] . '/');

	//サークルカット削除
	freo_rmdir(FREO_FILE_DIR . 'plugins/circle_images/' . $_GET['id'] . '/');

	//サークル削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	return;
}

?>
