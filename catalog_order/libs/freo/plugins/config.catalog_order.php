<?php

/*********************************************************************

 注文管理プラグイン | 設定ファイル (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//プラグインの名前
define('FREO_PLUGIN_CATALOG_ORDER_NAME', '注文管理');

//プラグインのバージョン
define('FREO_PLUGIN_CATALOG_ORDER_VERSION', '1.0.0');

//管理ページの設定
define('FREO_PLUGIN_CATALOG_ORDER_ADMIN', 'catalog_order/admin');

//beginファイルの読み込み設定
define('FREO_PLUGIN_CATALOG_ORDER_LOAD_BEGIN', 'catalog/cart,catalog/cart_putin,catalog/order_send');

//pageファイルの読み込み設定
define('FREO_PLUGIN_CATALOG_ORDER_LOAD_PAGE', 'catalog_order');

//endファイルの読み込み設定
define('FREO_PLUGIN_CATALOG_ORDER_LOAD_END', 'admin/user_delete,catalog/cart,catalog/cart_putin,catalog/cart_update,catalog/cart_delete,catalog/cart_clear,catalog/order_send');

//displayファイルの読み込み設定
define('FREO_PLUGIN_CATALOG_ORDER_LOAD_DISPLAY', 'catalog/order');

?>
