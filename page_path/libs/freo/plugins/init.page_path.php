<?php

/*********************************************************************

 ページパス調整プラグイン (2010/10/08)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_init_page_path()
{
	global $freo;

	//パラメータ取得
	if (isset($freo->parameters[0])) {
		$parameters = array();
		$i          = -1;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$parameter = implode('/', $parameters);
	} else {
		$parameter = null;
	}

	//ページ確認
	if ($parameter) {
		$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $parameter);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$_REQUEST['freo']['mode'] = 'page';
			$_REQUEST['freo']['work'] = $freo->parameters[0];

			$freo->parameters = array_merge(array('page'), $freo->parameters);
		}
	}

	return;
}

?>
