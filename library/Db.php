<?php
/**
 * 简单封装MySQL类
 * @package PDO
 */

class Db
{
    public static $config = array();         //设置连接参数，配置信息
    public static $link = NULL;              //保存连接标识符
    public static $pconnect = FALSE;         //是否开启长连接
    public static $dbVersion = NULL;         //保存数据版本库
    public static $connected = FALSE;        //是否连接成功
    public static $PDOStatement = NULL;      //保存PDOStatement对象
    public static $queryStr = NULL;          //保存最后的执行操作
    public static $error = NULL;             //保存错误信息
    public static $lastInsertId = NULL;      //最后插入数据的id
    public static $numRows = NULL;           //上一步操作受影响记录的条数

    /**
     * 构造函数,连接PDO
     * @param $dbConfig = array(
     *              'dbtype' => 'mysql',
     *              'hostname' => localhost,
     *              'username' => root,
     *              'password' => root,
     *              'database' => db,
     *              'hostport' => 3306,
     *              'charset' => utf8
     *          );
     * @return boolean //返回连接状态，连接成功为true，否则为false
     */
    public function __construct($dbConfig)
    {
        if (!class_exists("PDO")) {
            self::throw_exception("不支持PDO, 请先开启");
        }
        if (empty($dbConfig['hostname'])) {
            self::throw_exception('没有定义数据库配置，请先定义');
        }
        self::$config = $dbConfig;
        if (empty(self::$config['params'])) {
            self::$config['params'] = [];
        }
        if (!isset(self::$link)) {
            $configs = self::$config;
            if (self::$pconnect) {
                //开启长连接，添加到配置数组中
                $configs['params'][constant("PDO::ATTR_PERSISTENT")] = 'true';
            }
            try {
                $dbtype = isset($dbConfig['dbtype']) ? $dbConfig['dbtype'] : 'mysql';
                $hostname = isset($dbConfig['hostname']) ? $dbConfig['hostname'] : '127.0.0.1';
                $hostport = isset($dbConfig['hostport']) ? $dbConfig['hostport'] : 3306;
                $database = isset($dbConfig['database']) ? $dbConfig['database'] : 'mysql';
                $dsn = "{$dbtype}:host={$hostname};port={$hostport};dbname={$database}";
                self::$link = new PDO($dsn, $configs['username'], $configs['password'], $configs['params']);
            } catch (PDOException $e) {
                self::throw_exception($e->getMessage());
            }

            if (!self::$link) {
                self::throw_exception('PDO连接失败');
                return false;
            }
            self::$link->exec('SET NAMES ' . $configs['charset']);
            self::$dbVersion = self::$link->getAttribute(constant('PDO::ATTR_SERVER_VERSION'));
            self::$connected = true;
        }
        return true;
    }

    /**
     * 销毁$PDOStatement对象，释放结果集
     */
    public static function free()
    {
        self::$PDOStatement = null;
    }

    /**
     * 执行一条sql操作,采用prepare-execute形式
     * @param string $sql //即将执行的sql语句
     * @return boolean      //执行成功，返回PDOStatement状态的结果，否则为false
     */
    public static function query($sql = '')
    {
        $link = self::$link;
        if (!$link) {
            echo '不存在连接标识符';
            return false;
        }

        //判断之前是否有结果集，如果有，则先释放结果集
        if (!empty(self::$PDOStatement)) {
            self::free();
        }

        self::$queryStr = $sql;
        self::$PDOStatement = $link->prepare(self::$queryStr);
        $res = self::$PDOStatement->execute();
        self::haveErrorThrowException();
        return $res;
    }

