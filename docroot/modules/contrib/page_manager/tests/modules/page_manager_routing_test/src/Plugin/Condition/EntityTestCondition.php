<?php

namespace Drupal\page_manager_routing_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * @todo.
 *
 * @Condition(
 *   id = "page_manager_routing_test__entity_test",
 *   label = @Translation("Entity Test"),
 *   context = {
 *     "entity_test" = @ContextDefinition("entity:entity_test")
 *   }
 * )
 */
class EntityTestCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return (bool) $this->getContext('entity_test');
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return '';
  }

}
