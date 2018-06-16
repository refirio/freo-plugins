<?php

/*********************************************************************

 漫画表示プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_media.php';

/* メイン処理 */
function freo_page_media_comic()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_media_comic_admin();
			break;
		default:
			freo_page_media_comic_default();
	}

	return;
}

/* 管理画面 | 漫画挿入 */
function freo_page_media_comic_admin()
{
	global $freo;

	if (isset($_GET['path']) and preg_match('/^' . preg_quote(FREO_PLUGIN_MEDIA_COMIC_DIR, '/') . '([\w\-\/]+)/', $_GET['path'], $matches)) {
		$path = $matches[1];
	} else {
		freo_error('挿入したい漫画を指定してください。');
	}

	if (preg_match('/([\w\-\/]+)\/$/', $path, $matches)) {
		$path = $matches[1];
	}

	//データ割当
	$freo->smarty->assign('plugin_media_comic_path', $path);

	return;
}

/* 漫画表示 */
function freo_page_media_comic_default()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['comic']) and isset($freo->parameters[1])) {
		$parameters = array();
		$i          = 0;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$_GET['comic'] = implode('/', $parameters);
	}
	if (!isset($_GET['comic']) or !preg_match('/^[\w\-\/]+$/', $_GET['comic'])) {
		freo_error('表示したい漫画を指定してください。');
	}

	if (isset($_GET['navigation']) and $_GET['navigation'] == 'off') {
		$_GET['navigation'] = 'off';
	} else {
		$_GET['navigation'] = 'on';
	}

	if (isset($_GET['columns']) and $_GET['columns'] == 1) {
		$_GET['columns'] = 1;
	} else {
		$_GET['columns'] = 2;
	}

	if (isset($_GET['devide']) and $_GET['devide'] == 'on') {
		$_GET['devide'] = 'on';
	} else {
		$_GET['devide'] = 'off';
	}

	if (isset($_GET['direction']) and $_GET['direction'] == 'ltr') {
		$_GET['direction'] = 'ltr';
	} else {
		$_GET['direction'] = 'rtl';
	}

	if (isset($_GET['end']) and $_GET['end'] == 'on') {
		$_GET['end'] = 'on';
	} else {
		$_GET['end'] = 'off';
	}

	if (isset($_GET['page'])) {
		$_GET['page'] = intval($_GET['page']);
	} else {
		$_GET['page'] = 0;
	}

	//メディアフィルター取得
	$media_filters = freo_filter_media('user', array(FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'));
	$media_filter  = $media_filters[FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'];

	if ($media_filter) {
		freo_error($freo->config['media']['filter_message']);
	}

	//ページ保護データ取得
	$media_securities = freo_security_media('user', array(FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'));
	$media_security   = $media_securities[FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'];

	if ($media_security) {
		freo_error($freo->config['media']['restriction_message']);
	}

	//閲覧制限の有無を確認
	$restrict_flag = false;

	$media_filters = freo_filter_media('nobody', array(FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'));
	$media_filter  = $media_filters[FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'];

	if ($media_filter) {
		$restrict_flag = true;
	}

	$media_securities = freo_security_media('nobody', array(FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'));
	$media_security   = $media_securities[FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/'];

	if ($media_security) {
		$restrict_flag = true;
	}

	//表紙の有無を検証
	$cover = null;

	if ($dir = scandir(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/', 1)) {
		foreach ($dir as $entry) {
			if (preg_match('/^1\.(gif|jpeg|jpg|jpe|png)$/i', $entry)) {
				$cover = false;
			} elseif (preg_match('/^0\.(gif|jpeg|jpg|jpe|png)$/i', $entry)) {
				$cover = true;
			}
		}
	} else {
		freo_error('画像格納ディレクトリを開けません。');
	}

	if ($cover === null) {
		freo_error('漫画が見つかりません。', '404 Not Found');
	}

	//画像を取得
	$media_comics = array();
	$i            = 0;
	$count        = 0;

	if ($cover) {
		$flag = false;
	} else {
		$flag = true;
	}

	if ($dir = scandir(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/')) {
		natcasesort($dir);

		foreach ($dir as $entry) {
			if (is_file(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/' . $entry) and preg_match('/^[\d]+\.(gif|jpeg|jpg|jpe|png)$/i', $entry)) {
				$id = intval($entry);

				if ($_GET['columns'] == 2) {
					if ($id % 2 == 0) {
						$link = $i + 1;
					} else {
						$link = $i - 1;
					}
				} else {
					$link = $i + 1;
				}

				$media_comics[$i][] = array(
					'id'   => $id,
					'file' => $entry,
					'link' => $link
				);

				if ($_GET['columns'] == 2) {
					if ($flag) {
						$flag = false;
					} else {
						$flag = true;
					}

					if ($flag) {
						$i++;
					}
				} else {
					$i++;
				}

				$count++;
			}
		}
	} else {
		freo_error('画像格納ディレクトリを開けません。');
	}

	//先頭ページリンク先を調整
	if ($cover === false and $_GET['columns'] == 2) {
		$media_comics[0][0]['link'] = 0;
	}

	//末尾ページリンク先を調整
	if ($_GET['end'] == 'on') {
		if ($_GET['devide'] == 'on') {
			if ($_GET['columns'] == 2) {
				$end_link = floor($count / 2);

				if ($count % 2 + $cover) {
					$end_link += 1;
				}
			} else {
				$end_link = $count;
			}
		} else {
			if ($_GET['columns'] == 2) {
				$end_link = floor($count / 2);

				if ($count % 2 + $cover) {
					$end_link += 1;
				}
			} else {
				$end_link = $count;
			}
		}
	} else {
		if ($_GET['devide'] == 'on') {
			if ($_GET['columns'] == 2) {
				$end_link = 0;
			} else {
				$end_link = 0;
			}
		} else {
			if ($_GET['columns'] == 2) {
				$end_link = 0;
			} else {
				$end_link = 0;
			}
		}
	}
	$media_comics[count($media_comics) - 1][count($media_comics[count($media_comics) - 1]) - 1]['link'] = $end_link;

	//画像をソート
	if (ksort($media_comics, SORT_NUMERIC) == false) {
		freo_error('画像をソートできません。');
	}

	//ファイルの説明を読み込み
	if (file_exists(FREO_FILE_DIR . 'media_memos/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/' . $media_comics[0][0]['file'] . '.txt')) {
		$memo = file_get_contents(FREO_FILE_DIR . 'media_memos/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/' . $media_comics[0][0]['file'] . '.txt');

		if ($memo === false) {
			freo_error('ファイル ' . FREO_FILE_DIR . 'media_memos/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/' . $media_comics[0][0]['file'] . '.txt' . ' を読み込めません。');
		}
	} else {
		$memo = null;
	}

	//ファイル情報を取得
	list($file_width, $file_height) = freo_file(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/' . $media_comics[0][0]['file']);

	$first    = 0;
	$last     = $count - 1;
	$all      = $id;
	$from     = $first;
	$to       = $last;
	$end      = $to + 1;
	$previous = $first;
	$next     = $first + 1;

	if ($_GET['end'] == 'on') {
		if ($_GET['columns'] == 2) {
			if ($last % 2) {
				$to = floor($last / 2) + 1;

				if ($cover) {
					$to += 1;
				}
			} else {
				$to = floor($last / 2) + 1;
			}

			$last = floor($last / 2) + 1;
			$end  = $to;

			if ($_GET['page'] > 0) {
				$previous = $_GET['page'] - 1;
			} else {
				$previous = 0;
			}

			if ($_GET['page'] < $to) {
				$next = $_GET['page'] + 1;
			} else {
				$next = $to;
			}
		} else {
			if ($last % 2) {
				$to = $last + 1;

				if ($cover) {
				}
			} else {
				$to = $last + 1;
			}

			if ($_GET['page'] > 0) {
				$previous = $_GET['page'] - 1;
			} else {
				$previous = 0;
			}

			if ($_GET['page'] < $to) {
				$next = $_GET['page'] + 1;
			} else {
				$next = $to;
			}
		}
	} else {
		if ($_GET['columns'] == 2) {
			if ($last % 2) {
				$to = floor($last / 2);

				if ($cover) {
					$to += 1;
				}
			} else {
				$to = floor($last / 2);
			}

			$last = floor($last / 2) + 1;
			$end  = $to;

			if ($_GET['page'] > 0) {
				$previous = $_GET['page'] - 1;
			} else {
				$previous = 0;
			}

			if ($_GET['page'] < $to) {
				$next = $_GET['page'] + 1;
			} else {
				$next = $to;
			}
		} else {
			if ($last % 2) {
				if ($cover) {
				}
			} else {
			}

			if ($_GET['page'] > 0) {
				$previous = $_GET['page'] - 1;
			} else {
				$previous = 0;
			}

			if ($_GET['page'] < $last) {
				$next = $_GET['page'] + 1;
			} else {
				$next = $last;
			}
		}
	}

	//ページ情報を取得
	$media_comic_pages = array();
	$show              = null;

	for ($i = $first; $i <= $last; $i++) {
		if ($cover and $i <= $from) {
			continue;
		}

		if ($_GET['columns'] == 2) {
			if ($i >= $last) {
				break;;
			}

			if ($cover) {
				$page = $i - 1;
			} else {
				$page = $i;
			}

			if ($page == $last - 1 and $last % 2 == 0) {
				$label = ($page * 2 + 1);
			} else {
				$label = ($page * 2 + 1) . '-' . ($page * 2 + 2);
			}
		} else {
			if ($cover) {
				$page = $i - 1;
			} else {
				$page = $i;
			}

			$label = $page + 1;
		}

		if ($i == $_GET['page']) {
			$show = $label;
		}

		$media_comic_pages[] = array(
			'id'    => $i,
			'label' => $label
		);
	}

	//戻り先を取得
	if (!isset($_SESSION['plugin']['media_comic']['referer'])) {
		$_SESSION['plugin']['media_comic']['referer'] = $freo->core['http_file'];
	}
	if (isset($_SERVER['HTTP_REFERER']) and !preg_match('/' . preg_quote('media_comic/' . $_GET['comic'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
		$_SESSION['plugin']['media_comic']['referer'] = $_SERVER['HTTP_REFERER'];
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                          => freo_token('create'),
		'plugin_media_comics'            => $media_comics,
		'plugin_media_comic_pages'       => $media_comic_pages,
		'plugin_media_comic_dir'         => FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_MEDIA_COMIC_DIR . $_GET['comic'] . '/',
		'plugin_media_comic_cover'       => $cover,
		'plugin_media_comic_memo'        => $memo,
		'plugin_media_comic_first'       => $first,
		'plugin_media_comic_last'        => $last,
		'plugin_media_comic_all'         => $all,
		'plugin_media_comic_from'        => $from,
		'plugin_media_comic_to'          => $to,
		'plugin_media_comic_end'         => $end,
		'plugin_media_comic_previous'    => $previous,
		'plugin_media_comic_next'        => $next,
		'plugin_media_comic_show'        => $show,
		'plugin_media_comic_width'       => $file_width,
		'plugin_media_comic_height'      => $file_height,
		'plugin_media_comic_referer'     => $_SESSION['plugin']['media_comic']['referer'],
		'plugin_media_comic_restriction' => $restrict_flag
	));

	return;
}

?>
