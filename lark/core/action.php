<?php
namespace Lark\core;

/**
 * 应用层的接口文件
 *
 * 初始化应用程序层插件系列
 * 根据配置参数，提供实例化视图的操作
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: action.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Action
{
    /**
     * 视图对象
     *
     * @var Lark_View
     */
    protected $_viewer  = null;
    protected $_request = null;

    protected $_action;
    protected $_controller;
    protected $_application;

    /**
     * 构造函数
     *
     * @param
     */
    public function __construct($params=array())
    {
        $this->_action      = __ACTION_NAME__;
        $this->_controller  = __CONTROLLER_NAME__;
        $this->_application = __APP_NAME__;

        $GLOBALS['ACTION']      = $this->_action;
        $GLOBALS['CONTROLLER']  = $this->_controller;
        $GLOBALS['APPLICATION'] = $this->_application;
    }

    /**
     * 代理视图对象的模板赋值
     *
     * @param string $template
     */
    public function assign($name, $val)
    {
        $this->_getViewer()->$name = $val;
    }

    /**
     * 代理视图对象的渲染方法
     *
     * @param string $template
     * @param string $tplStyle
     */
    public function display($template='', $tplStyle='')
    {
        $this->_render($template, $tplStyle);
        $this->_getViewer()->display();
    }

    /**
     * 代理视图对象的渲染方法
     *
     * @param string $template
     * @param string $tplStyle
     * @return string
     */
    public function render($template='', $tplStyle='')
    {
        $this->_render($template, $tplStyle);
        return $this->_getViewer()->render();
    }

    /**
     * 启用或禁用layout
     *
     * @param $need
     */
    public function needLayout($need=true)
    {
        $this->_getViewer()->setLayout($need);
    }

    /**
     * 设置layout模板所在目录
     *
     * @param $directory
     */
    public function setLayoutPath($directory)
    {
        $this->_getViewer()->setLayoutPath($directory);
    }

    /**
     * 获取POST数据
     *
     * @param $key  可以为空，为空返回所有POST数据
     * @param $type 返回数据类型
     * @return string|array
     */
    public function post($key='', $type='')
    {
        if($key){
            $res = $this->_getRequest()->getPost($key);
            if($type) {
                $res = convert_data_type($res, $type);
            }
        }else{
            $res = $this->_getRequest()->getPost();
        }
        return $res;
    }

    /**
     * 获取GET数据
     *
     * @param $key  可以为空，为空返回所有GET数据
     * @param $type 返回数据类型
     * @return string|array
     */
    public function get($key='', $type='')
    {
        if($key){
            $res = $this->_getRequest()->getQuery($key);
            if($type) {
                $res = convert_data_type($res, $type);
            }
        }else{
            $res = $this->_getRequest()->getQuery();
        }
        return $res;
    }

    /**
     * 获取REQUEST数据
     *
     * @param $key  可以为空，为空返回所ß有REQUEST数据
     * @param $type 返回数据类型
     * @return string|array
     */
    public function getParam($key='', $type='')
    {
        if($key){
            $res = $this->_getRequest()->getParam($key);
            if($type) {
                $res = convert_data_type($res, $type);
            }
        }else{
            $res = $this->_getRequest()->getParam();
        }
        return $res;
    }

    /**
     * 判断是否Ajax请求
     *
     * @return boolean
     */
    public function isAjax()
    {
        return $this->_getRequest()->isXmlHttpRequest();
    }

    /**
     * 加载应用程序级的model
     *
     * model的默认命名就就是$modelName.php
     *
     * @param string $modelName
     * @return Lark_Model
     */
    public function loadAppModel($modelName, $application='', $param=array(), $dbConfig=array())
    {
        return Loader::loadAppModel($modelName, $application, $param, $dbConfig);
    }

    /**
     * 加载Model
     *
     * @param string $modelName
     * @param array  $dbconfig
     * @param string $application
     * @return Lark_Model
     */
    public function loadModel($modelName, $application='', $param=array(), $dbConfig=array())
    {
        return Loader::loadModel($modelName, $application, $param, $dbConfig);
    }

    /**
     * 加载插件
     *
     * @param string $pluginName
     * @return Lark_Model
     */
    public function loadPlugin($pluginName, $param=array())
    {
        return Loader::loadPlugin($pluginName, $param);
    }

    /**
     * 获得渲染器
     *
     */
    protected function _getViewer()
    {
        if (is_null($this->_viewer)) {
            $this->_viewer = View::getInstance();
        }
        return $this->_viewer;
    }

    protected function _getRequest()
    {
        if(is_null($this->_request)) {
            $this->_request = Request::getInstance();
        }
        return $this->_request;
    }

    private function _render($template='', $tplStyle='')
    {
        $template = $template ? $template : __ACTION_NAME__;
        $tplStyle = $tplStyle ? $tplStyle : (C('theme') ? C('theme') : DEFAULT_THEME);
        $base_dir = _VIEW_PATH_ . $tplStyle . __SEPARATOR__;

        $this->_getViewer()->setBaseDir($base_dir);
        $this->_getViewer()->setTemplate($template);
    }

}
