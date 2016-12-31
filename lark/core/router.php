<?php
namespace Lark\core;

/**
 * 路由器
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: router.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Router
{
    /**
     * 单例
     *
     * @var Lark_Router
     */
    static protected $_instance = null;

    /**
     * 获得Request对象
     *
     * @var string
     */
    protected $_request = '';

    /**
     * 用户路由规则集正则
     *
     * @var array
     */
    protected $_userRouters = array();

    /**
     * 默认路由规则集正则
     *
     * @var array
     */
    protected $_defaultRouters = array();

    /**
     * 配对后的结果，用于获得最终路由的
     *
     * @var array
     */
    protected $_matches = array();

    /**
     * 运行路由后的应用程序名
     *
     * @var string
     */
    protected $_appName = '';

    /**
     * 运行路由后的动作名
     *
     * @var string
     */
    protected $_actionName = '';

    /**
     * 运行路由后的控制器名
     *
     * @var string
     */
    protected $_controllerName = '';

    /**
     * 所有的请求参数
     *
     * @var array
     */
    protected $_params = array();

    /**
     * 路由规则配置对象
     *
     * @var Lark_Config
     */
    protected $_router_config = null;

    /**
     * 系统键
     *
     * @var array
     */
    protected $_systemKeys = array('domain', 'app', 'port', 'controller', 'action', 'params');

    /**
     * 实例化本程序
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

    /**
     * 构造函数
     *
     * @param
     */
    protected function __construct($params=array())
    {
        $this->_request = Request::getInstance();

        // 解析网址以生成相应的参数对象
        $this->matchRouter();
        $this->_appName        = $this->_getApp();
        $this->_controllerName = $this->_getController();
        $this->_actionName     = $this->_getAction();
        $this->_params         = $this->_getParams();
    }

    /**
     * 获得路由配置信息
     *
     * @return
     */
    public function getConfig()
    {
        if (!$this->_router_config){
            require_once(__CONFIG__."router.php");
            $this->_router_config = $router_config;
        }

        return $this->_router_config;
    }

    /**
     * 获得应用程序名称，应用程序对应一个具体的项目
     * 默认为default
     *
     * @return string
     */
    public function getApp()
    {
        return $this->_appName;
    }

    /**
     * 获得动作名，动作是控制器的具体执行函数
     * 默认为index
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_actionName;
    }

    /**
     * 获得控制器名字
     * 默认为controller
     *
     * @return string
     */
    public function getController()
    {
        return $this->_controllerName;
    }

    /**
     * 设置应用程序名，为重新分发
     *
     * @param string $appName
     */
    public function setApp($appName)
    {
        $this->_appName = $appName;
    }

    /**
     * 设置动作名，为重新分发
     *
     * @param string $actionName
     */
    public function setAction($actionName)
    {
        $this->_actionName = $actionName;
    }

    /**
     * 设置控制器名字
     *
     * @param string $controllerName
     */
    public function setController($controllerName)
    {
        $this->_controllerName = $controllerName;
    }

    /**
     * 获得应用程序名称，应用程序对应一个具体的项目
     *
     * @return string
     */
    protected function _getApp()
    {
        if (is_array($this->_matches) && !empty($this->_matches['app']) && $this->_matches['app'] != 'index.php') {
            return $this->_matches['app'];
        } else {
            return $this->getBaseApp();
        }
    }

    /**
     * 获得动作名，动作是控制器的具体执行函数
     * 默认为index
     *
     * @return string
     */
    protected function _getAction()
    {
        if (is_array($this->_matches) && !empty($this->_matches['action'])) {
            return $this->_matches['action'];
        } else {
            return $this->getBaseAction();
        }
    }

    /**
     * 获得控制器名字
     * 默认为controller
     *
     * @return string
     */
    protected function _getController()
    {
        if (is_array($this->_matches) && isset($this->_matches['controller'])) {
            return $this->_matches['controller'];
        } else {
            return $this->getBaseController();
        }
    }

    /**
     * 获得url参数
     *
     * @return array
     */
    protected function _getParams()
    {
        if (is_array($this->_matches) && !empty($this->_matches['params'])) {
            return $this->_matches['params'];
        } else {
            return array();
        }
    }

    /**
     * 获得基本的路由设置
     *
     * @param string $section 哪一段
     * @return mixed
     */
    public function getBase($section='')
    {

        $config = $this->getConfig();
        if (isset($config['base'])) {
            if ($section && isset($config['base'][$section])) {
                return $config['base'][$section];
            } else {
                return $config['base'];
            }
        } else {
            return null;
        }
    }

    /**
     * 获得基本域
     *
     * @return string
     */
    public function getBaseDomain()
    {
        $basedomain = $this->getBase('basedomain');
        if (!$basedomain) {
            $domain = Lark_Request::get('HTTP_HOST');
            $domainSections = explode('.', $domain);
            $domainparts    = count($domainSections);
            $basedomain     = $domainSections[$domainparts-2] . $domainSections[$domainparts-1];
        }
        return $basedomain;
    }

    /**
     * 获得基本端口
     *
     * @return string
     */
    public function getBasePort()
    {
        $baseport = $this->getBase('baseport');
        $port = isset($baseport) ? $baseport : '';
        return $port;
    }

    /**
     * 获得基本应用程序名
     *
     * @return string
     */
    public function getBaseApp()
    {
        $baseapp = $this->getBase('baseapp');
        if (!$baseapp) {
            $baseapp = 'demo';
        }
        return $baseapp;
    }

    /**
     * 获得基本动作名
     *
     * @return string
     */
    public function getBaseAction()
    {
        $action = $this->getBase('baseaction');
        if (!$action) {
            $action = 'index';
        }
        return $action;
    }

    /**
     * 获得基本控制器名
     *
     * @return string
     */
    public function getBaseController()
    {
        $controller = $this->getBase('basecontroller');
        if (!$controller) {
            $controller = 'index';
        }
        return $controller;
    }

    /**
     * 获得基本参数名，默认为id
     *
     * @return string
     */
    public function getBaseParam()
    {
        $param = $this->getBase('baseparam');
        return isset($param) ? $param : 'id';
    }

    /**
     * 获得请求对象
     *
     * @return Lark_Request
     */
    public function getRequest()
    {
        if (!$this->_request) {
            throw new Exception('Please run Router::run() first! ');
        }
        return $this->_request;
    }

    /**
     * 获得路由器
     *
     * @param  $section 哪一段
     * @return mixed
     */
    public function getRouters($section='')
    {
        $config = $this->getConfig();
        if (isset($config['router'])) {
            if ($section && isset($config['router'][$section])) {
                return $config['router'][$section];
            } else {
                return $config['router']['defaultrule'];
            }
        } else {
            return array();
        }
    }

    /**
     * 1.把路由器转换成正则
     *  . 先把统一在每一段的:后加上默认规则，如app由getBaseApp()获得
     *  . 定义好alpha, char, mixed的正则表达式
     *  . 参数由数据类型nubmer, char, all 补上如果没有指定，默认用char补上
     *  . 定义特殊字符"/" , "_" , "-", "."  作为网址允许的连接字符串
     *  . 正则替换，把[]按":"分成前后两段内容，转换成(?<前一段>[后一段])这样的表达式
     * array('action', 'controller', 'params', 'params', 'port', 'domain', 'app')
     * @param string $rule
     * @return string
     */
    public function parseRule($rule)
    {
        // 转义连接符
        $specialChars = array('/', '-', '.');
        $replaceWith  = array('[/]', '[-]', '[.]');
        $rule = str_replace($specialChars, $replaceWith, $rule);

        // 域名
        $baseDomain =  $this->getBaseDomain();
        $rule = str_replace(array('[domain:]'), array('[domain:' . $baseDomain . ']'), $rule);

        // 转换参数规则
        $rule = preg_replace('/\[(\w+):\]/', '[\\1:all]', $rule);
        $paramRegs = array(
                        'alpha' => '\w+',
                        'all'    => '{tag}',
                        'number' => '\d+'
                        );
        foreach ($paramRegs as $key => $paramReg) {
            $rule = preg_replace("/\[(\w+):$key\]/", "[\\1:$paramReg]", $rule);
        }

        // 转换定义的规则
        $rule = preg_replace("/\[(\w+):(.*?)\]/", "(?<\\1>\\2)", $rule);

        // 转义[_]等连接规则
        $rule = preg_replace('/\[(.*?)\]/', '\\\\\1', $rule);
        $rule = str_replace('{tag}', '[^\/\-\?]*?', $rule);

        $result = '/' . $rule . '(?<params>\?.*?)*\/*$/';
        // $result = '/' . $rule . '\/(?<params>.*?)$/';
        return $result;
    }

    /**
     * 根据当前的request对象获得匹配的路由规则
     *
     * 1. 根据base参数把所有的路由规则生成合适的正则表达式
     * 2. 根据当前的网址匹配已设置的路由规则正则
     * 3. 如果匹配成功，把匹配的结果返回，否则抛出404 Error
     *
     * @example
     * rule  : [app:][domain:][port:]/[controller:]/[action:]/[param:]
     * match : www.LarkPHP.com/help/index/aaaa
     * result: app=> www , domain=>larkphp.com, port=>80, controller=>help, action=> index, param=>aaaa
     *
     * @return array|boolean:false
     */
    public function matchRouter()
    {
        // 用户规则正则集
        if (empty($this->_userRouters)) {
            $userRules = (array) $this->getRouters('rule');
            foreach ($userRules as $userRule) {
                $this->_userRouters[] = $this->parseRule($userRule);
            }
        }

        // 默认正则集
        if (empty($this->_defaultRouters)) {
            $defaultRules = (array) $this->getRouters('defaultrule');
            foreach ($defaultRules as $rule) {
                $this->_defaultRouters[] = $this->parseRule($rule);
            }
        }

        // 先用户路由后默认路由
        $request = $this->getRequest();
        $routers = array_merge($this->_userRouters, $this->_defaultRouters);
        $url = $request->getUrl();
        if (substr($url, -1) != '/') $url .= '/';

        // 匹配用户规则，如果配对成功，返回配对结果
        foreach ($routers as $router) {
            if (preg_match($router, $url, $matches)) {
                $this->_matches = $matches ;
                return $matches;
            }
        }
        return false;
    }

    /**
     * 生成URL
     *
     * @param string $action       动作名
     * @param string $controller   控制器名，默认与当前
     * @param string $controller   控制器名，可选，默认与当前控制器同名
     * @param string $application  模块名  ，可选，默认与当前模块名相同
     * @param array $params        传递的参数，参数将以GET方法传递
     * @return string
     */
    public function buildUrl($action, $controller='', $application='', $params=array())
    {
        if ('' == $controller) {
            $controller = $this->getController();
        }

        if ('' == $application) {
            $application = $this->getApp();
        }

        $userRules = array_merge($userRules = $this->getRouters('rule'), $this->getRouters('defaultrule'));
        foreach ($userRules as $rule) {
            if (preg_match('/action/',$rule)
                    && preg_match('/controller/',$rule)
                    && preg_match('/app/',$rule)) {
                $rule = substr($rule, strpos($rule, '/'));
                $url = str_replace(array('[app:]', '[controller:]', '[action:]'),
                                   array($application, $controller, $action),
                                   $rule);
                if (!empty($params)) {
                    $url .= '?' . http_build_query($params);
                }
                return __DOMAIN__ .$url;
            }
        }
        return '';
    }

}
