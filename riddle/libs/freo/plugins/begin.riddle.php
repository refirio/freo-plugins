<?php

/*********************************************************************

 なぞなぞ認証プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_riddle()
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
	} elseif (!empty($_SESSION['plugin']['riddle']['approved'])) {
		return;
	}

	$auth_flag = false;
	if (isset($_POST['plugin']['riddle']['answer']) and $_POST['plugin']['riddle']['answer'] != '') {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles WHERE id = :id AND answer = :answer');
		$stmt->bindValue(':id',     $_POST['plugin']['riddle']['id'], PDO::PARAM_INT);
		$stmt->bindValue(':answer', $_POST['plugin']['riddle']['answer']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$auth_flag = true;
		}
	}

	if ($auth_flag) {
		$_SESSION['plugin']['riddle']['approved'] = true;
	} else {
		$freo->smarty->append('errors', 'なぞなぞの答えが違います。');
	}

	return;
}

?>