    /**
     * 采用PDO::exec()函数实现对数据的增删改操作，返回受上一步操作影响的记录条数
     * @param string $sql //即将执行的sql语句
     * @return boolean|int  //操作成功，返回受影响的记录条数，否则为false
     */
    public static function exec($sql = null)
    {
        $link = self::$link;
        if (!$link) {
            echo '不存在连接标识符';
            return false;
        }
        self::$queryStr = $sql;
        if (!empty(self::$PDOStatement)) {
            self::free();
        }
        $res = $link->exec(self::$queryStr);
        self::haveErrorThrowException();
        if ($res) {
            self::$lastInsertId = self::$link->lastInsertId();
            self::$numRows = $res;
            return self::$numRows;
        } else {
            return false;
        }
    }

    /**
     * 根据主键id查询记录
     * @param string $tabName //表名
     * @param string $priId //主键id
     * @param string $fields //要查询的表中字段，可能为矩阵或字符串
     * @return mixed            //执行成功返回一条结果
     */
    public static function findById($tabName, $priId, $fields = '*')
    {
        $sql = "SELECT %s FROM %s WHERE id=%d";  //%s表示该处输入的值是字符串类型，%d则表示整型
        //将后面三个参数按顺序插入到sql语句有%s和%d的位置中
        return self::getOne(sprintf($sql, self::parseFields($fields), $tabName, $priId));
    }

    /**
     * 获取一条记录
     * @param string $fields //需要查询的表中字段，字符串或矩阵形式
     * @param string $tables //需要查询的表，字符串形式
     * @param string $where //可能存在的where条件,字符串形式
     * @return mixed <mix, multitype:>
     */
    public static function findOne($tables, $fields = '*', $where = null)
    {
        $sql = 'SELECT ' . self::parseFields($fields) . ' FROM ' . $tables . self::parseWhere($where);
        $dataOne = self::getOne($sql);
        return $dataOne;
    }

    /**
     * 获取查询记录的总数
     * @param string $fields //需要查询的表中字段，字符串或矩阵形式
     * @param string $tables //需要查询的表，字符串形式
     * @param string $where //可能存在的where条件,字符串形式
     * @return mixed <mix, multitype:>
     */
    public static function count($tables, $fields = '*', $where = null)
    {
        $sql = 'SELECT COUNT(' . $fields . ') AS count FROM ' . $tables . self::parseWhere($where);
        $dataOne = self::getOne($sql);
        return $dataOne['count'];
    }

    /**
     * 执行普通select查询
     * @param string $fields //需要查询的表中字段，字符串或矩阵形式
     * @param string $tables //需要查询的表，字符串形式
     * @param string $where //可能存在的where条件,字符串形式
     * @param string $group //可能存在的group by条件，字符串或矩阵形式
     * @param string $having //可能存在的having条件，字符串形式
     * @param string $order //可能存在的order by条件，字符串或矩阵形式
     * @param string $limit //可能存在的limit条件，字符串或矩阵形式
     * @return mixed <mix, multitype:>
     */
    public static function find($tables, $fields = '*', $where = null, $group = null, $having = null, $order = null, $limit = null)
    {
        $sql = 'SELECT ' . self::parseFields($fields) . ' FROM ' . $tables
            . self::parseWhere($where)
            . self::parseGroup($group)
            . self::parseHaving($having)
            . self::parseOrder($order)
            . self::parseLimit($limit);
        $dataAll = self::getAll($sql);
        return $dataAll;
    }

    /**
     * 实现mysql的插入insert操作
     * @param array $dataArray //要插入的字段和字段值
     * @param string $table //要插入的表名
     * @return mixed <boolean, number>
     */
    public static function add($dataArray, $table)
    {
        $keys = array_keys($dataArray);
        array_walk($keys, array('Db', 'addSpecilChar'));
        $fieldsStr = join(',', $keys);
        $values = "'" . join("','", array_values($dataArray)) . "'";
        $sql = "INSERT INTO {$table} ({$fieldsStr}) VALUES ({$values})";
        return self::exec($sql);
    }

