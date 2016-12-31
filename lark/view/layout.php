<?php
namespace Lark\view;

/**
 * 布局渲染引擎 - layout
 *
 * 此引擎负责页面布局渲染，如果不需要布局模板则不需要调用此引擎
 * 此布局渲染需要和模板渲染引擎配合适用
 *
 * @category    Lark
 * @package     Lark_View
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: layout.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Layout
{
    /**
     * 单例
     *
     * @var Lark_Request
     */
    static protected $_instance = null;

    protected $_layout    = '_layout';

    protected $_layoutTag = 'content';

    /**
     * 模板扩展名
     *
     * @var string
     */
    protected $_layoutSuffix = '.html';

    /**
     * 默认的模板，全路径
     *
     * @var string
     */
    protected $_template = '';

    /**
     * 哪个目录下的模板？
     * 默认根据app,controller,action路径搜索
     *
     * @var string
     */
    private $_layoutDirectory = array();

    /**
     * 构造函数
     */
    protected function __construct()
    {
        $this->setLayout('_layout');
    }

    /**
     * 实例化本程序
     *
     * @param $args = func_get_args();
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
     * 设置布局模板
     *
     * @param string $layoutTpl
     */
    public function setLayout($layoutTpl)
    {
        $this->_layout = $layoutTpl;
    }

    /**
     * 获得布局模板
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * 设置布局占位符
     *
     * @param string $tagName
     */
    public function setLayoutTag($tagName)
    {
        $this->_layoutTag = $tagName;
    }

    /**
     * 获得布局占位符
     *
     * @return string
     */
    protected function getLayoutTag()
    {
        return $this->_layoutTag;
    }

    /**
     * 设置布局物理模板的位置
     *
     * @param array $directory
     * @return array
     */
    public function setLayoutPath($directory)
    {
        $this->_layoutDirectory = $directory;
    }

    /**
     * 获得布局位置
     *
     * @return array
     */
    protected function getLayoutPath()
    {
        return $this->_layoutDirectory;
    }

    /**
     * 设置布局的后缀
     *
     * @param string $suffix
     */
    public function setLayoutSuffix($suffix)
    {
        $this->_layoutSuffix = $suffix;
    }

    /**
     * 获得布局的后缀
     *
     * @return string
     */
    protected function getLayoutSuffix()
    {
        return $this->_layoutSuffix;
    }

    /**
     * 渲染布局视图
     * 遍历所给的目录列表，找到合适的layout模板
     *
     * @return null|string
     */
    public function renderLayout(&$render, $content)
    {
        $render->assign($this->getLayoutTag(), $content);

        $template   = $this->getLayout();
        $layoutPath = $this->getLayoutPath();
        if (empty($layoutPath)) {
            exit('Layout Path not found!');
        }

        $render->setBaseDir($layoutPath);
        $render->setTemplate($template);
        $path = $render->getTemplatePath();
        if (file_exists($path)) {
            return $render->render();
        }

        return null;
    }

}
