<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Functional;

/**
 * Class NodeContextTest.
 *
 * @package Drupal\Tests\TqExtension\Functional
 */
class NodeContextTest extends BehatTest
{
    /**
     * @covers \Drupal\TqExtension\Utils\BaseEntity::entityUrl
     * @covers \Drupal\TqExtension\Context\Node\NodeContext::visitPage
     * @covers \Drupal\TqExtension\Context\Redirect\RedirectContext::visitPage
     */
    public function test()
    {
        $this->runFeaturesGroup('NodeContext');
    }
}
