<?php
namespace Lark\plugin;

/**
 * 调试请求
 *
 * 用法：Lark_Debuger::debug($var);
 * 一般是用Lark_Plugin::__debug();方法
 *
 * @category    Lark
 * @package     Lark_Plugin
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: debuger.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Debuger
{
    /**
     * 要调试的变量
     *
     * @var array
     */
    static protected $_debugVars = array();

    /**
     * 全局控制调试选项
     *
     * @var boolean
     */
    static protected $_allowDebug = true;

    /**
     * 当前调试时间
     *
     * @var float
     */
    static protected $_sectionTime = 0;

    /**
     * 总运行时间
     *
     * @var float
     */
    static protected $_time = 0;

    /**
     * 保存的时间变量组
     *
     * @var array
     */
    static protected $_timeVars = array();

    /**
     * 初始化运行时间
     *
     */
    protected function __construct()
    {
        self::$_time = self::$_sectionTime = self::getMicroTime();
    }

    /**
     * 获得计时器微秒
     *
     * @return float
     */
    static public function getMicroTime()
    {
        $timeArray = explode(' ',microtime()) ;
        $time = $timeArray[0] + $timeArray[1] ;
        return  $time;
    }

    /**
     * 调试某一个变量
     *
     * @param mixed $var        要调试的变量
     * @param boolean $display  是否在页面上显示，为false时，输出到头信息变量X-LarkPHP-Data中
     */
    static public function debug($var, $display=true)
    {
        if (!self::$_allowDebug) {
            return;
        }

        if (!$display) {
            ob_start();
            var_dump($var);
            $buf = ob_get_clean();
            header('X-LarkPHP-Data:' . $buf);
            return;
        } else {
            $currentId = count(self::$_debugVars);
            self::$_debugVars[$currentId+1] = $var;
        }
    }

    /**
     * 显示区域调试结果
     * 计时：上一个片段计时结束
     */
    static public function showVar()
    {
        // 格式化调试变量及显示变量获得时间
        foreach (self::$_debugVars as $key=>$value)
        {
            echo "<pre style='display:block;background:#eee;padding:10px;border:1px solid #000;clear:both;'>";
            var_dump($value);
            echo "</pre>";
        }
        return ;
    }

    /**
     * 格式显示运行时间
     *
     */
    static protected function showTime($time, $section=0)
    {
        echo "<span style='clear:both;display:block;background:#ff0;border:1px solid #000;'>";
        echo('section ' . $section . ' Time:' . $time);
        echo "</span>";
    }
}


