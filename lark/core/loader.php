<?php
namespace Lark\core;
use Lark\plugin\LarkException;

/**
 * 自动加载程序
 *
 * 根据规则加载相关的类
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: load.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Loader
{
    /**
     * 数据关系模型
     *
     * @var Lark_Model
     */
    static public $_model = array();

    /**
     * 插件类
     *
     * @var Lark_Plugin
     */
    static public $_plugins = array();

    /**
     * 插件类
     *
     * @var Lark_Plugin
     */
    static public $_controller = array();

    /**
     * 自动加载类
     *
     * @param  string $className - The full class name of a Lark component.
     * @return void
     * @throws Exception
     */
    public static function loadClass($className)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return;
        }

        $file = strtolower($className . '.php');
        $file = str_replace('\\', '/', $file);
// echo $file;
// echo '<br />';
// echo '<br />';
        require_once($file);

        if (!class_exists($className, false) && !interface_exists($className, false)) {
            if (__DEBUG__) {
                throw new LarkException("文件 \"$file\" 不存在或类 \"$class\" 没有找到");
            }
            return false;

        } else {
            return true;
        }
    }

    /**
     * 加载应用程序级model
     *
     * model的默认命名就就是$model.php
     *
     * @param  string $modelName
     * @return Lark_Model
     */
    static public function loadAppModel($modelName, $param=array(), $dbConfig=array())
    {
        $modelFile = strtolower($modelName) . '.php';
        $modelPath = __APP__ . '_model' . __SEPARATOR__ . $modelFile;

        if (!file_exists($modelPath)) {
            return false;
        }

        // $model = ucwords($modelName);
        $hash = md5($modelName . serialize($dbConfig));

        if (empty(self::$_model[$hash])) {
            require_once($modelPath);
            $name = convert_to_small_hump($modelName);
            self::$_model[$hash] = new $name($name, $param, $dbConfig);
        }
        return  self::$_model[$hash];
    }

    /**
     * 返回模块级model
     *
     * @param  string $modelName
     * @return Lark_Model
     */
    static public function loadModel($modelName, $application='', $param=array(), $dbConfig=array())
    {
        $modelFile = strtolower($modelName) . '.php';

        if ($application == '') {
            $application = __APP_NAME__;
        }

        $modelDirectory = __APP__ . 'modules/' . $application . __SEPARATOR__ . 'model' . __SEPARATOR__;
        $modelPath = $modelDirectory . $modelFile;
        $hash = md5($application . $modelName . serialize($dbConfig));

        if (empty(self::$_model[$hash])) {
            if (file_exists($modelPath)) {
                require_once($modelPath);
                $name = convert_to_small_hump($modelName);
                self::$_model[$hash] = new $name($name, $param, $dbConfig);
            }else{
                //尝试实例化应用级MODEL
                if ($appModel = self::loadAppModel($modelName, $param, $dbConfig)) {
                    return $appModel;
                }else{
                    return false;
                }
            }
        }
        return self::$_model[$hash];
    }

    /**
     * 返回控制器对象
     *
     * @param  string $controllerName
     * @param  string $application
     * @param  string $param
     * @return
     */
    static public function loadController($controllerName, $application='', $param=array())
    {
        $modelFile = $controllerName . '.php';

        if ($application == '') {
            $application = __APP_NAME__;
        }

        $controllerDirectory = __APP__ . $application . __SEPARATOR__;
        $controllerPath = $controllerDirectory . $modelFile;
        $hash = md5($application . $controllerName);

        if (empty(self::$_controller[$hash])) {
            if (file_exists($controllerPath)) {
                require_once($controllerPath);
                $controllerName = convert_to_small_hump($controllerName);
                $name = $controllerName.'Controller';
                self::$_controller[$hash] = new $name();
            }
        }
        return self::$_controller[$hash];
    }

    /**
     * 加载插件
     *
     * 找不到应用级插件时，会自动去寻找框架级插件
     *
     * @param string $pluginName
     * @param array $param
     */
    static public function loadPlugin($pluginName, $param=array())
    {
        $pluginFile = strtolower($pluginName) . '.php';
        $pluginDirectory = _PLUGIN_;
        $pluginPath = $pluginDirectory . $pluginFile;
        $hash = md5($pluginPath);

        if (empty(self::$_plugins[$hash])) {
            if (file_exists($pluginPath)) {
                require_once($pluginPath);

                $tmp  = explode('_', $pluginName);
                $name = 'Plugin_';
                foreach ($tmp as $val) {
                    $name .= ucfirst($val);
                }

                self::$_plugins[$hash] =  new $name($param);
            }else{
                //尝试实例化框架级插件
                if ($plugin = self::loadFwPlugin($pluginName, $param)) {
                    return $plugin;
                }else{
                    return false;
                }
            }
        }
        return self::$_plugins[$hash];
    }

    /**
     * 加载框架级插件
     *
     * @param string $pluginName
     * @param array $param
     */
    static public function loadFwPlugin($pluginName, $param=array())
    {
        $pluginFile = strtolower($pluginName) . '.php';
        $pluginDirectory = __PLUGIN__  . __SEPARATOR__;
        $pluginPath = $pluginDirectory . $pluginFile;
        $hash = md5($pluginPath);

        if (empty(self::$_plugins[$hash])) {
            if (file_exists($pluginPath)) {
                require_once($pluginPath);

                $tmp  = explode('_', $pluginName);
                $name = 'Plugin_';
                foreach ($tmp as $val) {
                    $name .= ucfirst($val);
                }
                self::$_plugins[$hash] =  new $name($param);
            }else{
                return false;
            }
        }
        return self::$_plugins[$hash];
    }

    public static function autoLoad($className)
    {
        try{
            self::loadClass($className);
            return $className;
        }catch(LarkException $e){
            return false;
        }
    }

    /**
     * 自动注册 {@link autoload()} 用 spl_autoload() 方法自动实现
     *
     * @param  boolean $enable 根据配置选项打开或关闭自动加载
     * @return void
     * @throws Exception 如果spl_autoload()不存在，抛出本异常
     */
    public static function setAutoLoad($enable=true)
    {
        if (!$enable) {
            return ;
        }

        if (!function_exists('spl_autoload_register')) {
            exit('spl_autoload 不存在，可能是SPL库没有安装');
        }

        spl_autoload_register(array(__CLASS__, 'autoLoad'));
    }
}

?>
