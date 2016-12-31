<?php
use Lark\core\Loader;
use Lark\core\Dispatcher;

/**
 * 控制器类
 *
 * 框架入口类，初始化一些基本配置，实现路由分发
 *
 * @category    Lark
 * @package     Lark_bootstrap
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: controller.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
require_once("lark/config/init.php");
class Lark_Controller
{
    /**
     * 单例模式
     *
     * @var Lark_Controller
     */
    static protected $_instance = null;

    private function __construct()
    {
        //初始化自动加载类
        require_once(__CORE__.'loader.php');
        Loader::setAutoLoad();

        //初始化公共函数
        $file_arr = array('functions.php');
        foreach($file_arr as $val){
            require_once(__COMMON__.$val);
        }

        //初始化应用配置文件
        $config_flag = false;
        loadConfig(__APP_CONFIG__.'config.php');

        //初始化目录结构
        if(!defined('INIT_DIRECTORY') || (defined('INIT_DIRECTORY') && INIT_DIRECTORY)){
            $this->initDirectoryStructure();
        }

        if($config_flag === false){
            loadConfig(__APP_CONFIG__.'config.php');
        }

        //初始化语言包
        //$this->initLanguage();
    }

    /**
     * 实例化本程序
     *
     * @param  $args = func_get_args();
     * @return object of this class
     */
    static public function getInstance()
    {
        if(!isset(self::$_instance)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    //初始化语言包
    private function initLanguage()
    {
        $config = Config::getInstance();
        $config->initLanguage();
    }

    //初始化目录结构
    private function initDirectoryStructure()
    {
        Init::getInstance();
    }


    public function run()
    {
        $dispatcher = Dispatcher::getInstance();
        $dispatcher -> dispatch();
    }
}


