<?php

/*********************************************************************

 ファイル管理プラグイン | 設定ファイル (2012/12/03)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//プラグインの名前
define('FREO_PLUGIN_FILEMANAGER_NAME', 'ファイル管理');

//プラグインのバージョン
define('FREO_PLUGIN_FILEMANAGER_VERSION', '1.0.0');

//管理ページの設定
define('FREO_PLUGIN_FILEMANAGER_ADMIN', 'filemanager/admin');

//pageファイルの読み込み設定
define('FREO_PLUGIN_FILEMANAGER_LOAD_PAGE', 'filemanager');

//管理対象ディレクトリ
define('FREO_PLUGIN_FILEMANAGER_DIR', './');

//管理対象除外ディレクトリ
define('FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR', 'database/,libs/PEAR/,libs/smarty/,tiny_mce/');

//パスとして使用を許可する文字
define('FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER', '^[\w\!\#\$\%\&\'\(\)\-\^\@\[\;\]\,\.\=\~\`\{\+\}\_\/]+$');

?>
