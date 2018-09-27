<?php
/**
 * 初始化的配置
 * @date     2018/08/08 15:40
 */
require __DIR__ . '/../library/Db.php';
require __DIR__ . '/../library/phpQuery/phpQuery.php';
require __DIR__ . '/../library/phpExcel/Excel.php';

class Bootstrap
{
    private $_config;

    public function __construct()
    {
        date_default_timezone_set('PRC'); // set china timezone
        $this->_config = parse_ini_file("../config/app.ini", true);
    }

    /**
     * @param null $database
     * @return Db
     */
    public function db($database = null)
    {
        $config = $this->_config['MySQL'];
        if ($database) {
            $config['database'] = $database;
        }
        return new Db($config);
    }
}