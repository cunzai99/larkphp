<?php
namespace Lark\db;
use Lark\plugin\Debuger;

/**
 * 数据库驱动抽象类
 *
 * @category    Lark
 * @package     Lark_Db
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: abstracts.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
abstract class Abstracts
{
    // 当前SQL指令
    protected $queryStr    = '';
    // 最后插入ID
    public $lastInsID      = null;
    // 返回或者影响记录数
    protected $numRows     = 0;
    // 返回字段数
    protected $numCols     = 0;
    // 事务指令数
    protected $transTimes  = 0;
    // 数据库表达式
    protected $comparison  = array('eq'=>'=','neq'=>'!=','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
    // 查询表达式
    protected $selectSql   = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';

    static protected $r_times = 0;
    static protected $w_times = 0;

    abstract function connect();

    /**
     *
     * 初始化数据库连接
     *
     * @access protected
     * @param  boolean $master 主服务器
     * @return void
     *
     */
    protected function initConnect($master=true)
    {
        $_config = array();
        if(empty($_config)) {
            // 缓存分布式数据库配置解析
            $_config = array();
            //如果没有设置读或写
            if (!isset($this->config['write']) || !isset($this->config['read'])){
                $_config['write'] = $_config['read'] = $this->config;
            } else {
                $_config['write'] = $this->config['write'];
                $_config['read']  = $this->config['read'];
            }
        }

        // 主从式采用读写分离
        if($master) {
            // 默认主服务器是连接第一个数据库配置
            $t = 'write';
        } else {
            // 读操作连接从服务器
            $t = 'read';
        }

        if (isset($_config[$t]['persist'])) {
            $this->pconnect = $_config[$t]['persist'];
        }

        $db_config = array(
                'username'  => $_config[$t]['username'],
                'password'  => $_config[$t]['password'],
                'host'      => $_config[$t]['host'],
                'port'      => $_config[$t]['port'],
                'dbname'    => $_config[$t]['dbname'],
                'charset'   => isset($_config[$t]['charset']) ? $_config[$t]['charset'] : $this->charset,
                'dsn'       => isset($_config[$t]['dsn']) ? $_config[$t]['dsn'] : '',
                'params'    => isset($_config[$t]['params']) ? $_config[$t]['params'] : ''
        );

        $this->_linkID = $this->connect($db_config, $t);
    }

    /**
     * 设置锁机制
     *
     * @access protected
     * @return string
     */
    protected function parseLock($lock=false)
    {
        if(!$lock) return '';
        if('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    /**
     * set分析
     *
     * @access protected
     * @param  array $data
     * @return string
     */
    protected function parseSet($data)
    {
        $set = array();
        foreach ($data as $key=>$val){
            $value = $this->parseValue($val);
            if(is_scalar($value)) {// 过滤非标量数据
                $set[] = $this->addSpecialChar($key) . '=' . $value;
            }
        }
        return ' SET '.implode(',', $set);
    }

    /**
     * value分析
     *
     * @access protected
     * @param  mixed $value
     * @return string
     */
    protected function parseValue(&$value)
    {
        if(is_string($value)) {
            $value = '\''.$this->escape_string($value).'\'';
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value = $this->escape_string($value[1]);
        }elseif(is_null($value)){
            $value = 'null';
        }
        return $value;
    }

    /**
     * field分析
     *
     * @access protected
     * @param  mixed $fields
     * @return string
     */
    protected function parseField($fields)
    {
        if(is_array($fields)) {
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array   =  array();
            foreach ($fields as $key=>$field){
                if(!is_numeric($key))
                    $array[] = $this->addSpecialChar($key).' AS '.$this->addSpecialChar($field);
                else
                    $array[] = $this->addSpecialChar($field);
            }
            $fieldsStr = implode(',', $array);
        }elseif(is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->addSpecialChar($fields);
        }else{
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    /**
     * table分析
     *
     * @access protected
     * @param  mixed $table
     * @return string
     */
    protected function parseTable($tables)
    {
        if(is_string($tables))
            $tables = explode(',', $tables);
        $array = array();
        foreach ($tables as $key=>$table){
            if(is_numeric($key)) {
                $array[] = $this->addSpecialChar($table);
            }else{
                $array[] = $this->addSpecialChar($key).' '.$this->addSpecialChar($table);
            }
        }
        return implode(',', $array);
    }

    /**
     * where分析
     *
     * @access protected
     * @param  mixed $where
     * @return string
     */
    protected function parseWhere($where) {
        $whereStr = '';
        if(!is_array($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        }else{ // 使用数组条件表达式
            if(array_key_exists('_logic', $where)) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate = ' '.strtoupper($where['_logic']).' ';
                unset($where['_logic']);
            }else{
                // 默认进行 AND 运算
                $operate = ' AND ';
            }
            foreach ($where as $key=>$val){
                $whereStr .= "( ";
                if(0 === strpos($key,'_')) {
                    // 解析特殊条件表达式
                    $whereStr .= $this->parseLarkWhere($key, $val);
                }else{
                    $key = $this->addSpecialChar($key);
                    if(is_array($val)) {
                        if(is_string($val[0])) {
                            if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i',$val[0])) { // 比较运算
                                $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parseValue($val[1]);
                            }elseif('exp'==strtolower($val[0])){ // 使用表达式
                                $whereStr .= ' ('.$key.' '.$val[1].') ';
                            }elseif(preg_match('/IN/i',$val[0])){ // IN 运算
                                if(is_array($val[1])) {
                                    array_walk($val[1], array($this, 'parseValue'));
                                    $zone = implode(',', $val[1]);
                                }else{
                                    $zone = $val[1];
                                }
                                $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                            }elseif(preg_match('/BETWEEN/i',$val[0])){ // BETWEEN运算
                                $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                                $whereStr .= ' ('.$key.' '.strtoupper($val[0]).' '.$this->parseValue($data[0]).' AND '.$this->parseValue($data[1]).' )';
                            }else{
                                throw new Exception('SQL BETWEEN 语法错误'.':'.$val[0]);
                            }
                        }else {
                            $count = count($val);
                            if(in_array(strtoupper(trim($val[$count-1])),array('AND','OR','XOR'))) {
                                $rule = strtoupper(trim($val[$count-1]));
                                $count = $count -1;
                            }else{
                                $rule = 'AND';
                            }
                            for($i=0; $i<$count; $i++) {
                                $data = is_array($val[$i])?$val[$i][1]:$val[$i];
                                if('exp'==strtolower($val[$i][0])) {
                                    $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                                }else{
                                    $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                                    $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                                }
                            }
                            $whereStr = substr($whereStr, 0, -4);
                        }
                    }else {
                        $whereStr .= $key . " = " . $this->parseValue($val);
                    }
                }
                $whereStr .= ' )'.$operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE '.$whereStr;
    }

    /**
     * 特殊条件分析
     *
     * @access protected
     * @param  string $key
     * @param  mixed $val
     * @return string
     */
    protected function parseLarkWhere($key, $val)
    {
        $whereStr = '';
        switch($key) {
            case '_string':
                // 字符串模式查询条件
                $whereStr = $val;
                break;
            case '_complex':
                // 复合查询条件
                $whereStr = substr($this->parseWhere($val), 6);
                break;
            case '_query':
                // 字符串模式查询条件
                parse_str($val, $where);
                if(array_key_exists('_logic', $where)) {
                    $op = ' ' . strtoupper($where['_logic']) . ' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = array();
                foreach ($where as $field=>$data) {
                    $array[] = $this->addSpecialChar($field).' = '.$this->parseValue($data);
                }
                $whereStr = implode($op, $array);
                break;
        }
        return $whereStr;
    }

    /**
     * limit分析
     *
     * @access protected
     * @param  mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT '.$limit.' ' : '';
    }

    /**
     * mssql limit 分析
     *
     * @param  string $sql
     * @param  string $limit
     * @return string
     */
    protected function mssqlLimit($sql, $limit)
    {
        return $sql;
    }

    /**
     * join分析
     *
     * @access protected
     * @param  mixed $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if(!empty($join)) {
            if(is_array($join)) {
                foreach ($join as $key=>$_join){
                    if(false !== stripos($_join,'JOIN'))
                        $joinStr .= ' '.$_join;
                    else
                        $joinStr .= ' LEFT JOIN ' .$_join;
                }
            }else{
                $joinStr .= ' LEFT JOIN ' .$join;
            }
        }
        return $joinStr;
    }

    /**
     * order分析
     *
     * @access protected
     * @param  mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if(is_array($order)) {
            $array = array();
            foreach ($order as $key=>$val){
                if(is_numeric($key)) {
                    $array[] = $this->addSpecialChar($val);
                }else{
                    $array[] = $this->addSpecialChar($key).' '.$val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * group分析
     *
     * @access protected
     * @param  mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     *
     * @access protected
     * @param  string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * distinct分析
     *
     * @access protected
     * @param  mixed $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * 插入记录
     *
     * @access public
     *
     * @param  mixed $data 数据
     * @param  array $options 参数表达式
     * @return false | integer
     */
    public function insert($data, $options=array())
    {
        $values = array();
        $fields = array();
        foreach ($data as $key=>$val){
            $value = $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'INSERT INTO '.$this->parseTable($options['table'])
               .' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /**
     * 通过Select方式插入记录
     *
     * @access public
     *
     * @param  string $fields 要插入的数据表字段名
     * @param  string $table 要插入的数据表名
     * @param  array $option  查询数据参数
     *
     * @return false | integer
     */
    public function selectInsert($fields, $table, $options=array())
    {
        if(is_string($fields)) {
            $fields = explode(',', $fields);
        }

        array_walk($fields, array($this, 'addSpecialChar'));
        $sql = 'INSERT INTO '.$this->parseTable($table).' ('.implode(',', $fields).') ';

        $selectSql = str_replace(
            array('%TABLE%','%DISTINCT%','%FIELDS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct'])?$options['distinct']:false),
                $this->parseField(isset($options['field'])?$options['field']:'*'),
                $this->parseJoin(isset($options['join'])?$options['join']:''),
                $this->parseWhere(isset($options['where'])?$options['where']:''),
                $this->parseGroup(isset($options['group'])?$options['group']:''),
                $this->parseHaving(isset($options['having'])?$options['having']:''),
                $this->parseOrder(isset($options['order'])?$options['order']:''),
                $this->parseLimit(isset($options['limit'])?$options['limit']:'')
            ), $this->selectSql);

        $selectSql = $this->mssqlLimit($selectSql, isset($options['limit']) ? $options['limit'] : '');

        $sql .= $selectSql;
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /**
     * 更新记录
     *
     * @access public
     * @param  mixed $data 数据
     * @param  array $options 表达式
     * @return false | integer
     */
    public function update($data, $options)
    {
        $sql = 'UPDATE '
            .$this->parseTable($options['table'])
            .$this->parseSet($data)
            .$this->parseWhere(isset($options['where']) ? $options['where'] : '')
            .$this->parseOrder(isset($options['order']) ? $options['order'] : '')
            .$this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
            .$this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /**
     * 删除记录
     *
     * @access public
     * @param  array $options 表达式
     * @return false | integer
     */
    public function delete($options=array())
    {
        $sql = 'DELETE FROM '
            .$this->parseTable($options['table'])
            .$this->parseWhere(isset($options['where']) ? $options['where'] : '')
            .$this->parseOrder(isset($options['order']) ? $options['order'] : '')
            .$this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
            .$this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute($sql);
    }

    /**
     * 查找记录
     *
     * @access public
     * @param  array $options 表达式
     * @return array
     */
    public function select($options=array())
    {
        if(isset($options['page'])) {
            // 根据页数计算limit
            @list($page,$listRows) = explode(',', $options['page']);
            $listRows = $listRows ? $listRows : ((isset($options['limit']) && is_numeric($options['limit'])) ? $options['limit'] : 20);
            $offset   = $listRows*((int)$page-1);
            $options['limit'] =  $offset.','.$listRows;
        }
        $sql = str_replace(
            array('%TABLE%','%DISTINCT%','%FIELDS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct']:false),
                $this->parseField(isset($options['field'])   ? $options['field']  : '*'),
                $this->parseJoin(isset($options['join'])     ? $options['join']   : ''),
                $this->parseWhere(isset($options['where'])   ? $options['where']  : ''),
                $this->parseGroup(isset($options['group'])   ? $options['group']  : ''),
                $this->parseHaving(isset($options['having']) ? $options['having'] : ''),
                $this->parseOrder(isset($options['order'])   ? $options['order']  : ''),
                $this->parseLimit(isset($options['limit'])   ? $options['limit']  : '')
            ),$this->selectSql);
        $sql  = $this->mssqlLimit($sql, isset($options['limit']) ? $options['limit'] : '');
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->query($sql);
    }

    /**
     * 字段和表名添加`
     * 保证指令中使用关键字不出错 针对mysql
     *
     * @access protected
     * @param  mixed $value
     * @return mixed
     */
    protected function addSpecialChar(&$value)
    {
        if(0 === strpos($this->dbType, 'MYSQL')){
            $value = trim($value);
            if( false !== strpos($value, ' ') || false !== strpos($value, ',') || false !== strpos($value, '*') ||  false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos($value, '`')) {
                //如果包含* 或者 使用了sql方法 则不作处理
            }else{
                $value = '`'.$value.'`';
            }
        }
        return $value;
    }

    /**
     * 查询次数更新或者查询
     *
     * @access public
     * @param  mixed $times
     * @return void
     */
    public function Q($times='')
    {
        if(empty($times)) {
            return self::$r_times;
        }else{
            self::$r_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(TRUE);
        }
    }

    /**
     * 写入次数更新或者查询
     *
     * @access public
     * @param  mixed $times
     * @return void
     */
    public function W($times='')
    {
        if(empty($times)) {
            return self::$w_times;
        }else{
            self::$w_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(TRUE);
        }
    }

    /**
     * 获取最近一次查询的sql语句
     *
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 获取最近的错误信息
     *
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error();
    }

    /**
     * 数据库调试 记录当前SQL
     *
     * @access protected
     */
    protected function debug()
    {
        // 记录操作结束时间
        if ( $this->debug ) {
            $runtime = number_format(microtime(TRUE) - $this->beginTime, 6);
            Debuger::debug(" RunTime:".$runtime."s SQL = ".$this->queryStr);
            if ($error = $this->error()) {
                throw new Exception($error);
            }
        }
    }

}
