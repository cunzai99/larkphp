<?php
namespace Lark\core;
use Lark\plugin\Debuger;
use Lark\plugin\Response;
use Lark\plugin\LarkException;

/**
 * 分发器
 *
 * 分析Request请求，查询是否有相关的重写路由
 * 如果有重写路由则由重写路由分解出Controller及Action交给dispatcher
 * Dispatcher根据传送过来的数据调用相关Action及Controller
 *
 * 对应的对象完成后会抛出一个响应，可能是OK，也有可能是强制错误显示等
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: dispatcher.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Dispatcher
{
    /**
     * 单例
     *
     * @var Lark_Dispatcher
     */
    static protected $_instance = null;

    /**
     * 路由器
     *
     * @var Lark_Router
     */
    public $_router = null;

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

    /**
     * 构造函数
     */
    protected function __construct()
    {
        $this->_router = Router::getInstance();
    }

    /**
     * 获得应用程序名字
     *
     * @return string
     */
    public function getAppName()
    {
        return $this->_router->getApp();
    }

    /**
     * 得到控制器名字
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_router->getController();
    }

    /**
     * 获得动作名字
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_router->getAction();
    }

    /**
     * 获得默认的动作名
     *
     * @return string
     */
    public function getDefaultApp()
    {
        return $this->_router->getBaseApp();
    }

    /**
     * 获得默认的控制器名字
     *
     * @return string
     */
    public function getDefaultController()
    {
        return $this->_router->getBaseController();
    }

    /**
     * 获得默认的动作名字
     *
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->_router->getBaseAction();
    }

    /**
     * 分发过程
     *
     * 可供程序直接跳转
     *
     * @param string $appName
     * @param string $controllerName
     * @param string $actionName
     * @param array  $params 查询参数，相当于post 或 get过来的参数值
     */
    public function dispatch($params=array())
    {
        $appName        = $this->getAppName();
        $actionName     = $this->getActionName();
        $controllerName = convert_hump($this->getControllerName());

        // 找到相关程序并执行
        $classPath = __APP__ . 'modules/' . $appName . "/" . $controllerName . ".php";

        //如果不存在，则按默认执行
        if (!file_exists($classPath)) {
            if(!$appName){
                $appName    = $this->getDefaultApp();
            }
            $controllerName = $this->getDefaultController();
            $actionName     = $this->getDefaultAction();
            $classPath      = __APP__ . 'modules/' . $appName . "/" . $controllerName . ".php";

            if (!file_exists($classPath)) {
                throw new LarkException('Error 404, error page not found!', 1);
                // redirect('/', 'Error 404, error page not found!');
            }
        }

        define('__APP_NAME__'       , $appName);
        define('__ACTION_NAME__'    , $actionName);
        define('__CONTROLLER_NAME__', $controllerName);

        require_once($classPath);

        //加载配置文件
        loadConfig(_APP_.$appName.'/config/config.php');

        // 生成控制器、动作名
        $actionFunctionName  = $actionName . 'Action';
        $controllerClassName = convert_to_small_hump($controllerName) . 'Controller';

        // 实例化控制器
        $controller =  new $controllerClassName();
        // if (method_exists($controller, 'execute'))
        //     $controller -> execute();

        if (!method_exists($controller, $actionFunctionName)) {
            exit('Page <strong>[' . $actionFunctionName . ']</strong>
            not found! The reason cause this error may be Method not exist
            in the Controller <strong>[' . $controllerClassName . ']</strong>');
        }

        //执行动作
        $response = $controller->$actionFunctionName();

        //显示调试信息
        if(__DEBUG__){
            $request = Request::getInstance();
            if (!$request->isXmlHttpRequest() && !$request->isFlashRequest()){
                Debuger::showVar();
            }
        }
    }

}
