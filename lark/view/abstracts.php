<?php
namespace Lark\view;
/**
 * 标准渲染器
 *
 * @category    Lark
 * @package     Lark_View
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: abstracts.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
abstract class Abstracts
{
    /**
     * 模板变量
     *
     * @var mixed
     */
    protected $_vars = array();

    /**
     * 模板名称
     *
     * @var string
     */
    protected $_template = '';

    /**
     * 模板扩展名
     *
     * @var string
     */
    protected $_templateSuffix = '.html';

    /**
     * 模板所在目录
     *
     * @var string
     */
    protected $_templateDirectory = '';

    /**
     * 是否需要_layout
     *
     * @var string
     */
    protected $_layout = '';

    /**
     * 渲染过的结果
     *
     * @var string
     */
    protected $_renderContent = '';

    /**
     * 设置要渲染的模板
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
    }

    /**
     * 获得要渲染的模板
     *
     * @return string
     */
    protected function getTemplate()
    {
        if(empty($this->_template)){
            $this->setTemplate();
        }
        return $this->_template;
    }

    /**
     * 设置模板位置目录
     *
     * @param $baseDirectory
     */
    public function setBaseDir($baseDirectory)
    {
        $this->_templateDirectory = $baseDirectory;
    }

    /**
     * 获得模板位置目录
     *
     * @return string
     */
    public function getBaseDir()
    {
        if (empty($this->_templateDirectory)) {
            $this->setBaseDir();
        }
        return $this->_templateDirectory;
    }

    /**
     * 设置模板扩展名
     *
     * @param string $suffix
     */
    public function setTemplateSuffix($suffix)
    {
        $this->_templateSuffix = $suffix;
    }

    /**
     * 获得模板扩展名
     *
     * @return string
     */
    public function  getTemplateSuffix()
    {
        if ($this->_templateSuffix) {
            return $this->_templateSuffix;
        } else {
            return '.html';
        }
    }

    /**
     * 获得模板物理位置
     *
     * @return string
     */
    public function getTemplatePath()
    {
        $template = $this->getTemplate();
        if ($template) {
            $template_path = $this->getBaseDir() . $template . $this->getTemplateSuffix();
            if (!file_exists($template_path)) {
                die('Template <strong>['
                . $template . $this->getTemplateSuffix() . ']</strong> not found '
                . "in directorys <br/> -- " .$this->getBaseDir()
                );
            } else {
                return $template_path;
            }
        } else {
            return $template;
        }
    }

    /**
     * 渲染方法
     *
     * @param  string $template 模板名字
     * @return string 渲染模板结果
     */
    abstract function render($template='');
    abstract function display($template='');

    /**
     * 获得渲染结果
     *
     * @return string
     */
    public function getRenderContent()
    {
        return $this->_renderContent;
    }

    /**
     * 设置是否启用layout
     *
     * @param $need
     */
    public function setLayout($need=true)
    {
        $this->_layout = $need;
    }

    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * 加载变量
     *
     * @param string $var 变量名
     * @param mixed $value
     */
    public function assign($var, $value)
    {
        $this->_vars[$var] = $value;
    }

    /**
     * 获得模板变量
     *
     * @param string $var 变量名称
     * @return mixed
     */
    public function getVar($var)
    {
        return $this->__get($var);
    }

    /**
     * 删除模板变量
     *
     * @param string $var 变量名称
     */
    public function clearVars($var)
    {
        if (isset($this->_vars[$var])) {
            $this->__unset($var);
        }
    }

    /**
     * 设置模板变量
     * @see self::assign()
     * @param string $var
     * @param mixed $value
     *
     */
    public function __set($var, $value)
    {
        $this->assign($var, $value);
    }

    /**
     * 获得模板变量
     *
     * @param string $var 变量名
     * @return mixed
     */
    public function __get($var)
    {
        if (isset($this->_vars[$var])) {
            return $this->_vars[$var];
        } else {
            return null;
        }
    }

    /**
     * 清除模板变量
     *
     * @param string $var
     */
    public function __unset($var)
    {
        unset($var);
    }

}
