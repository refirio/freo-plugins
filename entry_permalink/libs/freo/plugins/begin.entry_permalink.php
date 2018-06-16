<?php

/*********************************************************************

 エントリー固定リンクプラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_entry_permalink()
{
	global $freo;

	if (!isset($_GET['id']) and !isset($_GET['code']) and isset($freo->parameters[1]) and preg_match('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/', $freo->parameters[1], $matches)) {
		$datetime = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] . ':' . $matches[6];

		if ($freo->config['plugin']['entry_permalink']['key'] == 'datetime') {
			$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE datetime = :datetime LIMIT 1');
		} else {
			$stmt = $freo->pdo->prepare('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE created = :datetime LIMIT 1');
		}
		$stmt->bindValue(':datetime', $datetime);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$_GET['id'] = $data['id'];
		}
	}

	return;
}

?>
