<?php
namespace Lark\plugin;
use Lark\core\View;

/**
 * 异常处理类
 *
 * @category    Lark
 * @package     Lark_Plugin
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: larkexception.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class LarkException extends \Exception
{
    /**
     * 错误文件模板
     *
     * @var string
     */
    protected $_exceptionFile = 'exception';

    /**
     * 渲染器
     *
     * @var Lark_View
     */
    protected $_viewer = null;

    /**
     * 错误列表显示
     *
     * @param string $message
     * @param code   $code
     */
    public function __construct($message=0, $code = null)
    {
        if(__DEBUG__){
            $this->_viewer = View::getInstance();
            $this->_viewer->setBaseDir(__TPL__);
            $this->_viewer->setTemplate($this->_exceptionFile);
            $this->_viewer->title = '出错了!';

            $time = date('Y-m-d H:i:s', time());
            $this->_viewer->time = $time;
            $this->_viewer->code = $code;
            $this->_viewer->file = $this->getFile();
            $this->_viewer->line = $this->getLine();
            $this->_viewer->message = $message;
            $this->_viewer->display();

            Debuger::showVar();
        }else{
            echo $message;
        }
        die();
    }

}

