<?php

namespace Drupal\custom_formatters\Plugin\CustomFormatters\FormatterType;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_formatters\FormatterTypeBase;

/**
 * Plugin implementation of the PHP Formatter type.
 *
 * @FormatterType(
 *   id = "php",
 *   label = "PHP",
 *   description = "A PHP based editor with support for multiple fields and multiple values.",
 *   multipleFields = "true"
 * )
 */
class PHP extends FormatterTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['data']['#description'] = $this->t('Enter the PHP code that will be evaluated. You should NOT include %php tags.<br /><br /><strong>Available parameters:</strong><dl><dt><em><a href=":field_item_list_inerface" target="_blank">FieldItemListInterface</a></em> $items</dt><dd>The field values to be rendered.</dd><dt><em>string</em> $langcode</dt><dd>The language that should be used to render the field.</dd></dt></dl>', [
      '%php'                      => '<?php ?>',
      ':field_item_list_inerface' => 'https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FieldItemListInterface.php/interface/FieldItemListInterface',
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    ob_start();
    $output = eval($this->entity->get('data'));
    $output = !empty($output) ? $output : ob_get_contents();
    ob_end_clean();

    // Preview debugging; Show the available variables data.
    // @TODO - Re-add when preview functionality re-added.
    //if (\Drupal::moduleHandler()->moduleExists('devel') && isset($formatter->preview) && $formatter->preview['options']['dpm']['vars']) {
    //  dpm($variables);
    //}

    return empty($output) ? FALSE : $output;
  }

}
