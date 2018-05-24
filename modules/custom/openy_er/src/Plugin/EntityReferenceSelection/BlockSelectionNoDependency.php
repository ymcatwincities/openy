<?php

namespace Drupal\openy_er\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
 * Provides block_content entity type specific selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "default_no_dep:block_content",
 *   label = @Translation("Block selection (openy)"),
 *   entity_types = {"block_content"},
 *   group = "default_no_dep",
 *   weight = 1
 * )
 */
class BlockSelectionNoDependency extends NodeSelection {

  use SelectionNoDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form = $this->buildConfigurationFormAlter($form, $form_state);
    return $form;
  }

}
