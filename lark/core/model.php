<?php
namespace Lark\core;
use Lark\plugin\LarkException;

/**
 * 处理业务关系及数据关系的模型
 *
 * @category    Lark
 * @package     Lark_Core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: model.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */
class Model
{
    protected $_db              = null;       //当前数据库操作对象
    protected $_pk              = 'id';       //主键名称
    protected $_tablePrefix     = '';         //数据表前缀
    protected $_tableSuffix     = '';         //数据表后缀
    protected $_name            = '';         //模型名称
    protected $_dbName          = '';         //数据库名称
    protected $_tableName       = '';         //数据表名（不包含表前缀）
    protected $_trueTableName   ='';          //实际数据表名（包含表前缀）
    protected $_error           = '';         //最近错误信息
    protected $_fields          = array();    //字段信息
    protected $_data            = array();    //数据信息
    protected $_options         = array();
    protected $_dbConfig        = array();    //数据库配置
    static public $debug        = false;
    protected $_autoCheckFields = true;       //是否自动检测数据表字段信息
    public static $_application = '';         //记录调用模型的模块名
    static public $dbFieldtypeCheck = true;   //是否启用字段验证

    /**
     * 构造函数
     *
     * @param   string $name     模型名称
     * @param   array  $param    数据库配置
     * @param   array  $dbConfig 数据库配置
     * @example
     */
    public function __construct($name='', $param=array(), $dbConfig=array())
    {
        // 获取模型名称
        if(!empty($name)) {
            $this->_name = $name;
        }

        // 初始化数据库配置文件
        if (empty($dbConfig)) {
            $dbConfig = require(_CONFIG_ . 'db.php');
        }

        // 设置表前/后缀
        $this->_tablePrefix = $this->_tablePrefix ? $this->_tablePrefix : (isset($dbConfig['tablePrefix']) ? $dbConfig['tablePrefix'] : '');
        $this->_tableSuffix = $this->_tableSuffix ? $this->_tableSuffix : (isset($dbConfig['tableSuffix']) ? $dbConfig['tableSuffix'] : '');

        // 初始化数据库连接
        $this->_db = Db::getInstance($dbConfig);
        if (isset($dbConfig['debug'])) {
            self::$debug      = $dbConfig['debug'];
            $this->_db->debug = $dbConfig['debug'];
        }

        $this->_dbConfig = $dbConfig;
        unset($dbConfig);

        // 字段检测
        if(!empty($this->_name) && $this->_autoCheckFields) {
            $this->_dbName = $this->_dbConfig['write']['dbname'];
            $this->_checkTableInfo();
        }
    }

    /**
     * 自动检测数据表信息
     *
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
        // 只在第一次执行记录
        if(empty($this->_fields)) {
            // 如果数据表字段没有定义则自动获取
            if(!self::$debug) {
                $this->_fields = F($this->_tableInfoPath(), '', _FIELDS_);
                if(!$this->_fields) {
                    $this->flush();
                }
            }else{
                $this->flush(); // 每次都会读取数据表信息
            }
        }
    }

    /**
     * 获取字段信息并缓存
     *
     * @param  $updateFile  是否更新表结构缓存文件
     * @return void
     */
    public function flush($updateFile = true)
    {
        // 缓存不存在则查询数据表信息
        $fields = $this->_db->getFields($this->getTableName());
        $this->_fields = array_keys($fields);
        $this->_fields['_autoinc'] = false;
        foreach ($fields as $key=>$val){
            // 记录字段类型
            $type[$key] = $val['type'];
            if($val['primary']) {
                $this->_fields['_pk'] = $key;
                if($val['autoinc']) $this->_fields['_autoinc'] = true;
            }
        }

        // 记录字段类型信息
        if (self::$dbFieldtypeCheck && isset($type)) {
            $this->_fields['_type'] = $type;
        }

        if (!self::$debug && $updateFile) {
            // 数据表信息缓存到文件
            $fields = "<?php\r\n\r\nreturn ".var_export($this->_fields, true).";\r\n?>";
            F($this->_tableInfoPath(), $fields, _FIELDS_);
        }
    }

    protected function _tableInfoPath()
    {
        return $this->_dbName . __SEPARATOR__ . $this->getTableName() . '.php';
    }

