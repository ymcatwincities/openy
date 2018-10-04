<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;
use Drupal\TqExtension\Utils\EntityDrupalWrapper;

/**
 * Class EntityDrupalWrapperTest.
 *
 * @package Drupal\Tests\TqExtension\Utils
 *
 * @coversDefaultClass \Drupal\TqExtension\Utils\EntityDrupalWrapper
 */
class EntityDrupalWrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityDrupalWrapper
     */
    private $target;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        list($id, $type, $bundle) = DrupalKernelPlaceholder::entityCreate('node', [
            'type' => 'article',
            'title' => 'Test node',
        ]);

        $this->target = new EntityDrupalWrapper($type, $bundle);
        $this->target->load($id);
    }

    /**
     * @covers ::load
     * @covers ::getEntity
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityCreate
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::getFieldDefinitions
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityLoad
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityHasField
     */
    public function testHasField()
    {
        static::assertTrue($this->target->hasField('field_tags'));
    }

    /**
     * @covers ::load
     * @covers ::getEntity
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityCreate
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::getFieldDefinitions
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityLoad
     * @covers \Drupal\TqExtension\Cores\DrupalKernelPlaceholder::entityFieldValue
     */
    public function testGetFieldValue()
    {
        static::assertSame('Test node', $this->target->getFieldValue('title'));
    }

    public function testGetFieldNameByLocator()
    {
        static::assertSame('field_tags', $this->target->getFieldNameByLocator('Tags'));
    }

    public function testGetRequiredFields()
    {
        $expected = [
            'type' => 'Content type',
            'title' => 'Title',
        ];

        if (DRUPAL_CORE > 7) {
            $expected['comment'] = 'Comments';
        } else {
            $expected['author'] = 'Author';
        }

        static::assertSame($expected, $this->target->getRequiredFields());
    }
}
