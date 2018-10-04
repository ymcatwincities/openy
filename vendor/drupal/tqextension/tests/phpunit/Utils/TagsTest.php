<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils;

use Drupal\TqExtension\Utils\Tags;
use Drupal\Tests\TqExtension\TraitTest;

/**
 * Class TagsTest.
 *
 * @package Drupal\Tests\TqExtension\Utils
 *
 * @property Tags $target
 *
 * @coversDefaultClass \Drupal\TqExtension\Utils\Tags
 */
class TagsTest extends TraitTest
{
    const FQN = Tags::class;

    /**
     * @covers ::collectTags
     */
    public function testCollectTags()
    {
        $this->target->collectTags(['JavaScript', 'WYSIWYG', 'wysiwyg:CKEditor']);

        self::assertAttributeCount(2, 'tags', $this->target);
    }

    /**
     * @covers ::hasTag
     */
    public function testHasTag()
    {
        $this->testCollectTags();

        self::assertTrue($this->target->hasTag('javascript'));
        self::assertTrue($this->target->hasTag('wysiwyg'));

        self::assertFalse($this->target->hasTag('JavaScript'));
        self::assertFalse($this->target->hasTag('WYSIWYG'));
    }

    /**
     * @covers ::getTag
     */
    public function testGetTag()
    {
        $this->testCollectTags();

        self::assertSame('CKEditor', $this->target->getTag('wysiwyg'));
    }
}
