<?php
namespace Lark\core;
use Lark\db as dbdb;

/**
 * 数据库中间层实现类
 * 支持Mysql 以及 PDO
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: db.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Db
{
    // 数据库类型 默认使用PDO
    protected $dbType      = 'pdomysql';
    //编码格式 默认使用UTF8
    protected $charset     = 'UTF8';
    // 是否自动释放查询结果
    protected $autoFree    = false;
    // 是否显示调试信息 如果启用会在日志文件记录sql语句
    public $debug          = false;
    // 是否使用永久连接
    protected $pconnect    = false;
    // 错误信息
    protected $error       = '';
    // 数据库连接ID 支持多个连接
    protected $linkID      = array();
    // 当前连接ID
    protected $_linkID     = null;
    // 当前查询ID
    protected $queryID     = null;
    // 是否已经连接数据库
    protected $connected   = false;
    // 数据库连接参数配置
    protected $config      = '';
    // SQL 执行时间记录
    protected $beginTime;
    // 单例
    static private $instance = array();

    /**
     * 构造函数
     *
     * @access public
     * @param array $config 数据库配置数组
     */
    protected function __construct($config='')
    {
    }

    /**
     * 取得数据库类实例
     *
     * @static
     * @access public
     * @return mixed 返回数据库驱动类
     */
    public static function getInstance()
    {
        $args = func_get_args();
        $hash = md5(serialize($args));

        if (!isset(self::$instance[$hash])) {
            $o = new self();
            self::$instance[$hash] = call_user_func_array(array($o, 'factory'), $args);
        }
        return self::$instance[$hash];
    }

    /**
     * 加载数据库 支持配置文件或者 DSN
     *
     * @access public
     * @param  mixed $dbConfig 数据库配置信息
     * @return string
     * @throws Exception
     */
    public function factory($dbConfig='')
    {
        // 读取数据库配置
        $dbConfig = $this->parseConfig($dbConfig);

        if(empty($dbConfig['dbms'])){
            $dbConfig['dbms'] = $this->dbType;
        }else{
            $this->dbType = strtolower($dbConfig['dbms']);
        }

        // 判断数据库类型(不截取了...)
        $dbDriver = substr($this->dbType, 0, 3);

        // 检查驱动类
        if(!class_exists($dbDriver)) {
            throw new Exception('数据库驱动不存在: ' . $dbConfig['dbms']);
        }

        if ($dbDriver == 'pdo') {
            $this->dbType = $dbConfig['dbtype'];
            $db = 'pdoDriver';
        } else {
            $db = $this->dbType;
        }

        $dbClass = ucfirst($db);

        switch ($dbDriver) {
            case 'pdo':
                $db = new dbdb\PdoDriver($dbConfig);
                break;

            default:
                # code...
                break;
        }

        $db->dbType = strtoupper($this->dbType);

        return $db;
    }

    /**
     * 分析数据库配置信息，支持数组和DSN
     *
     * @access private
     * @param  mixed $dbConfig 数据库配置信息
     * @return string
     */
    private function parseConfig($dbConfig='')
    {
        if ( !empty($dbConfig) && is_string($dbConfig)) {
            // 如果DSN字符串则进行解析
            $dbConfig = $this->parseDSN($dbConfig);
        }
        return $dbConfig;
    }

    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName
     *
     * @access public
     * @param  string $dsnStr
     * @return array
     */
    public function parseDSN($dsnStr)
    {
        if( empty($dsnStr) ){return false;}
        $info = parse_url($dsnStr);
        if($info['scheme']){
            $dsn = array(
                'dbms'     => $info['scheme'],
                'username' => isset($info['user']) ? $info['user'] : '',
                'password' => isset($info['pass']) ? $info['pass'] : '',
                'host'     => isset($info['host']) ? $info['host'] : '',
                'port'     => isset($info['port']) ? $info['port'] : '',
                'dbname'   => isset($info['path']) ? substr($info['path'],1) : ''
            );
        }else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = array (
                'dbms'     => $matches[1],
                'username' => $matches[2],
                'password' => $matches[3],
                'host'     => $matches[4],
                'port'     => $matches[5],
                'dbname'   => $matches[6]
            );
        }
        return $dsn;
    }

    /**
     * 根据DSN获取数据库类型 返回大写
     *
     * @access protected
     * @param  string $dsn  dsn字符串
     * @return string
     */
    protected function _getDsnType($dsn)
    {
        $match  = explode(':', $dsn);
        $dbType = strtoupper(trim($match[0]));
        return $dbType;
    }

    /**
     * 增加数据库连接(相同类型的)
     *
     * @param  mixed $config 数据库连接信息
     * @param  mixed $linkNum  创建的连接序号
     *
     * @return void
     */
    public function addConnect($config, $linkNum=null)
    {
        $dbConfig = $this->parseConfig($config);
        if(empty($linkNum))
            $linkNum = count($this->linkID);
        if(isset($this->linkID[$linkNum]))
            // 已经存在连接
            return false;
        // 创建新的数据库连接
        return $this->connect($dbConfig, $linkNum);
    }

    /**
     * 切换数据库连接
     *
     * @param  integer $linkNum  创建的连接序号
     * @return void
     */
    public function switchConnect($linkNum)
    {
        if(isset($this->linkID[$linkNum])) {
            // 存在指定的数据库连接序号
            $this->_linkID = $this->linkID[$linkNum];
            return true;
        }else{
            return false;
        }
    }

}
