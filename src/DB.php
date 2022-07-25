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
     * Credentials options
     * @var string[]
     */
    private static array $defaultOptions = [
        'host' => 'mysql',
        'port' => '3306',
        'user' => 'mysql',
        'password' => 'mysql',
        'db' => 'db',
    ];

    /**
     * @param mixed[] $options
     */
    public static function setDefaultOptions(array $options): void {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }

    /**
     * DB constructor
     * @param array $options
     */
    public function __construct($options = []) {
        $loop = ReactAdapter::get();
        self::setDefaultOptions($options);

        $this->driver = new MysqlDriver($loop);
        $this->platform = new MySqlPlatform();
        $this->credentials = new Credentials(
            self::$defaultOptions['host'],
            self::$defaultOptions['port'],
            self::$defaultOptions['user'],
            self::$defaultOptions['password'],
            self::$defaultOptions['db']
        );

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
