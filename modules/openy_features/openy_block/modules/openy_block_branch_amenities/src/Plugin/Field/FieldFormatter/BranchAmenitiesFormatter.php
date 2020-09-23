<?php

namespace Drupal\openy_block_branch_amenities\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'openy_branch_amenities' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_branch_amenities",
 *   label = @Translation("Display Amenities for Current Branch Page"),
 *   field_types = {
 *     "boolean",
 *     "entity_reference",
 *     "link"
 *   }
 * )
 */
class BranchAmenitiesFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays markup.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [[
        '#type' => 'markup',
        '#markup' => check_markup('[openy:list-branch-amenities]', 'full_html'),
      ],
    ];

    return $elements;
  }

}
