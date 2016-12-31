<?php
namespace Lark\core;

/**
 * 初始化目录结构
 *
 * 初始化基础目录结构和文件，配置文件可配置是否这样做
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: init.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Init
{
    /**
     * 单例
     *
     */
    static protected $_instance = null;

    /**
     * 实例化本程序
     *
     * @param  $args = func_get_args();
     * @return object of this class
     */
    static public function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self(func_get_args());
        }
        return self::$_instance;
    }

    /**
     * 构造函数
     *
     * @param $params
     */
    protected function __construct($params=array())
    {
        $this->createDirectoryStructure();
        $this->createDirectoryFiles();
    }

    //初始化默认目录
    private function createDirectoryStructure($defaultDirectory=array(), $pwd='')
    {
        if(is_writable(__ROOT__)){
            if(empty($defaultDirectory) && !$pwd){
                $pwd = __ROOT__;
                $defaultDirectory = require_once(__CONFIG__.'init/directory.php');
            }
            foreach($defaultDirectory as $key => $val){
                if(is_array($val)){
                    $directory = $pwd.$key.'/';
                    mk_dir($directory);
                    $this->createDirectoryStructure($val, $directory);
                }else{
                    $directory = $pwd.$val;
                    mk_dir($directory);
                }
            }
        }else{
            exit('目录 “'.__ROOT__.'” 没有写权限！');
        }
    }

    //初始化默认控制器
    private function createDirectoryFiles()
    {
        $defaultApp = defined('DEFAULT_APP') ? DEFAULT_APP : 'demo';
        $defaultConfig            = require_once(__CONFIG__.'init/config.php');
        $defaultConfigDb          = require_once(__CONFIG__.'init/config.db.php');
        $defaultCommonController  = require_once(__CONFIG__.'init/common.controller.php');
        $defaultDefaultController = require_once(__CONFIG__.'init/index.controller.php');

        $config     = __APP_CONFIG__.'config.php';
        $configDb   = __APP_CONFIG__.'db.php';
        $controller = __APP__.'controller.php';
        $default    = __APP__.$defaultApp.'/index.php';
        if(!is_file($config)){
            file_put_contents($config    , $defaultConfig);
            // require_once($config);
        }
        if(!is_file($configDb))
            file_put_contents($configDb  , $defaultConfigDb);
        if(!is_file($controller))
            file_put_contents($controller, $defaultCommonController);
        if(!is_file($default))
            file_put_contents($default   , $defaultDefaultController);
    }

}
