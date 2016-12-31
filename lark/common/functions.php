<?php
use Lark\plugin\Config;
use Lark\plugin\Debuger;
use Lark\plugin\Response;
use Lark\core\Router;

/**
 * 框架提供的一些基础函数
 *
 * @category    Lark
 * @package     Lark_Common
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: functions.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */

// set_error_handler('exception_error_handler');

/**
 * 自定义错误处理函数
 *
 * @param $errno          异常代码
 * @param $errstr         异常消息内容
 * @param $errfile        抛出异常的文件名
 * @param $errline        抛出异常在该文件中的行号
 * @param $errcontext
 * @todo  第5个参数待确认
 */
function exception_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
    throw new Exception($errstr);
}

/**
 * 文件操作函数
 *
 * @param $name        文件名
 * @param $value       要写入的文件内容
 * @param $path        文件路径
 * @todo  第3个参数待优化
 */
function F($name, $value='', $path=_CACHE_DIR_)
{
    static $_cache = array();
    $path = rtrim($path, '/');
    $filename = $path . __SEPARATOR__ . $name;
    if('' !== $value) {
        if(is_null($value)) {
            // 删除文件
            return unlink($filename);
        }else{
            // 写入数据
            $dir = dirname($filename);
            // 目录不存在则创建
            if(!is_dir($dir)) {
                mk_dir($dir);
            }
            return file_put_contents($filename, $value);
        }
    }else{
        if(isset($_cache[$name])) {
            return $_cache[$name];
        }

        // 获取缓存数据
        if(is_file($filename)) {
            $value = include $filename;
            $_cache[$name] = $value;
        }else{
            $value = false;
        }
        return $value;
    }
}

/**
 * 配置文件操作函数
 *
 * @param $key
 * @param $val
 */
function C($key='', $val='')
{
    $config = Config::getInstance();
    if($key && $val){
        $config->setConfig($key, $val);
        return true;
    }else if($key && !$val){
        return $config->getConfig($key);
    }else{
        return $config->getConfig();
    }
}

/**
 * 读取配置文件
 *
 * @param $file
 */
function loadConfig($file)
{
    $config = Config::getInstance();
    $config->loadConfig($file);
}

/**
 * 循环创建目录
 *
 * @param  string $dir
 * @param  int $mode
 * @return boolean
 */
function mk_dir($dir, $mode = 0755)
{
    if(!$dir){
        return false;
    }

    if (is_dir($dir)){
        return true;
    }

    return mkdir($dir, $mode, true);
}

/**
 * 控制器名称转换(转小驼峰)
 *
 * @param  string $name
 * @return string
 */
function convert_to_small_hump($name)
{
    $tmp  = explode('_', $name);
    $name = '';
    foreach ($tmp as $val) {
        $name .= ucfirst(strtolower($val));
    }

    //小驼峰
    if ( function_exists('lcfirst') === false){
        $name = strtolower(substr($name, 0, 1)).substr($name, 1);
    }else{
        $name = lcfirst($name);
    }
    return $name;
}

/**
 * 控制器名称转换(分割驼峰)
 *
 * @param  string $name
 * @return string
 */
function convert_hump($name)
{
    $tmp  = preg_split("/(?=[A-Z])/", $name);
    $name = trim(strtolower(implode('_', $tmp)), '_');

    return $name;
}

/**
 * 数据类型转换
 *
 * $content 要转换的数据
 * $type    要转换的数据类型   int/str
 */
function convert_data_type($content, $type)
{
    if(!$content || !$type) {
        return false;
    }

    switch ($type) {
        case 'int':
            $content = (int)$content;
            break;
        case 'str':
            $content = strval($content);
            break;
        default:
            # code...
            break;
    }

    return $content;
}

/**
 * 多语言替换
 *
 * @param $key
 */
function get_language($key='')
{
    if(!$key){
        return '';
    }else{
        $config   = Config::getInstance();
        $language = $config->getLanguage($key);
        return $language;
    }
}

/**
 * 加载Controller
 *
 * @param string $controllerName
 * @param array  $application
 * @param string $param
 * @return Lark_Model
 */
function import($controllerName, $application='', $param=array())
{
    return Loader::loadController($controllerName, $application, $param);
}

/**
 * 动态生成url
 *
 * @param string $action       动作名
 * @param string $controller   控制器名，默认与当前
 * @param string $controller   控制器名，可选，默认与当前控制器同名
 * @param string $application  模块名  ，可选，默认与当前模块名相同
 * @param array $params        传递的参数，参数将以GET方法传递
 *
 * @return string
 */
function build_url($action, $controller='', $application='', $param=array())
{
    if(!$action) return '';
    $router = Router::getInstance();
    $url = $router->buildUrl($action, $controller, $application, $param);

    return $url;
}

/**
 * 获取客户端真实IP
 *
 */
function get_real_ip()
{
    $ip_address = '';
    if(isset($_SERVER)){
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else if(isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip_address = $_SERVER["HTTP_CLIENT_IP"];
        }else{
            $ip_address = $_SERVER["REMOTE_ADDR"];
        }
    }else{
        if(getenv("HTTP_X_FORWARDED_FOR")){
            $ip_address = getenv("HTTP_X_FORWARDED_FOR");
        }else if(getenv("HTTP_CLIENT_IP")) {
            $ip_address = getenv("HTTP_CLIENT_IP");
        }else{
            $ip_address = getenv("REMOTE_ADDR");
        }
    }
    return $ip_address;
}

/**
 * 通过curl访问URL
 *
 * @param $remote_server 要访问的URL
 * @return $data         要传的参数
 */
function curl_request($remote_server, $data='', $type='get') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($type == 'post'){
        curl_setopt($ch, CURLOPT_POST, true);
        $data = http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $return_data = curl_exec($ch);
    curl_close($ch);

    return $return_data;
}

/**
 * 调试信息
 *
 * @param  $var     调试变量
 * @param  display  是否在浏览器中显示，为false时，信息将输出到X－LarkPHP-Data中
 * @return boolean
 */
function debug($var, $display=true)
{
    Debuger::debug($var, $display);
}

/**
 * URL重定向
 *
 * @param string    $msg    显示消息
 * @param string    $url    to Go
 * @param int       $time   页面停留时间单位秒，0：页面不跳转
 */
function redirect($url, $msg='', $time=3)
{
    $response = Response::getInstance();
    $response -> redirect($url, $msg, $time);
    exit;
}

