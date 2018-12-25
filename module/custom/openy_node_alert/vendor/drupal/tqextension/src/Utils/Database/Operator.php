<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Database;

// Helpers.
use Behat\DebugExtension\Debugger;

/**
 * Class Operator.
 *
 * @package Drupal\TqExtension\Utils\Database
 */
class Operator
{
    use Debugger;

    /**
     * MySQL and MySQLDump login arguments.
     *
     * @var string
     */
    private $credentials = '';

    /**
     * Operator constructor.
     *
     * @param string $username
     *   The name of MySQL user.
     * @param string $password
     *   Password from an account of MySQL user.
     * @param string $host
     *   MySQL host address.
     * @param string $port
     *   Port of the MySQL host.
     */
    public function __construct($username, $password = '', $host = '', $port = '')
    {
        // -u%s -p%s -h%s -P%s
        $credentials = [];

        foreach ([
            'u' => $username,
            'p' => $password,
            'h' => $host,
            'P' => $port,
        ] as $parameter => $value) {
            if (!empty($value)) {
                $credentials[] = "-$parameter$value";
            }
        }

        $this->credentials = implode(' ', $credentials);
    }

    /**
     * @param string $name
     *   Name of the database to check.
     *
     * @return bool
     *   Checking state.
     */
    public function exist($name)
    {
        return !empty($this->exec("mysql -e 'show databases' | grep '^$name$'"));
    }

    /**
     * @param string $name
     *   Name of the database to create.
     */
    public function create($name)
    {
        if (!$this->exist($name)) {
            $this->exec("mysql -e '%s database $name;'", __FUNCTION__);
        }
    }

    /**
     * @param string $name
     *   Name of the database to drop.
     */
    public function drop($name)
    {
        if ($this->exist($name)) {
            $this->exec("mysql -e '%s database $name;'", __FUNCTION__);
        }
    }

    /**
     * @param string $source
     *   Source DB name.
     * @param string $destination
     *   Name of the new DB.
     */
    public function copy($source, $destination)
    {
        $this->clear($destination);
        $this->exec("mysqldump $source | mysql $destination");
    }

    /**
     * @param string $name
     *   Name of the DB.
     */
    public function clear($name)
    {
        $this->drop($name);
        $this->create($name);
    }

    /**
     * Executes a shell command.
     *
     * @param string $command
     *   Command to execute.
     *
     * @return string
     *   Result of a shell command.
     */
    private function exec($command)
    {
        // Adding credentials after "mysql" and "mysqldump" commands.
        $command = preg_replace(
            '/(mysql(?:dump)?)/',
            "\\1 $this->credentials",
            vsprintf($command, array_slice(func_get_args(), 1))
        );

        self::debug(['%s'], [$command]);

        return trim(shell_exec($command));
    }
}
