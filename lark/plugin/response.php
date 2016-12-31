<?php
namespace Lark\plugin;
use Lark\core\view;

/**
 * 响应类
 *
 * @category    Lark
 * @package     Lark_Plugin
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: response.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Response
{
    /**
     * 跳转文件模板
     *
     * @var string
     */
    protected $_responseFile = 'msg';

    /**
     * 单例
     *
     * @var Lark_Dispatcher
     */
    static protected $_instance = null;

    /**
     * 构造函数
     * @param array $plugins
     */
    protected function __construct()
    {

    }

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
     * 重定向
     * @param string    $msg    显示消息
     * @param string    $url    to Go
     * @param int       $time   页面停留时间单位秒，0：页面不跳转
     *
     */
    public function redirect($url, $msg, $time=3)
    {
        $this->_viewer = View::getInstance();
        $this->_viewer->setBaseDir(__TPL__);
        $this->_viewer->setTemplate($this->_responseFile);

        $stay_time = $time * 1000;
        if ($stay_time == 0) {
            echo "<script>window.location.href ='{$url}';</script>"; exit;
        } else {
            if ($url == '') {
                $this->_viewer->direct_js = "setTimeout(\"history.go(-1);\",{$stay_time})";;
            } else {
                $this->_viewer->direct_js = "setTimeout(\"window.location.href ='{$url}';\",{$stay_time})";
            }
        }

        $this->_viewer->title   = '信息提示页';
        $this->_viewer->message = $msg.'<br /><br />3秒后自动跳转到首页<br /><a href="' . $url . '"> <b>点击这里</b> </a>手动跳转';
        $this->_viewer->display();
        die();
    }

}
