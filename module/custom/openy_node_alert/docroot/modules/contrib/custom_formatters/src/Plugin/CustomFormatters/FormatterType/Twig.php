<?php

namespace Drupal\custom_formatters\Plugin\CustomFormatters\FormatterType;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_formatters\FormatterTypeBase;
use Twig_Error;

/**
 * Plugin implementation of the Twig type.
 *
 * @FormatterType(
 *   id = "twig",
 *   label = "Twig",
 *   description = "A Twig based editor.",
 * )
 */
class Twig extends FormatterTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['data']['#description'] = $this->t('Enter the Twig code that will be evaluated.<br /><br /><strong>Available parameters:</strong><dl><dt><em><a href=":field_item_list_inerface" target="_blank">FieldItemListInterface</a></em> {{ items }}</dt><dd>The field values to be rendered.</dd><dt><em>string</em> {{ langcode }}</dt><dd>The language that should be used to render the field.</dd></dt></dl>', [
      ':field_item_list_inerface' => 'https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FieldItemListInterface.php/interface/FieldItemListInterface',
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = '';

    /** @var \Twig_Environment $twig_service */
    $twig_service = \Drupal::service('twig');

    try {
      $output = $twig_service->createTemplate($this->entity->data)->render([
        'items'    => $items,
        'langcode' => $langcode,
      ]);
    }
    catch (Twig_Error $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    return $output;
  }

}
