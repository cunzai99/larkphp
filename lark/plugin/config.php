<?php
namespace Lark\plugin;

/**
 * 配制文件操作类
 *
 * @category    Lark
 * @package     Lark_Plugin
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: config.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Config
{
    /**
     * 单例
     *
     * @var Lark_Config
     */
    static protected $_instance = null;

    /**
     * 配置数组
     *
     * @var Lark_Config
     */
    static protected $_configArr = array();

    /**
     * 语言包数组
     *
     * @var Lark_Config
     */
    static protected $_languageArr = array();

    /**
     * 实例化本程序
     *
     * @param  $args = func_get_args();
     * @return object of this class
     */
    static public function getInstance()
    {
        if (!isset(self::$_instance)) {

            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function __construct(){}

    /**
     * 设置配置
     *
     * @param $key
     * @param $val
     * @return string
     */
    public function setConfig($key, $val)
    {
        $key = trim($key);
        $val = $val;
        if($key){
            self::$_configArr[$key] = $val;
        }
    }

    /**
     * 获取配置
     *
     * @param $key
     * @return string
     */
    public function getConfig($key='')
    {
        $key = trim($key);
        if($key){
            if(isset(self::$_configArr[$key])){
                return self::$_configArr[$key];
            }else{
                return false;
            }
        }else{
            return self::$_configArr;
        }
    }

    /**
     * 读取配置文件
     *
     * @param $file
     * @return
     */
    public function loadConfig($file)
    {
        if(file_exists($file)){
            require_once($file);
            if(isset($config) && is_array($config)){
                foreach ($config as $key => $val) {
                    $this->setConfig($key, $val);
                }
                unset($config);
            }
        }
    }

    /**
     * 设置语言元素
     *
     * @param $key
     * @param $val
     * @return string
     */
    public function setLanguage($key, $val)
    {
        $key = trim($key);
        $val = $val;
        if($key){
            self::$_languageArr[$key] = $val;
        }
    }

    /**
     * 获取语言元素
     *
     * @param $key
     * @return string
     */
    public function getLanguage($key)
    {
        $key = trim($key);
        if($key){
            if(isset(self::$_languageArr[$key])){
                return self::$_languageArr[$key];
            }else{
                return false;
            }
        }else{
            return self::$_languageArr;
        }
    }

    /**
     * 读取语言包文件
     *
     * @param $language
     * @return
     */
    public function initLanguage($language='')
    {
        if(!$language){
            $language = $this->getConfig('default_language');
        }

        if($language){
            $language .= '.php';

            //加载框架级语言包
            $file  = __F_LANG__.$language;
            $this -> _loadLanguage($file);

            //加载应用级语言包
            $file  = __LANG__.$language;
            $this -> _loadLanguage($file);
        }else{

        }
    }

    private function _loadLanguage($file='')
    {
        if($file && file_exists($file)){
            $config = require_once($file);
            foreach ($config as $key => $val) {
                $this->setLanguage($key, $val);
            }
            unset($config);
        }
    }
}



