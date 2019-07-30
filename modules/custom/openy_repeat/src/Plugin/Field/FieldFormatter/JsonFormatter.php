<?php

namespace Drupal\openy_repeat\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Render\Markup;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'json' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_json",
 *   label = @Translation("Data exported in JSON format"),
 *   field_types = {
 *     "boolean",
 *     "entity_reference",
 *     "link"
 *   }
 * )
 */
class JsonFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays value in json for javascript.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $fieldName = $this->fieldDefinition->getName();

    $export = [];
    foreach ($items as $item) {
      if ($item instanceof EntityReferenceItem) {
        $export[] = [
          'id' => $item->entity->id(),
          'title' => $item->entity->label(),
          'url' => $item->entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
        ];
      }
      elseif ($item instanceof LinkItem) {
        $export[] = [
          'url' => $item->getUrl()->toString(),
        ];
      }
      else {
        $export[] =  $item->getValue();
      }
    }

    $js = '<script>
        window.OpenY = window.OpenY || {};
        window.OpenY.' . $fieldName . ' = ' . json_encode($export) . ';
    </script>';

    $elements = [[
      '#type' => 'inline_template',
      '#template' => '{{ variable|raw }}',
      '#context' => [
        'variable' => $js,
      ],
    ]];

    return $elements;
  }

}
