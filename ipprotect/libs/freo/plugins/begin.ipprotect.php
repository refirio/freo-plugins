<?php

/*********************************************************************

 IP制限プラグイン (2011/11/28)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_ipprotect()
{
	global $freo;

	if ($_REQUEST['freo']['mode'] == 'admin') {
		$flag = false;

		foreach (explode(',', FREO_PLUGIN_IPPROTECT_ADDRESSES) as $address) {
			if ($_SERVER['REMOTE_ADDR'] == $address) {
				$flag = true;

				break;
			}
		}

		if ($flag == false) {
			exit('Forbidden');
		}
	}

	return;
}

?>
