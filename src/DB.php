<?php
namespace RatchetRevoltExporter;

use Amp\ReactAdapter\ReactAdapter;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\SingleConnection;

class DB {
    /**
     * Singleton instance
     * @var DB|null
     */
    private static ?DB $_instance = null;

    /**
     * DataBase Abstract Layer Connection
     * @var Connection
     */

    private Connection $conn;
    /**
     * @var MysqlDriver
     */

    private MysqlDriver $driver;

    /**
     * @var MySQLPlatform
     */
    private MySQLPlatform $platform;

    /**
     * @var Credentials
     */
    private Credentials $credentials;

    /**
     * DB constructor
     */
    public function __construct() {
        $loop = ReactAdapter::get();

        $this->driver = new MysqlDriver($loop);
        $this->platform = new MySqlPlatform();
        $this->credentials = new Credentials('mysql', '3306', 'mysql', 'mysql', 'db');

        $this->conn = SingleConnection::createConnected($this->driver, $this->credentials, $this->platform);

        self::$_instance = $this;
    }

    /**
     * @return DB
     */
    public static function getInstance(): DB {
        if (self::$_instance != null) {
            return self::$_instance;
        }
        return new self();
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection {
        return $this->conn;
    }

    /**
     * @return MySQLPlatform
     */
    public function getPlatform(): MySQLPlatform {
        return $this->platform;
    }
}
