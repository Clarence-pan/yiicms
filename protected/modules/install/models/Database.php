<?php
/**
 * Database.php class file.
 *
 * @author Ruslan Fadeev <fadeevr@gmail.com>
 * @link http://yiifad.ru/
 * @copyright 2012-2013 Ruslan Fadeev
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

class Database extends CFormModel
{
    public $dbType = 'mysql';
    public $host = 'localhost';
    public $username;
    public $password;
    public $dbName;
    public $tablePrefix = 'fad_';

    private $_dbExists = false;

    /**
     * @return array databases supported
     */
    public function dbSupported()
    {
        $dbSupported = array(
            'mysql'  => 'MySQL',
            'sqlite' => 'SQLite',
            'pgsql'  => 'PostgreSQL',
            (strncmp(PHP_OS, 'WIN', 3) === 0) ? 'mssql' : 'dblib' => 'MSSQL',
            'oci'    => 'Oracle Database'
        );
        foreach ($dbSupported as $key => $value) {
            if (!extension_loaded('pdo_' . $key)) {
                unset($dbSupported[$key]);
            } else if ($key == 'mssql') {
                if (!extension_loaded('pdo_sqlsrv')) {
                    unset($dbSupported['mssql']);
                }
            }
        }
        return $dbSupported;
    }

    public function rules()
    {
        return array(
            array('dbType, host, dbName, username', 'required'),
            array('tablePrefix, password', 'safe'),
        );
    }

    /**
     * Returns a list of behaviors that this model should behave as.
     * @return array the behavior configurations (behavior name=>behavior configuration)
     */
    public function behaviors()
    {
        return array(
            'behavior' => array(
                'class' => 'install.behaviors.InstallBehavior',
            )
        );
    }

    public function attributeLabels()
    {
        return array(
            'dbType'           => Yii::t('InstallModule.database', 'Database type'),
            'host'             => Yii::t('InstallModule.database', 'Database host'),
            'dbName'           => Yii::t('InstallModule.database', 'Database name'),
            'username'         => Yii::t('InstallModule.database', 'Database username'),
            'password'         => Yii::t('InstallModule.database', 'Database password'),
            'tablePrefix'      => Yii::t('InstallModule.database', 'Table tablePrefix')
        );
    }

    /**
     * @return array config for db.php
     */
    public function getDbConfig()
    {
        return array(
            'connectionString' => $this->getConnectionString(),
            'emulatePrepare'   => true,
            'username'         => $this->username,
            'password'         => $this->password,
            'charset'          => 'utf8',
            'tablePrefix'      => $this->tablePrefix
        );
    }

    /**
     * @param bool $dbExists
     * @return CDbConnection
     */
    public function createDbConnection($dbExists = true)
    {
        $this->_dbExists = $dbExists;
        $connection              = new CDbConnection($this->getConnectionString(), $this->username, $this->password);
        $connection->tablePrefix = $this->tablePrefix;
        $connection->initSQLs    = array("SET NAMES 'utf8' COLLATE 'utf8_general_ci';");
        $connection->init();
        return $connection;
    }

    /**
     * Try to create Db if not exists
     * @return bool|string
     */
    public function createDb()
    {
        try {
            $db = $this->createDbConnection(false);
            $db->createCommand("CREATE DATABASE IF NOT EXISTS `{$this->dbName}` CHARACTER SET utf8 COLLATE utf8_general_ci")->execute();
            $this->_dbExists = true;
            return true;
        } catch ( CDbException $e ) {
            return $e->errorInfo['2'] . PHP_EOL . $e->getMessage() . (YII_DEBUG ? PHP_EOL . $e->getTraceAsString() : '');
        }
    }

    /**
     * @return string ConnectionString for Database and db.php later
     */
    private function getConnectionString()
    {
        $dsn = array(
            'sqlite' => 'sqlite:' . $this->dbName,
            'mysql'  => 'mysql:host=' . $this->host . ';' . ($this->_dbExists ? 'dbname=' . $this->dbName : ''),
            'pgsql'  => 'pgsql:host=' . $this->host . ';port=5432;' . ($this->_dbExists ? 'dbname=' . $this->dbName : ''),
            'mssql'  => 'mssql:host=' . $this->host . ';' . ($this->_dbExists ? 'dbname=' . $this->dbName : ''),
            'oci'    => 'oci:dbname=//' . $this->host . ':1521/' . ($this->_dbExists ? 'dbname=' . $this->dbName : ''),
        );
        return $this->dbType == 'dblib' ? $dsn['mssql'] : $dsn[$this->dbType];
    }
}