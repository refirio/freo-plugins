<?php

/*********************************************************************

 直接リンク防止プラグイン (2010/05/21)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_protect()
{
	global $freo;

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
