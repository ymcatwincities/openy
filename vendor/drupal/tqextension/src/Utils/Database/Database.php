<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Database;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

/**
 * Class Database.
 *
 * @package Drupal\TqExtension\Utils\Database
 */
class Database
{
    /**
     * @var Operator
     */
    private $db;
    /**
     * Name of original database.
     *
     * @var string
     */
    private $source = '';
    /**
     * Name of temporary database that will store data from original.
     *
     * @var string
     */
    private $temporary = '';
    /**
     * Indicates that DB was cloned.
     *
     * @var bool
     */
    private $cloned = false;

    /**
     * @param string $connection
     *   Database connection name (key in $databases array from settings.php).
     */
    public function __construct($connection)
    {
        if (!defined('DRUPAL_ROOT')) {
            throw new \RuntimeException('Drupal is not bootstrapped.');
        }

        $database = DrupalKernelPlaceholder::getDatabaseConnectionInfo($connection);

        if (empty($database)) {
            throw new \InvalidArgumentException(sprintf('The "%s" database connection does not exist.', $connection));
        }

        $db = $database['default'];

        $this->db = new Operator($db['username'], $db['password'], $db['host'], $db['port']);
        $this->source = $db['database'];
        $this->temporary = "tqextension_$this->source";
    }

    /**
     * Clone a database.
     */
    public function __clone()
    {
        // Drop and create temporary DB and copy source into it.
        $this->db->copy($this->source, $this->temporary);
        $this->cloned = true;
    }

    /**
     * Restore original database.
     */
    public function __destruct()
    {
        if ($this->cloned) {
            // Drop and create source DB and copy temporary into it.
            $this->db->copy($this->temporary, $this->source);
            // Kill temporary DB.
            $this->db->drop($this->temporary);
        }
    }
}
