<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils\Database;

use Drupal\TqExtension\Utils\Database;
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

/**
 * Class DatabaseTest.
 *
 * @package Drupal\Tests\TqExtension\Utils\Database
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    const CONNECTION = 'default';

    public function test()
    {
        $database = new Database\Database(self::CONNECTION);
        /** @var Database\Operator $operator */
        $operator = self::getObjectAttribute($database, 'db');

        $dbName = DrupalKernelPlaceholder::getDatabaseConnectionInfo(self::CONNECTION)['default']['database'];
        $dbCopy = "tqextension_$dbName";

        self::assertSame($dbName, self::getObjectAttribute($database, 'source'));
        self::assertSame($dbCopy, self::getObjectAttribute($database, 'temporary'));

        // Initially copy of the database should not exist.
        self::assertFalse($operator->exist($dbCopy));
        // Since database wasn't cloned.
        self::assertAttributeEquals(false, 'cloned', $database);
        // Create a copy of database.
        $database = clone $database;
        // Ensure that property was updated.
        self::assertAttributeEquals(true, 'cloned', $database);
        // Make sure that database was copied.
        self::assertTrue($operator->exist($dbCopy));
        // Remove temporary database (call destructor).
        $database = null;
        // Make sure that cloned database does not exist.
        self::assertFalse($operator->exist($dbCopy));
    }
}
