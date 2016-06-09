<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\CurrentUserContextTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\page_manager\EventSubscriber\CurrentUserContext;
use Drupal\user\UserInterface;
use Prophecy\Argument;

/**
 * Tests the current user context.
 *
 * @coversDefaultClass \Drupal\page_manager\EventSubscriber\CurrentUserContext
 *
 * @group PageManager
 */
class CurrentUserContextTest extends PageContextTestBase {

  /**
   * @covers ::onPageContext
   */
  public function testOnPageContext() {
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(1);
    $user = $this->prophesize(UserInterface::class);

    $data_definition = new DataDefinition(['type' => 'entity:user']);

    $this->typedDataManager->create($data_definition, $user)
      ->willReturn(EntityAdapter::createFromEntity($user->reveal()));

    $this->typedDataManager->getDefaultConstraints($data_definition)
      ->willReturn([]);

    $this->typedDataManager->createDataDefinition('entity:user')
      ->will(function () use ($data_definition) {
        return $data_definition;
      });

    $this->page->addContext('current_user', Argument::type(Context::class))->shouldBeCalled();

    $user_storage = $this->prophesize(EntityStorageInterface::class);
    $user_storage->load(1)->willReturn($user->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage->reveal());

    $route_param_context = new CurrentUserContext($account->reveal(), $entity_type_manager->reveal());
    $route_param_context->onPageContext($this->event);
  }

}