    /**
     * 实现mysql的修改update操作
     * @param array $dataArray //update时set后跟随的内容，矩阵形式
     * @param string $table //update的表
     * @param string $where //where条件
     * @param string $order //order by 条件
     * @param string $limit //limit 条件
     * @return mixed <boolean, number>
     */
    public static function update($dataArray, $table, $where = null, $order = null, $limit = null)
    {
        $sets = '';
        foreach ($dataArray as $key => $val) {
            if (is_array($val)) {
                // 函数表达式，无需引号
                if (isset($val['var'])) {
                    $sets .= $key . "={$val['var']},";
                }
            } else {
                $sets .= $key . "='{$val}',";
            }
        }
        $sets = rtrim($sets, ',');
        $sql = 'UPDATE ' . $table . ' SET ' . $sets
            . self::parseWhere($where)
            . self::parseOrder($order)
            . self::parseLimit($limit);
        return self::exec($sql);
    }

    /**
     * 实现mysql的删除delete操作
     * @param string $table //delete对应的表
     * @param string $where //where条件
     * @param string $order //order条件
     * @param number $limit //limit条件
     * @return mixed <boolean, number>
     */
    public static function delete($table, $where = null, $order = null, $limit = 0)
    {
        $sql = 'DELETE FROM ' . $table
            . self::parseWhere($where)
            . self::parseOrder($order)
            . self::parseLimit($limit);
        return self::exec($sql);
    }

    /**
     * 返回最后执行的一条sql语句
     * @return boolean|string
     */
    public static function getLastSql()
    {
        $link = self::$link;
        if (!$link) {
            echo '连接标识符不存在';
            return false;
        }
        return self::$queryStr;
    }

    /**
     * 得到上一步插入insert操作产生的id值
     * @return boolean|string
     */
    public static function getLastId()
    {
        $link = self::$link;
        if (!$link) {
            echo '连接标识符不存在';
            return false;
        }
        return self::$lastInsertId;
    }

    /**
     * 得到数据库的版本
     * @return boolean|mixed
     */
    public static function getDbVersion()
    {
        $link = self::$link;
        if (!$link) {
            echo '连接标识符不存在';
            return false;
        }
        return self::$dbVersion;
    }

    /**
     * 得到数据库中存在的表
     * @return array
     */
    public static function showTables()
    {
        $tables = array();
        if (self::query("SHOW TABLES")) {
            $res = self::getAll();
            foreach ($res as $key => $val) {
                $tables[$key] = current($val);
            }
        }
        return $tables;
    }