    /**
     * 对保存到数据库的数据进行处理
     *
     * @param  mixed $data 要操作的数据
     * @return boolean
     */
    protected function _facade($data)
    {
        // 检查非数据字段
        if(!empty($this->_fields)) {
            foreach ($data as $key=>$val){
                if(!in_array($key, $this->_fields,true)){
                    unset($data[$key]);
                }elseif(self::$dbFieldtypeCheck && is_scalar($val)) {
                    // 字段类型检查
                    $fieldType = strtolower($this->_fields['_type'][$key]);
                    if(false !== strpos($fieldType, 'int')) {
                        $data[$key] = intval($val);
                    }elseif(false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')){
                        $data[$key] = floatval($val);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 新增数据
     *
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return mixed
     */
    public function add($data='', $options=array())
    {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->_data)) {
                $data = $this->_data;
            }else{
                $this->_error = '添加记录类型必须为对象或数组';
                return false;
            }
        }

        $options =  $this->_parseOptions($options); // 分析表达式
        $data    = $this->_facade($data);           // 数据处理

        // 写入数据到数据库
        $result = $this->_db->insert($data, $options);
        if(false !== $result ) {
            $insertId = $this->getLastInsID();
            if($insertId) {
                // 自增主键返回插入ID
                $data[$this->getPk()] = $insertId;
                return $insertId;
            }
        }
        return $result;
    }

    /**
     * 保存数据
     *
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save($data='', $options=array())
    {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->_data)) {
                $data = $this->_data;
            }else{
                $this->_error = '保存的数据类型错误';
                return false;
            }
        }

        $data    = $this->_facade($data);           // 数据处理
        $options = $this->_parseOptions($options);  // 分析表达式

        if(!isset($options['where']) ) {
            // 如果存在主键数据 则自动作为更新条件
            if(isset($data[$this->getPk()])) {
                $pk = $this->getPk();
                $options['where'] = $pk.'=\''.$data[$pk].'\'';
                $pkValue = $data[$pk];
                unset($data[$pk]);
            }else{
                // 如果没有任何更新条件则不执行
                $this->_error = '没有更新条件';
                return false;
            }
        }

        $result = $this->_db->update($data, $options);
        if(false !== $result) {
            if(isset($pkValue)) $data[$pk] = $pkValue;
        }

        return $result;
    }

    /**
     * 删除数据
     *
     * @param  mixed $options 表达式
     * @return mixed
     */
    public function delete($options=array())
    {
        if(empty($options) && empty($this->_options)) {
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if(!empty($this->_data) && isset($this->_data[$this->getPk()])) {
                return $this->delete($this->_data[$this->getPk()]);
            } else {
                return false;
            }
        }

        if(is_numeric($options) || is_string($options)) {
            // 根据主键删除记录
            $pk = $this->getPk();
            if(strpos($options, ',')) {
                $where = $pk.' IN ('.$options.')';
            }else{
                $where   = $pk.'=\''.$options.'\'';
                $pkValue = $options;
            }
            $options = array();
            $options['where'] =  $where;
        }

        //防止整表数据误删除
        if ( !isset($this->_options['where']) && !isset($options['where'])) {
            return false;
        }

        // 分析表达式
        $options = $this->_parseOptions($options);
        $result  = $this->_db->delete($options);

        if(false !== $result) {
            $data = array();
            if(isset($pkValue)) $data[$pk] = $pkValue;
        }

        // 返回删除记录个数
        return $result;
    }

    /**
     * 查询数据集
     *
     * @param  array $options 表达式参数
     * @return mixed
     */
    public function select()
    {
        // 分析表达式
        $options = $this->_parseOptions();
        $result  = $this->_db->select($options);

        if(false === $result) {
            return false;
        }

        if(empty($result)) {
            return array();
        }

        return $result;
    }

    public function findAll($options=array()) {
        return $this->select();
    }

    /**
     * 查询数据
     *
     * @param  mixed $options 表达式参数
     * @return mixed
     */
    public function find($options=array())
    {
        if(is_numeric($options) || is_string($options)) {
            $where   = $this->getPk().'=\''.$options.'\'';
            $options = array();
            $options['where'] = $where;
        }

         // 总是查找一条记录
        $options['limit'] = 1;

        // 分析表达式
        $options = $this->_parseOptions($options);
        $result  = $this->_db->select($options);

        if(false === $result) {
            return false;
        }

        if(empty($result)) {
            return array();
        }

        // $this->_data = $result[0];
        return $result[0];
    }

    /**
     * 分析表达式
     *
     * @access private
     * @param  array $options 表达式参数
     * @return array
     */
    private function _parseOptions($options=array())
    {
        if(is_array($options)) {
            $options = array_merge($this->_options, $options);
        }
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->_options = array();
        if(!isset($options['table'])) {
            $options['table'] = $this->getTableName();  //获取表名
        }
        // 字段类型验证
        if(self::$dbFieldtypeCheck) {
            if(isset($options['where']) && is_array($options['where'])) {
                // 对数组查询条件进行字段类型检查
                foreach ($options['where'] as $key=>$val){
                    if(in_array($key, $this->_fields, true) && is_scalar($val)){
                        $fieldType = strtolower($this->_fields['_type'][$key]);
                        if(false !== strpos($fieldType, 'int')) {
                            $options['where'][$key] = intval($val);
                        }elseif(false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double')){
                            $options['where'][$key] = floatval($val);
                        }
                    }
                }
            }
        }
        // 表达式过滤
        $this->_options_filter($options);
        return $options;
    }

    // 表达式过滤回调方法
    protected function _options_filter(&$options) {}

    /**
     * 获取一条记录的某个字段值
     *
     * @param string $field     字段名
     * @param mixed $condition  查询条件
     *
     * @return mixed
     */
    public function getField($field, $condition='')
    {
        if($condition) {
            $options['where'] = $condition;
        }

        $options['field'] = $field;
        $options = $this->_parseOptions($options);
        $result  = $this->_db->select($options);

        if(false === $result) {
            return false;
        }

        if(!empty($result)) {
            return reset($result[0]);
        }
    }

    /**
     * 设置记录的某个字段值
     * 支持使用数据库字段和方法
     *
     * @param string|array $field  字段名
     * @param string|array $value  字段值
     * @param mixed $condition     条件
     *
     * @return boolean
     */
    public function setField($field, $value, $condition='')
    {
        if(empty($condition) && isset($this->_options['where'])){
            $condition = $this->_options['where'];
        }

        $options['where'] = $condition;

        if(is_array($field)) {
            foreach ($field as $key=>$val)
                $data[$val] = $value[$key];
        }else{
            $data[$field] = $value;
        }

        return $this->save($data, $options);
    }

    /**
     * 字段值增长
     *
     * @param string $field     字段名
     * @param integer $step     增长值
     *
     * @return boolean
     */
    public function setInc($field, $condition='', $step=1)
    {
        return $this->setField($field, array('exp', $field.'+'.$step), $condition);
    }

    /**
     * 字段值减少
     *
     * @param string $field     字段名
     * @param integer $step     减少值
     *
     * @return boolean
     */
    public function setDec($field, $condition='', $step=1)
    {
        return $this->setField($field, array('exp', $field.'-'.$step), $condition);
    }

    /**
     * SQL查询
     *
     * @param  mixed $sql    SQL指令
     * @param  string $type  返回数据类型，默认为带下标的二数组
     * @return mixed
     */
    public function query($sql, $type = 'assoc')
    {
        if(!empty($sql)) {
            if(strpos($sql, '__TABLE__')) {
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            }
            return $this->_db->query($sql, $type);
        }else{
            return false;
        }
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql  SQL指令
     * @return false | integer
     */
    public function execute($sql)
    {
        if(!empty($sql)) {
            if(strpos($sql, '__TABLE__'))
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            return $this->_db->execute($sql);
        }else {
            return false;
        }
    }

    /**
     * 得到当前的数据对象名称
     *
     * @return string
     */
    public function getModelName()
    {
        if(empty($this->_name)) {
            $this->_name = get_class($this);
        }
        return $this->_name;
    }

    /**
     * 得到完整的数据表名
     *
     * @return string
     */
    public function getTableName()
    {
        if(empty($this->_trueTableName)) {
            $tableName  = !empty($this->_tablePrefix) ? $this->_tablePrefix : '';
            if(!empty($this->_tableName)) {
                $tableName .= $this->_tableName;
            }else{
                $tableName .= $this->_name;
            }
            $tableName .= !empty($this->_tableSuffix) ? $this->_tableSuffix : '';
            $this->_trueTableName = strtolower($tableName);
        }
        return $this->_trueTableName;
    }

    /**
     * 返回最后插入的ID
     *
     * @return string
     */
    public function getLastInsID()
    {
        return $this->_db->lastInsID;
    }

    /**
     *
     * 返回最后执行的sql语句
     *
     * @return string
     */
    public function getLastSql()
    {
        return $this->_db->getLastSql();
    }

    /**
     * 获取主键名称
     *
     * @return string
     */
    public function getPk()
    {
        return isset($this->_fields['_pk']) ? $this->_fields['_pk'] : $this->_pk;
    }

    /**
     * 获取数据表字段信息
     *
     * @return array
     */
    public function getDbFields()
    {
        return $this->_fields;
    }

    /**
     * 加载Model
     *
     * @param  string $modelName
     * @param  array  $dbconfig
     * @param  string $application
     * @return Lark_Model
     */
    public function loadModel($modelName, $application='', $param=array(), $dbConfig=array())
    {
        return Loader::loadModel($modelName, $application, $param, $dbConfig);
    }

    /**
     * 获取数据对象的值
     *
     * @param  string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : null;
    }

    /**
     * 设置数据对象的值
     *
     * @param  string $name 名称
     * @param  mixed $value 值
     * @return void
     */
    public function __set($name,$value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * 销毁数据对象的值
     *
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    /**
     * 检测数据对象的值
     *
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     *
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        if(in_array(strtolower($method), array('field', 'table', 'where', 'order', 'limit', 'page', 'having', 'group', 'lock', 'distinct'), true)) {
            // 连贯操作的实现
            $this->_options[strtolower($method)] = $args[0];
            return $this;
        }elseif(in_array(strtolower($method), array('count','sum','min', 'max','avg'), true)){
            // 统计查询的实现
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ') AS lark_' . $method);
        }elseif(strtolower(substr($method, 0,5)) == 'getby') {
            // 根据某个字段获取记录
            $field = substr($method, 5);
            $options['where'] = $field . '=\'' . $args[0] . '\'';
            return $this->find($options);
        }elseif(strtolower($method) == 'join'){
            $this->_options[strtolower($method)][] = $args[0];
            return $this;
        }else{
            throw new LarkException(__CLASS__ . ':' . $method . '方法不存在');
            return;
        }
    }

}
