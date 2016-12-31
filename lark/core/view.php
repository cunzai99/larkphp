<?php
namespace Lark\core;
use Lark\view\Layout;
use Lark\view\Render;

/**
 * 渲染代理
 *
 * 根据渲染配置的环境变量选择演染插件，泻染插件必须要有自己的接口以便统一操作
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: view.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class View
{
    /**
     * 单例
     *
     * @var Lark_View
     */
    static protected $_instance = null;

    /**
     * 渲染引擎
     *
     * @var Lark_View
     */
    static protected $_engine = null;

    /**
     * 是否允许使用$_renderLayout
     *
     * @var boolean
     */
    private $_renderLayout = false;

    /**
     * 布局对象
     *
     * @var Layout
     */
    private $_layouter = null;

    /**
     * 实例化本程序
     * @param $args = func_get_args();
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
     * 代理某个具体渲染器的方法
     *
     * @param params
     */
    protected function __construct($params=array())
    {
        $engine = defined('VIEWER_ENGINE') ? VIEWER_ENGINE : 'Render';
        if($engine){
            if (!self::$_engine) {
                self::$_engine = new Render();
            }
        }else{

        }
    }

    /**
     * 执行渲染过程
     *
     */
    public function display()
    {
        $content = $this->_render();
        echo $content;
    }

    /**
     * 执行渲染过程
     *
     * @return string
     */
    public function render()
    {
        $content = $this->_render();
        return $content;
    }

    public function _render()
    {
        // 渲染模板
        $engine = $this->getEngine();
        $result = $engine->render();

        if($engine->getLayout()){
            $layout = Layout::getInstance();
            $result = $layout -> renderLayout($engine, $result);
        }
        return $result;
    }

    /**
     * 获得模板引擎
     * 根据插件环境变量获得调用哪个具体的渲染实例类
     * 默认Lark_View
     *
     * @return string
     */
    public function getEngine()
    {
        if (!self::$_engine) {
            self::$_engine = new $engine();
        }
        return self::$_engine;
    }

    /**
     * 设置layout目录
     *
     * @param $directory
     */
    public function setLayoutPath($directory)
    {
        $layout = Layout::getInstance();
        $result = $layout -> setLayoutPath($directory);
    }

    /**
     * 为模板加载变量
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        if (isset($this->$key)){
            $this->$key = $value;
        }else{
            self::$_engine->assign($key, $value);
        }
    }

    /**
     * 调用代理的类的相应方法
     * 如$this->display()可能调用的是smarty->display();
     *
     * @param string  $methodName
     * @param array   $arguments
     */
    public function __call($methodName, $arguments)
    {
        if (self::$_engine) {
            return  call_user_func_array( array(self::$_engine, $methodName), $arguments);
        }
    }

}
