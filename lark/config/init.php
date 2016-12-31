<?php
/**
 * 框架配制文件
 *
 * @category    Lark
 * @package     Lark_Config
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: init.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */

header('Content-Type:text/html;charset=utf-8');

//框架相关
session_start();

define('__SEPARATOR__'  , '/');

define('__FW__'         , str_replace('\\', '/', dirname(dirname(dirname(__FILE__))).__SEPARATOR__));
define('_ROOT_'         , str_replace('\\', '/', dirname(dirname(dirname(dirname(__FILE__)))) . __SEPARATOR__), '/');

define('__LARK__'       , __FW__  .'lark'  .__SEPARATOR__);
define('__COMMON__'     , __LARK__.'common'.__SEPARATOR__);
define('__CONFIG__'     , __LARK__.'config'.__SEPARATOR__);
define('__CORE__'       , __LARK__.'core'  .__SEPARATOR__);
define('__TPL__'        , __LARK__.'tpl'   .__SEPARATOR__);
define('__DB__'         , __LARK__.'db'    .__SEPARATOR__);
define('__PLUGIN__'     , __LARK__.'plugin'.__SEPARATOR__);

define('__IS_LARKPHP__' , true);

define('__HTTP_HOST__'  , $_SERVER['HTTP_HOST']);
define('__DOMAIN__'     , $_SERVER['REQUEST_SCHEME'] . '://'. __HTTP_HOST__);
define('__DEBUG__'      , true);
define('__ROOT__'       , dirname(__FW__).__SEPARATOR__);
define('__APP__'        , __ROOT__.'app'.__SEPARATOR__);
define('__APP_CONFIG__' , __APP__.'_config'.__SEPARATOR__);

set_include_path( __FW__ . PATH_SEPARATOR . __PLUGIN__ . PATH_SEPARATOR . get_include_path());

//系统信息
//if(PHP_VERSION < '6.0') {
    ini_set('magic_quotes_runtime', 0);
    define('__MAGIC_QUOTES_GPC__', get_magic_quotes_gpc() ? true : false);
//}

?>
