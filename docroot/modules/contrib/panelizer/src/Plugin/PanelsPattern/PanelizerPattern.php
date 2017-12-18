<?php

namespace Drupal\panelizer\Plugin\PanelsPattern;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panels\Plugin\PanelsPattern\DefaultPattern;
use Drupal\user\SharedTempStoreFactory;

/**
 * @PanelsPattern("panelizer")
 */
class PanelizerPattern extends DefaultPattern {

  /**
   * {@inheritdoc}
   */
  public function getDefaultContexts(SharedTempStoreFactory $tempstore, $tempstore_id, $machine_name) {
    $contexts = [];
    list($entity_type, $bundle) = explode('__', $machine_name);
    $entity_definition = new ContextDefinition("entity:$entity_type", $this->t('Entity being panelized'));
    $contexts['@panelizer.entity_context:entity'] = new AutomaticContext($entity_definition);
    $user_definition = new ContextDefinition("entity:user", $this->t('Current user'));
    $contexts['current_user'] = new Context($user_definition);
    return $contexts + parent::getDefaultContexts($tempstore, $tempstore_id, $machine_name);
  }

}