    /**
     * 解析Where条件
     * @param string $where //字符串形式
     * @return string   若输入的$where为空或不是字符串，则返回空，否则返回 WHERE+ $where
     */
    public static function parseWhere($where)
    {
        $whereStr = '';
        if (is_string($where) && !empty($where)) {
            //确保返回的where条件是字符串形式或为空
            $whereStr = $where;
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }


    /**
     * 解析分组Group BY 条件
     * @param string $group //字符串或矩阵形式
     * @return string
     */
    public static function parseGroup($group)
    {
        $groupStr = '';
        if (is_array($group)) {
            $groupStr = ' GROUP BY ' . implode(',', $group);
        } elseif (is_string($group) && !empty($group)) {
            $groupStr = ' GROUP BY ' . $group;
        }
        return empty($groupStr) ? '' : $groupStr;
    }

    /**
     * 对分组结构通过Having进行二次筛选
     * @param string $having //字符串形式
     * @return string
     */
    public static function parseHaving($having)
    {
        $havingStr = '';
        if (is_string($having) && !empty($having)) {
            $havingStr = ' HAVING ' . $having;
        }
        return empty($havingStr) ? '' : $havingStr;
    }

    /**
     * 解析Order By
     * @param string $order //字符串或矩阵形式
     * @return string
     */
    public static function parseOrder($order)
    {
        $orderStr = '';
        if (is_array($order)) {
            $orderStr = ' ORDER BY ' . implode(',', $order);
        } elseif (is_string($order) && !empty($order)) {
            $orderStr = ' ORDER BY ' . $order;
        }
        return empty($orderStr) ? '' : $orderStr;
    }

    /**
     * 解析限制条件Limit 的2中条件：例一， limit 1；例二，limit 1，3
     * @param string $limit //限制条件，可为字符串或矩阵形式
     * @return string
     */
    public static function parseLimit($limit)
    {
        $limitStr = '';
        if (is_array($limit)) {
            if (count($limit) > 1) {
                $limitStr = ' LIMIT ' . $limit[0] . ',' . $limit[1];
            } else {
                $limitStr = ' LIMIT ' . $limit[0];
            }
        } elseif (is_string($limit) && !empty($limit)) {
            $limitStr = ' LIMIT ' . $limit;
        }
        return $limitStr;
    }

    /**
     * 解析字段,使之成为符合规范的mysql字段内容
     * @param string $fields //要查询的表中字段，可能为矩阵或字符串
     * @return string           //返回解析后的表中字段（$fields）
     */
    public static function parseFields($fields)
    {
        if (is_array($fields)) {
            //类中加入回调函数callback，则需以如下形式引用：array(this,callback)或array('类名',callback)
            array_walk($fields, array('Db', 'addSpecilChar'));
            $fieldsStr = implode(',', $fields);   //将矩阵转换成字符串形式
        } elseif (is_string($fields) && !empty($fields)) {
            if (strpos($fields, '`') === false)   //字符串$fields中没有反引号'`'
            {
                $fields = explode(',', $fields);          //将字符串转换为矩阵形式
                array_walk($fields, array('Db', 'addSpecilChar'));
                $fieldsStr = implode(',', $fields);   //将矩阵转换成字符串形式
            } else {
                $fieldsStr = $fields;
            }
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    /**
     * 通过添加反引号'`'引用字段，避免与mysql保留的关键字产生冲突
     * @param string $value //要搜索的表中字段
     * @return string      //返回与mysql保留关键字区分的字段
     */
    public static function addSpecilChar(&$value)
    {
        if ($value === '*' || strpos($value, '.') !== false || strpos($value, '`') !== false || strpos($value, '(') !== false) {
            //字符串为'*'或字符串中存在'.'或'`'或'(',则不做任何处理
        } elseif (strpos($value, '`') === false) {
            $value = '`' . trim($value) . '`';    //移除字符串两端的空格并添加反引号'`'，一边与mysql中的保留字区分
        }
        return $value;
    }

    /**
     * 获取结果集中的一条数据
     * @param string $sql //即将执行的sql语句
     * @return mixed        //返回结果集中的一条数据
     */
    public static function getOne($sql = null)
    {
        if ($sql != null) {
            self::query($sql);
        }
        $result = self::$PDOStatement->fetch(constant('PDO::FETCH_ASSOC'));
        return $result;
    }

    /**
     * 获取所有结果集
     * @param string $sql //即将执行的sql语句
     * @return mixed      //返回结果集中的所有数据
     */
    public static function getAll($sql = null)
    {
        if ($sql != null) {
            self::query($sql);
        }
        $result = self::$PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
        return $result;
    }

    /**
     * PDO操作错误处理
     */
    public static function haveErrorThrowException()
    {
        $obj = empty(self::$PDOStatement) ? self::$link : self::$PDOStatement;
        $arrError = $obj->errorInfo();
        if ($arrError[0] != '00000') {
            self::$error = 'SQLSTATE:' . $arrError[0] . "<br /> SQL Error:" . $arrError[2] . "<br /> Error SQL:" . self::$queryStr;
            self::throw_exception(self::$error);
            return false;
        }
        if (self::$queryStr == '') {
            self::throw_exception('要执行的SQL语句为空');
            return false;
        }
        return true;
    }

    /**
     * 自定义错误处理
     * @param string $errMsg 错误信息
     * @throws
     */
    public static function throw_exception($errMsg)
    {
        throw new Exception($errMsg);
    }

    /**
     * 销毁连接对象，关闭数据库
     * @close
     */
    public static function close()
    {
        self::$link = null;
    }
}