<?php
namespace Lark\view;
use Lark\plugin\LarkException;

/**
 * 模板渲染引擎 - LarkRender
 *
 * @category    Lark
 * @package     Lark_View
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: render.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Render extends Abstracts{
    private $conf               = '';
    private $left_delimiter     = '<{';          //模板左标签
    private $right_delimiter    = '}>';          //模板右标签
    private $compile_dir        = '';
    private $compile_suffix     = '.tpl.php';

    //定义每个模板的标签的元素
    private $tag_foreach = array('from', 'item', 'key');
    private $tag_include = array('file');        //目前只支持读取模板默认路径

    public function __construct()
    {
        $this->compile_dir = _COMPILE_;
    }

    /**
     * 渲染页面
     *
     * @return
     */
    public function display($filename = '')
    {
        //编译开始
        echo $this->compile();
    }

     /**
     * 渲染页面
     *
     * @return
     */
    public function render($filename = '')
    {
        //编译开始
        return $this->compile();
    }

    /**
     * 编译控制器
     *
     * @param
     * @return
     */
    private function compile()
    {
        $file_path = $this->getTemplatePath();

        $template_name     = $this->getTemplate();
        $compile_file_name = $this->getCompileName($template_name);
        $compile_file      = $this->_compile($this->readFile($file_path), $compile_file_name);

        if($compile_file) {
            extract($this->_vars, EXTR_SKIP);
            ob_start();
            include($compile_file);
            $this->_renderContent = ob_get_clean();
            return $this->_renderContent;
        }
    }

    /**
     * 解析模板语法
     *
     * @param $str 内容
     * @param $compile_file_name 模版编译文件名
     * @return 编译过的PHP模板文件名
     */
    private function _compile($str, $compile_file_name)
    {
        //处理编译头部
        if(!$compile_file_name){
            die('编译模板名不能为空');
        }

        $compile_path = $this->compile_dir.$compile_file_name;    //编译文件
        if(is_file($compile_path)) {
            $file_path = $this->getTemplatePath();
            $compile_filemtime  = filemtime($compile_path);
            $template_filemtime = filemtime($file_path);

            //如果文件过期编译   当模板标签有include并且有修改时 也重新编译
            //<{include file="public/left.html"}> 当修改include里的文件，非DEBUG模式时  如果不更改主文件 目前是不重新编译include里的文件，我在考虑是否也要更改，没想好，暂时这样，所以在开发阶段一定要开启DEBUG=1模式 要不然修改include文件无效 。 有点罗嗦，不知道表述清楚没
            $debug = defined(_DEBUG_) ? _DEBUG_ : false;
            if($template_filemtime > $compile_filemtime || $debug) {
                $ret_file = $this->compileFile($compile_file_name, $str);
            }else{
                $ret_file = $compile_path;
            }
        }else{//编译文件不存在 创建他
            $ret_file = $this->compileFile($compile_file_name, $str);
        }

        return $ret_file;
    }

    /**
     * 写文件
     *
     * @param  string  $filename    文件路径
     * @param  string  $content     模板内容
     * @return 文件名
     */
    private function compileFile($filename, $content)
    {
        if(empty($filename)) {
            throw new LarkException('Error 404, error page not found!', 1);
        }

        if(!$this->compile_dir){
            throw new LarkException('Error 404, 未指定模板编译目录!', 1);
        }

        //检查目录是否存在
        $this->checkCompileDir($this->compile_dir);

        //对文件内容操作
        $content = $this->bodyContent($content);
        F($filename, $content, $this->compile_dir);
        // if(($fp = @fopen($filename, 'wb')) === false) {
        //     die("文件：$filename <br /> 编译失败，请检查文件权限.");
        // }

        // //开启flock
        // @flock($fp, LOCK_EX + LOCK_NB);
        // fwrite($fp, $content, strlen($content));
        // @flock($fp, LOCK_UN + LOCK_NB);
        // fclose($fp);

        return $this->compile_dir.$filename;
    }

    /**
     * 模板文件主体
     *
     * @param  $str    内容
     * @return html
     */
    private function bodyContent($str)
    {
        $str = $this->parse($str);
        $header_comment = "Create On##".time()."|Compiled from##".$this->_template_name;
        $content = "<?php if(!defined('__IS_LARKPHP__')) exit('Access Denied');?>\n$str";

        return $content;
    }

    /**
     * 开始解析相关模板标签
     *
     * @param $content 模板内容
     */
    private function parse($content)
    {
        $content = $this->parseDot($content);       //.
        $content = $this->parseForeach($content);   //foreach
        $content = $this->parseInclude($content);   //include
        $content = $this->parseLang($content);      //language
        $content = $this->parseIf($content);        //if
        $content = $this->parseElseif($content);    //elseif
        $content = $this->parseComm($content);      //模板标签公用部分
        $content = $this->parsePhp($content);       //转为PHP代码

        return $content;
    }

    /**
     * echo 如果默认直接<{$config['domain']}> 转成 <?php echo $config['domain']?>
     */
    private function parseEcho($content)
    {

    }

    /**
     * 转换数组元素调用
     *
     * <{$item.title}> 替换为 <{$item['title']}>
     * <{$item.test.a}>  替换为 <{$item['test']['a']}>
     *
     * @param $content html 模板内容
     * @return html 替换好的HTML
     */
    private function parseDot($content)
    {
        preg_match_all('/<{(.+?)}>/is', $content, $match);

        if(isset($match[1]) && !empty($match[1])){
            $replacements = $patterns = array();
            foreach($match[1] as $key => $val){
                if(strpos($val, '.') === false) continue;
                $tmp = explode('.', $val);
                if(strpos($tmp[0], '$') === false) continue;
                foreach($tmp as $k => $v){
                    if($k == 0) continue;
                    if(strpos($v, ' ') == true){
                        $t = explode(' ', $v);
                        $parenthese = false;
                        if(strpos($t[0], ')') == true){
                            $parenthese = true;
                            $t[0] = rtrim($t[0], ')');
                        }
                        $t[0] = "['".$t[0]."']";
                        if($parenthese){
                            $t[0] .= ')';
                            $parenthese = false;
                        }
                        $v = implode(' ', $t);
                        $tmp[$k] = $v;
                    }else{
                        if(strpos($v, ')') == true){
                            $v = rtrim($v, ')');
                            $tmp[$k] = "['".$v."'])";
                        }else{
                            $tmp[$k] = "['".$v."']";
                        }
                    }
                }
                $patterns[]     = $match[0][$key];
                $replacements[] = '<{'.implode('', $tmp).'}>';
            }
            $content = str_replace($patterns, $replacements, $content);
        }
        return $content;
    }

    /**
     * 转换为PHP
     *
     * @param $content html 模板内容
     * @return html 替换好的HTML
     */
    private function parsePhp($content)
    {
        if(empty($content)) return false;
        $content = preg_replace("/".$this->left_delimiter."(.+?)".$this->right_delimiter."/is", "<?php echo $1 ?>", $content);
        return $content;
    }

    /**
     * if判断语句
     *
     * <{if empty($zhang)}>
     * zhang
     * <{elseif empty($liang)}>
     *  liang
     * <{else}>
     *  zhangliang
     * <{/if}>
     */
    private function parseIf($content)
    {
        if(empty($content)) return false;

        $match = $this->preg_match_all("if\s+(.*?)", $content);
        if(!isset($match[1]) || !is_array($match[1])) return $content;

        foreach($match[1] as $k => $v) {
            //$s = preg_split("/\s+/is", $v);
            //$s = array_filter($s);
            $content = str_replace($match[0][$k], "<?php if({$v}) { ?>", $content);
        }

        return $content;
    }

    private function parseElseif($content)
    {
        if(empty($content)) return false;

        $match = $this->preg_match_all("elseif\s+(.*?)", $content);
        if(!isset($match[1]) || !is_array($match[1])) return $content;

        foreach($match[1] as $k => $v) {
            //$s = preg_split("/\s+/is", $v);
            //$s = array_filter($s);
            $content = str_replace($match[0][$k], "<?php } elseif ({$v}) { ?>", $content);
        }

        return $content;
    }

    /**
     * 解析 include
     *
     * include标签不是实时更新的  当主体文件更新的时候 才更新标签内容，所以想include生效 请修改一下主体文件
     * 记录一下 有时间开发一个当DEBUG模式的时候 每次执行删除模版编译文件
     * 使用方法 <{include file="..."}>
     *
     * @param $content 模板内容
     * @return html
     */
    private function parseInclude($content)
    {
        if(empty($content)) return false;

        $match = $this->preg_match_all("include\s+(.*?)", $content);
        if(!isset($match[1]) || !is_array($match[1])) return $content;

        $base_dir = $this->getBaseDir();
        foreach($match[1] as $match_key => $match_value) {
            $a = preg_split("/\s+/is", $match_value);

            $new_tag = array();
            //分析元素
            foreach($a as $t) {
                $b = explode('=', $t);

                if(in_array($b[0], $this->tag_include)) {
                    if(!empty($b[1])) {
                        $_old = array("\"", "'");
                        $_new = array(""  , "");
                        $new_tag[$b[0]] = str_replace($_old, $_new, $b[1]);
                    } else {
                        die('模板路径不存在!');
                    }
                }
            }

            extract($new_tag);
            $compile_file_name = $this->getCompileName($file);
            $file              = $base_dir.$file;
            $file_content      = $this->readFile($file);

            $include_file = $this->_compile($file_content, $compile_file_name);
            $content = str_replace($match[0][$match_key], '<?php include("'.$include_file.'")?>', $content);
        }
        return $content;
    }

    /**
     * 解析 lang
     *
     * lang 标签实现了语言国际化
     * 使用方法 <{lang="..."}>
     *
     * @param $content 模板内容
     * @return html
     */
    private function parseLang($content)
    {
        $match = $this->preg_match_all("lang\s?=\s?(.*?)", $content);
        if(!isset($match[1]) || !is_array($match[1])) return $content;

        foreach($match[1] as $match_key => $match_value) {
            $_old = array("\"", "'");
            $_new = array(""  , "");
            $match_value = str_replace($_old, $_new, $match_value);

            $content = str_replace($match[0][$match_key], '<?php echo get_language("'.$match_value.'")?>', $content);
        }

        return $content;
    }

    /**
     * 解析 foreach
     *
     * 使用方法 <{foreach from=$lists item=value key=key}>
     * @param $content 模板内容
     * @return html 解析后的内容
     */
    private function parseForeach($content)
    {
        if(empty($content)) return false;

        $match = $this->preg_match_all("foreach\s+(.*?)", $content);
        if(!isset($match[1]) || !is_array($match[1])) return $content;

        foreach($match[1] as $match_key => $value) {

            $split = preg_split("/\s+/is", $value);
            $split = array_filter($split);

            $new_tag = array();
            foreach($split as $v) {
                $a = explode("=", $v);
                if(in_array($a[0], $this->tag_foreach)) {//此处过滤标签 不存在过滤
                    $new_tag[$a[0]] = $a[1];
                }
            }

            extract($new_tag);
            $key = isset($key) ? '$'.$key.' =>' : '' ;
            $s   = '<?php foreach('.$from.' as '.$key.' $'.$item.') { ?>';
            $content = $this->str_replace($match[0][$match_key], $s, $content);
            unset($key);
        }
        return $content;
    }

    /**
     * 匹配结束 字符串
     *
     * @param $content
     */
    private function parseComm($content)
    {
        $search = array(
            "/".$this->left_delimiter."\/foreach".$this->right_delimiter."/is",
            "/".$this->left_delimiter."\/if".$this->right_delimiter."/is",
            "/".$this->left_delimiter."else".$this->right_delimiter."/is",

        );

        $replace = array(
            "<?php } ?>",
            "<?php } ?>",
            "<?php } else { ?>"
        );
        $content = preg_replace($search, $replace, $content);
        return $content;
    }

    /**
     * 检查编译目录  如果没有创建 则递归创建目录
     *
     */
    private function checkCompileDir()
    {
        if(!is_dir($this->compile_dir)){
            mk_dir($this->compile_dir);
        }
    }

    /**
     * 读文件
     *
     * @param  string  $path   文件完整路径
     * @return 模板内容
     */
    private function readFile($path)
    {
        if(!file_exists($path)){
            throw new LarkException("模版文件: $path <br /> 不存在！", 1);
        }
        if(($r = @fopen($path, 'r')) === false) {
            throw new LarkException("模版文件: $path <br /> 没有读取或执行权限，请检查！", 1);
        }
        $length = filesize($path);
        if($length){
            $content = fread($r, $length);
        }else{
            $content = '';
        }
        fclose($r);
        return $content;
    }

    /**
     * 这个检查文件权限函数
     *
     * @param  [$path] [路径]
     * @param  [status] [w=write, r=read]
     */
    public function checkFileLimits($path , $status = 'rw')
    {
        clearstatcache();
        if(!is_writable($path) && $status == 'w') {
            $msg = "{$path}<br/>没有写入权限，请检查.";
        }elseif(!is_readable($path) && $status == 'r') {
            $msg = "{$path}<br/>没有读取权限，请检查.";
        }elseif($status == 'rw') {
            if(!is_writable($path) || !is_readable($path)) {
                $msg = "{$path}<br/>没有写入或读取权限，请检查";
            }
        }
        throw new LarkException($msg, 1);
    }

    private function getCompileName($template_name)
    {
        $base_dir = $this->getBaseDir();
        $template_path = $base_dir.$template_name;

        $compile_name = md5($template_path).'.'.$template_name.$this->compile_suffix;

        return $compile_name;
    }

    /**
     * str_replace
     *
     * @param $search
     * @param $replace
     * @param $content
     * @return array
     */
    private function str_replace($search, $replace, $content)
    {
        if(empty($search) || empty($replace) || empty($content)) return false;
        return str_replace($search, $replace, $content);
    }

    /**
     * preg_match_all
     *
     * @param $pattern 正则
     * @param $content 内容
     * @return array
     */
    private function preg_match_all($pattern, $content)
    {
        if(empty($pattern) || empty($content)){
            //throw new LarkException('Error 100, !', 1);
        }else{
            preg_match_all("/".$this->left_delimiter.$pattern.$this->right_delimiter."/is", $content, $match);
            return $match;
        }
    }

    public function __destruct()
    {
        $this->_vars = null;
        $this->view_path_param = null;
    }

};

