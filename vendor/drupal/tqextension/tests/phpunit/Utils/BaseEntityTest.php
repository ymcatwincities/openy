<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils;

use Drupal\TqExtension\Utils\BaseEntity;
use Drupal\Tests\TqExtension\TraitTest;

/**
 * Class BaseEntityTest.
 *
 * @package Drupal\Tests\TqExtension\Utils
 *
 * @property BaseEntity|\PHPUnit_Framework_MockObject_MockObject $target
 *
 * @coversDefaultClass \Drupal\TqExtension\Utils\BaseEntity
 */
class BaseEntityTest extends TraitTest
{
    const FQN = BaseEntity::class;

    /**
     * @covers ::entityType
     * @covers ::getIdByArguments
     * @covers ::entityUrl
     */
    public function testGetCurrentId()
    {
        $this->target
            ->expects(static::once())
            ->method('entityType')
            ->willReturn('test_entity_type');

        $this->target
          ->expects(static::once())
          ->method('getIdByArguments')
          ->withAnyParameters()
          ->willReturn(12);

        $this->assertSame('test_entity_type/12', $this->target->entityUrl('visit', 'dummy'));
    }
}
