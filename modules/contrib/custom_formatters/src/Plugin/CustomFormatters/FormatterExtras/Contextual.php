<?php

namespace Drupal\custom_formatters\Plugin\CustomFormatters\FormatterExtras;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_formatters\FormatterExtrasBase;

define('CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_DISABLED', 0);
define('CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_ENABLED', 1);

/**
 * Contextual links optional integration plugin.
 *
 * @FormatterExtras(
 *   id = "contextual",
 *   label = "Contextual links",
 *   description = "Behaviour for Contextual links integration.",
 *   dependencies = {
 *     "module" = {
 *       "contextual"
 *     }
 *   }
 * )
 */
class Contextual extends FormatterExtrasBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = [];

    $form['mode'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Mode'),
      '#options'       => [
        CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_DISABLED => $this->t('Disabled'),
        CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_ENABLED  => $this->t('Enabled'),
      ],
      '#default_value' => $this->entity->getThirdPartySetting('contextual', 'mode', CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_ENABLED),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSave(array $form, FormStateInterface $form_state) {
    $this->entity->setThirdPartySetting('contextual', 'mode', $form_state->getValues()['extras']['contextual']['mode']);
  }

  /**
   * {@inheritdoc}
   */
  public function formatterViewElementsAlter(array &$element) {
    if ($this->entity->getThirdPartySetting('contextual', 'mode', CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_ENABLED) == CUSTOM_FORMATTERS_EXTRAS_CONTEXTUAL_ENABLED) {
      $element[0] = ['markup' => $element[0]];
      $element[0]['contextual_links'] = [
        '#type' => 'contextual_links_placeholder',
        '#id'   => _contextual_links_to_id([
          'custom_formatters' => [
            'route_parameters' => ['formatter' => $this->entity->id()],
          ],
        ]),
      ];
      $element['#attributes']['class'][] = 'contextual-region';
    }
  }

}
