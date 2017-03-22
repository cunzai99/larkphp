<?php
namespace Lark\db;
use Lark\plugin\LarkException;

/**
 * Pdo数据库驱动类
 *
 * @category    Lark
 * @package     Lark_Db
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: pdodriver.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class PdoDriver extends Abstracts
{
    protected $PDOStatement = null;
    private $table = '';

    /**
     * 架构函数 读取数据库配置信息
     *
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config = array())
    {
        if (!class_exists('PDO')) {
            throw new Exception('系统未安装相应的PHP扩展:Pdo');
        }
        if (!empty($config)) {
            $this->config = $config;
            if (empty($this->config['params'])){
                $this->config['params'] = array();
            }
        }
    }

    /**
     * 连接数据库方法
     *
     * @throws Exception
     */
    public function connect($config='', $linkNum=0)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }
            if ($this->pconnect) {
                $config['params'][\PDO::ATTR_PERSISTENT] = true;
            }

            try {
                if ('ODBC' == $this->dbType) {
                    $this->linkID[$linkNum] = new \PDO("odbc:Driver={SQL Server}; Server={$config['host']}; Uid={$config['username']}; Pwd={$config['password']}; Database={$config['dbname']};");
                } else {
                    $dsn = strtolower($this->dbType) . ":dbname={$config['dbname']};host={$config['host']};port={$config['port']}";
                    $this->linkID[$linkNum] = @new \PDO($dsn, $config ['username'], $config['password'], $config['params']);
                }
            } catch (LarkException $e) {
                throw new LarkException($e->getMessage());
            }
            // 因为PDO的连接切换可能导致数据库类型不同，因此重新获取下当前的数据库类型,以下暂没实现相关扩展
            if (!in_array($this->dbType, array('ODBC', 'DBLIB', 'MSSQL', 'ORACLE', 'IBASE', 'OCI'))) {
                $this->linkID[$linkNum]->exec('SET NAMES ' . $config ['charset']);
            }

            // 标记连接成功
            $this->connected = true;

            // 注销数据库连接配置信息
            // unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     * 释放查询结果
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 执行查询 返回数据集
     *
     * @param  string $str  sql指令
     * @param  string $type 返回数据类型，默认为带下标的二数组
     * @return mixed
     * @throws Exception
     */
    public function query($str, $type='assoc')
    {
        $this->initConnect(false);
        if(!$this->_linkID){
            return false;
        }

        $this->queryStr = $str;

        // 释放前次的查询结果
        if(!empty($this->PDOStatement)){
            $this->free();
        }

        $this->Q(1);

        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            throw new LarkException($this->error());
        }

        $result = $this->PDOStatement->execute();
        $this->debug ();
        if (false === $result) {
            throw new LarkException($this->error());
            //return false;
        } else {
            return $this->getAll($type);
        }
    }

    /**
     * 执行语句
     *
     * @param string $str sql指令
     * @return integer
     * @throws Exception
     */
    public function execute($str)
    {
        //@todo 要不要在这里做这件事
        $str = htmlspecialchars($str);

        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }

        $this->queryStr = $str;

        // 释放前次的查询结果
        if (!empty($this->PDOStatement)) {
            $this->free();
        }

        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            throw new LarkException($this->error());
        }

        $result = $this->PDOStatement->execute();
        $this->debug();
        if (false === $result) {
            throw new LarkException($this->error());
            //return false;
        } else {
            // $this->numRows = $result;
            $this->numRows = $this->PDOStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->getLastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     *
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }

        // 数据rollback 支持
        if ($this->transTimes == 0) {
            //$this->_linkID->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
            $this->_linkID->beginTransaction();
        }
        $this->transTimes ++;
        return;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return boolen
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->transTimes = 0;
            if (!$result) {
                throw new LarkException($this->error());
            }
        }
        return true;
    }

    /**
     * 事务回滚
     *
     * @return boolen
     * @throws Exception
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            //var_dump($result); exit;
            $this->transTimes = 0;
            if (!$result) {
                throw new Exception ($this->error());
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     *
     * @return array
     * @throws Exception
     */
    private function getAll($type = 'assoc')
    {
        // 返回数据集
        if ('assoc' == $type) {
            $result = $this->PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
        } else {
            $result = $this->PDOStatement->fetchAll(constant('PDO::FETCH_NUM'));
        }

        $this->numRows = count($result);
        return $result;
    }

    /**
     * 取得数据表的字段信息
     *
     * @throws Exception
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);

        switch ($this->dbType) {
            case 'MSSQL' :
            case 'ODBC' :
            case 'DBLIB' :
                $sql = "SELECT column_name as 'Name', data_type as 'Type', column_default as 'Default', is_nullable as 'Null'
                                                FROM information_schema.tables AS t
                                                JOIN information_schema.columns AS c
                                                ON  t.table_catalog  = c.table_catalog
                                                AND t.table_schema   = c.table_schema
                                                AND t.table_name     = c.table_name
                                                WHERE   t.table_name = '$tableName'";
                break;
            case 'SQLITE' : $sql = 'PRAGMA table_info (' . $tableName . ') '; break;
            case 'ORACLE' :
            case 'OCI' :
                $sql = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" " . "FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col " . "WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='" . strtoupper ( $tableName ) . "') b where table_name='" . strtoupper ( $tableName ) . "' and a.column_name=b.column_name(+)";
                break;
            case 'PGSQL' :
                $sql = 'select fields_name as "Field",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg(' . $tableName . ');';
                break;
            case 'IBASE' :
                break;
            case 'MYSQL' :
            default :
                $sql = 'DESCRIBE ' . $tableName;
        }

        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $name = strtolower ( isset ( $val ['Field'] ) ? $val ['Field'] : $val ['Name'] );
                $info [$name] = array (
                        'name' => $name,
                        'type' => $val ['Type'],
                        'notnull' => (bool) (((isset($val['Null'])) && ($val['Null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                        'default' => isset($val['Default'] ) ? $val['Default'] : (isset($val['dflt_value'] ) ? $val['dflt_value'] : ""),
                        'primary' => isset($val['Key'] ) ? strtolower ( $val['Key'] ) == 'pri' : (isset($val['pk'] ) ? $val['pk'] : false),
                        'autoinc' => isset($val['Extra'] ) ? strtolower ( $val['Extra'] ) == 'auto_increment' : (isset ( $val['Key'] ) ? $val['Key'] : false)
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     *
     * @throws Exception
     */
    public function getTables($dbName = '')
    {
        switch ($this->dbType) {
            case 'ORACLE' :
            case 'OCI' :
                $sql = 'SELECT table_name FROM user_tables';
                break;
            case 'MSSQL' :
            case 'ODBC' :
            case 'DBLIB' :
                $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                break;
            case 'PGSQL' :
                $sql = "select tablename as Tables_in_test from pg_tables where schemaname ='public'";
                break;
            case 'IBASE' :
                // 暂时不支持
                throw new Exception ( '系统暂不持' . ':IBASE' );
                break;
            case 'SQLITE' :
                $sql = "SELECT name FROM sqlite_master WHERE type='table' " . "UNION ALL SELECT name FROM sqlite_temp_master " . "WHERE type='table' ORDER BY name";
                break;
            case 'MYSQL' :
            default :
                if (!empty($dbName)) {
                    $sql = 'SHOW TABLES FROM ' . $dbName;
                } else {
                    $sql = 'SHOW TABLES ';
                }
        }

        $result = $this->query($sql);
        $info = array();
        foreach($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * limit分析
     *
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            switch ($this->dbType) {
                case 'PGSQL' :
                case 'SQLITE' :
                    $limit = explode (',', $limit);
                    if (count ( $limit ) > 1) {
                        $limitStr .= ' LIMIT ' . $limit [1] . ' OFFSET ' . $limit [0] . ' ';
                    } else {
                        $limitStr .= ' LIMIT ' . $limit [0] . ' ';
                    }
                    break;
                case 'MSSQL' :
                case 'ODBC' :
                case 'DBLIB' :
                    return '';
                    break;
                case 'IBASE' :
                    // 暂时不支持
                    break;
                case 'ORACLE' :
                case 'OCI' :
                    break;
                case 'MYSQL' :
                default :
                    $limitStr .= ' LIMIT ' . $limit . ' ';
            }
        }
        return $limitStr;
    }

    /**
     * mssql limit 分析
     *
     * @param string $sql
     * @param string $limit
     * @return string
     */
    protected function mssqlLimit($sql, $limit)
    {
        if (empty($limit) || !in_array($this->dbType, array (
                'DBLIB',
                'ODBC',
                'MSSQL'
        ) )) {
            return $sql;
        }

        $limit = explode(',', $limit);
        if (count($limit) > 1) {
            $count  = $limit[1];
            $offset = $limit[0];
        } else {
            $count  = $limit[0];
            $offset = 0;
        }

        $count = intval($count);
        if ($count <= 0) {
            throw new LarkException("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            throw new LarkException("LIMIT argument offset=$offset is not valid");
        }

        $sql = preg_replace('/^SELECT\s+(DISTINCT\s)?/i', 'SELECT $1TOP ' . ($count + $offset) . ' ', $sql);

        if ($offset > 0) {
            $orderby = stristr($sql, 'ORDER BY');

            if ($orderby !== false) {
                $orderParts = explode(',', substr($orderby, 8));
                $pregReplaceCount = null;
                $orderbyInverseParts = array();
                foreach ( $orderParts as $orderPart ) {
                    $orderPart = rtrim($orderPart);
                    $inv = preg_replace('/\s+desc$/i', ' ASC', $orderPart, 1, $pregReplaceCount);
                    if ($pregReplaceCount) {
                        $orderbyInverseParts[] = $inv;
                        continue;
                    }
                    $inv = preg_replace('/\s+asc$/i', ' DESC', $orderPart, 1, $pregReplaceCount);
                    if ($pregReplaceCount) {
                        $orderbyInverseParts[] = $inv;
                        continue;
                    } else {
                        $orderbyInverseParts[] = $orderPart . ' DESC';
                    }
                }

                $orderbyInverse = 'ORDER BY ' . implode(', ', $orderbyInverseParts);
            }

            $sql = 'SELECT * FROM (SELECT TOP ' . $count . ' * FROM (' . $sql . ') AS inner_tbl';
            if ($orderby !== false) {
                $sql .= ' ' . $orderbyInverse . ' ';
            }
            $sql .= ') AS outer_tbl';
            if ($orderby !== false) {
                $sql .= ' ' . $orderby;
            }
        }

        return $sql;
    }

    /**
     * 关闭数据库
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     *
     * @return string
     *
     */
    public function error()
    {
        $this->error = '';
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            if (isset($error[2]))
                $this->error = $error[2];
        }

        return $this->error;
    }

    /**
     * SQL指令安全过滤
     *
     * @access public
     * @param string $str SQL指令
     * @return string
     */
    public function escape_string($str)
    {
        switch ($this->dbType) {
            case 'SQLITE' :
            case 'ORACLE' :
            case 'OCI' :
            case 'PGSQL' :
            case 'MSSQL' :
            case 'IBASE' :
            case 'DBLIB' :
            case 'ODBC' :
                return str_ireplace("'", "''", $str);
            case 'MYSQL' :
            default :
                if (__MAGIC_QUOTES_GPC__) {
                    return $str;
                } else {
                    return addslashes($str);
                }
        }
    }

    /**
     * 获取最后插入id
     *
     * @access public
     * @return integer
     */
    public function getLastInsertId()
    {
        switch ($this->dbType) {
            case 'MSSQL' :
            case 'ODBC' :
            case 'DBLIB' :
                $sql = 'SELECT @@IDENTITY AS currval';
                // $sql = 'SELECT SCOPE_IDENTITY() AS currval';
                $this->PDOStatement = $this->_linkID->prepare($sql);
                $result = $this->PDOStatement->execute();
                $vo = $this->getAll('assoc');
                return $vo ? $vo[0]["currval"] : 0;
            case 'PGSQL' :
            case 'SQLITE' :
            case 'IBASE' :
            case 'MYSQL' :
                return $this->_linkID->lastInsertId();
            case 'ORACLE' :
            case 'OCI' :
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo ? $vo[0]["currval"] : 0;
        }
    }

    /**
     * 析构方法
     *
     * @access public
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}

