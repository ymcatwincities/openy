<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector\Single.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector;

use Drupal\entity_browser\WidgetSelectorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays only first widget.
 *
 * @EntityBrowserWidgetSelector(
 *   id = "single",
 *   label = @Translation("Single widget"),
 *   description = @Translation("Displays first configured widget.")
 * )
 */
class Single extends WidgetSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface &$form_state) {
    return array();
  }

}
