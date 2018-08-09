<?php

namespace Drupal\openy_er\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
 * No dependency selection handler implementation for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default_no_dep:node",
 *   label = @Translation("Node selection (openy)"),
 *   entity_types = {"node"},
 *   group = "default_no_dep",
 *   weight = 1
 * )
 */
class NodeSelectionNoDependency extends NodeSelection {

  use SelectionNoDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form = $this->buildConfigurationFormAlter($form, $form_state);
    $form['target_bundles_no_dep']['#title'] = $this->t('Content types');
    return $form;
  }

}
