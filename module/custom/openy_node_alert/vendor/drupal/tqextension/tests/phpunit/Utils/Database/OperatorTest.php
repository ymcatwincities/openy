<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils\Database;

use Drupal\TqExtension\Utils\Database\Operator;

/**
 * Class OperatorTest.
 *
 * @package Drupal\Tests\TqExtension\Utils\Database
 */
class OperatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Operator
     */
    private $db;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->db = new Operator(DRUPAL_DB_USER, DRUPAL_DB_PASS, DRUPAL_DB_HOST);
    }

    /**
     * @test
     */
    public function credentials()
    {
        self::assertAttributeEquals('-uroot -proot -haws.com -P4490', 'credentials', new Operator(
            'root',
            'root',
            'aws.com',
            '4490'
        ));

        self::assertAttributeEquals('-uBR0kEN -hlocalhost', 'credentials', new Operator(
            'BR0kEN',
            '',
            'localhost'
        ));
    }

    /**
     * @test
     */
    public function dbNotExist()
    {
        // Initially we have no database.
        self::assertFalse($this->db->exist('testdb'));
    }

    /**
     * @test
     */
    public function dbDrop()
    {
        $this->dbCreate();
        // Database was created in method from which this one depends.
        $this->db->drop('testdb');
        // Make sure that database does not exists after deleting.
        $this->dbNotExist();
    }

    /**
     * @test
     */
    public function dbCopy()
    {
        $this->dbCreate();
        // Database was created in method from which this one depends.
        $this->db->copy('testdb', 'testdb_copy');
        // Ensure that database was copied.
        self::assertTrue($this->db->exist('testdb_copy'));
        // Remove copied database.
        $this->db->drop('testdb_copy');
        // Make sure that copied database does not exists after deleting.
        self::assertFalse($this->db->exist('testdb_copy'));
    }

    /**
     * @test
     */
    public function dbClear()
    {
        $this->dbCreate();
        $this->db->clear('testdb');
        self::assertTrue($this->db->exist('testdb'));
        $this->dbDrop();
    }

    private function dbCreate()
    {
        $this->db->create('testdb');
        // Check that database exists.
        self::assertTrue($this->db->exist('testdb'));
    }
}
