<?php

/*********************************************************************

 人気コンテンツプラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_popularity()
{
	global $freo;

	//カウント取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_popularies WHERE status = \'publish\' ORDER BY count DESC, parameter LIMIT :limit');
	$stmt->bindValue(':limit', intval($freo->config['plugin']['popularity']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_popularities = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_popularities[$data['parameter']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_popularities' => $plugin_popularities
	));

	return;
}

?>
