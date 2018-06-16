<?php

/*********************************************************************

 ページカレンダー表示プラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_page_calender()
{
	global $freo;

	//表示年月日取得
	if (isset($_GET['date'])) {
		$date = $_GET['date'];
	} else {
		$date = null;
	}

	if (preg_match('/^(\d\d\d\d)$/', $date, $matches)) {
		$year  = $matches[1];
		$month = 1;
		$day   = 1;
	} elseif (preg_match('/^(\d\d\d\d)(\d\d)$/', $date, $matches)) {
		$year  = $matches[1];
		$month = $matches[2];
		$day   = 1;
	} elseif (preg_match('/^(\d\d\d\d)(\d\d)\d\d$/', $date, $matches)) {
		$year  = $matches[1];
		$month = $matches[2];
		$day   = 1;
	} else {
		$year  = date('Y');
		$month = date('m');
		$day   = 1;
	}

	//検索条件設定
	$condition = null;

	//制限されたページを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$page_filters = freo_filter_page('user', array_keys($freo->refer['pages']));
		$page_filters = array_keys($page_filters, true);
		$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
		if (!empty($page_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_filters) . ')';
		}

		$page_securities = freo_security_page('user', array_keys($freo->refer['pages']), array('password'));
		$page_securities = array_keys($page_securities, true);
		$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
		if (!empty($page_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_securities) . ')';
		}
	}

	//ページ取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT datetime FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND DATE_FORMAT(datetime, \'%Y%m\') = :month AND (close IS NULL OR close >= :now2) ' . $condition);
	} else {
		$stmt = $freo->pdo->prepare('SELECT datetime FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND STRFTIME(\'%Y%m\', datetime) = :month AND (close IS NULL OR close >= :now2) ' . $condition);
	}
	$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':month', $year . $month);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$page_days = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/^\d\d\d\d-\d\d-(\d\d)/', $data['datetime'], $matches)) {
			$page_days[intval($matches[1])] = true;
		}
	}

	//祝日定義（2000年～2020年）
	$holidays = Array(
		'2000' => '0101,0110,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2001' => '0101,0108,0211,0212,0320,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2002' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2003' => '0101,0113,0211,0321,0429,0503,0504,0505,0721,0915,0923,1013,1103,1123,1124,1223',
		'2004' => '0101,0112,0211,0320,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2005' => '0101,0110,0211,0320,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2006' => '0101,0102,0109,0211,0321,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2007' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2008' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2009' => '0101,0112,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2010' => '0101,0111,0211,0321,0322,0429,0503,0504,0505,0719,0920,0923,1011,1103,1123,1223',
		'2011' => '0101,0110,0211,0321,0429,0503,0504,0505,0718,0919,0923,1010,1103,1123,1223',
		'2012' => '0101,0102,0109,0211,0320,0429,0430,0503,0504,0505,0716,0917,0922,1008,1103,1123,1223,1224',
		'2013' => '0101,0114,0211,0320,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2014' => '0101,0113,0211,0321,0429,0503,0504,0505,0506,0721,0915,0923,1013,1103,1123,1124,1223',
		'2015' => '0101,0112,0211,0321,0429,0503,0504,0505,0506,0720,0921,0922,0923,1012,1103,1123,1223',
		'2016' => '0101,0111,0211,0320,0321,0429,0503,0504,0505,0718,0919,0922,1010,1103,1123,1223',
		'2017' => '0101,0102,0109,0211,0320,0429,0503,0504,0505,0717,0918,0923,1009,1103,1123,1223',
		'2018' => '0101,0108,0211,0212,0321,0429,0430,0503,0504,0505,0716,0917,0923,0924,1008,1103,1123,1223,1224',
		'2019' => '0101,0114,0211,0321,0429,0503,0504,0505,0506,0715,0916,0923,1014,1103,1104,1123,1223',
		'2020' => '0101,0113,0211,0320,0429,0503,0504,0505,0506,0720,0921,0922,1012,1103,1123,1223'
	);

	//投稿日一覧取得
	$key  = date('w', strtotime("$year-$month-01"));
	$last = date('t', strtotime("$year-$month-01"));
	$type = '';

	for ($i = 0; $i < 42; $i++) {
		if ($i == $key) {
			$type = 'day';
		} elseif ($day > $last) {
			$type = '';
		}
		if ($i == 35 and !$type) {
			break;
		}

		if ($type and $i % 7 == 0) {
			$type = 'sunday';
		} elseif ($type and $i % 7 == 6) {
			$type = 'satday';
		} elseif ($type) {
			$type = 'day';
		}

		if ($type) {
			if (isset($holidays[$year]) and strpos($holidays[$year], sprintf('%02d%02d', $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['page_calender']['holiday_yymmdd'], sprintf('%04d%02d%02d', $year, $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['page_calender']['holiday_mmdd'], sprintf('%02d%02d', $month, $day)) !== false) {
				$type = 'sunday';
			} elseif (strpos($freo->config['plugin']['page_calender']['holiday_dd'], sprintf('%02d', $day)) !== false) {
				$type = 'sunday';
			}
		}

		$calenders[] = array(
			'day'  => $day,
			'date' => sprintf('%04d%02d%02d', $year, $month, $day),
			'type' => $type,
			'flag' => isset($page_days[$day]) ? true : false
		);

		if ($type) {
			$day++;
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_calenders'         => $calenders,
		'plugin_page_calender_year'     => $year,
		'plugin_page_calender_month'    => $month,
		'plugin_page_calender_previous' => date('Ym', strtotime('-1 month', strtotime($year . '-' . $month . '-01'))),
		'plugin_page_calender_next'     => date('Ym', strtotime('+1 month', strtotime($year . '-' . $month . '-01')))
	));

	return;
}

?>
