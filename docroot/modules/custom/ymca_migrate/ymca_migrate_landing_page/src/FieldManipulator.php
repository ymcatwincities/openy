<?php

namespace Drupal\ymca_migrate_landing_page;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class FieldManipulator.
 *
 * @package Drupal\ymca_migrate_landing_page
 */
class FieldManipulator {

  /**
   * Replace old [YGTC] Secondary Description and Sidebar with OpenY one.
   */
  public function fixHeaderSecondaryDescription() {
    $paragraphQuery = \Drupal::entityQuery('paragraph');
    $legacyParagraphs = $paragraphQuery
      ->condition('type', '70_30_columns')
      ->execute();

    $nodeQuery = \Drupal::entityQuery('node');
    $ladingPageIds = $nodeQuery
      ->condition('type', 'landing_page')
      ->condition('field_header_content.target_id', array_values($legacyParagraphs), 'IN')
      ->execute();

    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $paragraphStorage = \Drupal::entityTypeManager()->getStorage('paragraph');

    $color = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Grey']);
    $color = reset($color);

    foreach ($ladingPageIds as $pageRevisionId => $pageId) {
      $pageEntity = $nodeStorage->loadRevision($pageRevisionId);

      /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $headerItems */
      $headerItems = $pageEntity->get('field_header_content');
      /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
      foreach ($headerItems as $index => $item) {
        $paragraphEntity = $paragraphStorage->loadRevision($item->target_revision_id);

        // Set grey color.
        if ($paragraphEntity->bundle() == 'small_banner') {
          try {
            $paragraphEntity->set('field_prgf_color', $color->tid);
            $paragraphEntity->save();
          }
          catch (\Exception $e) {
            watchdog_exception('ymca_master', $e, 'Failed to save color for node :id', [':id' => $pageEntity->id()]);
          }
        }

        if ($paragraphEntity->bundle() == '70_30_columns') {
          $newParagraph = Paragraph::create(['type' => 'secondary_description_sidebar']);
          $newParagraph->set(
            'field_prgf_left_column_block',
            ['target_id' => $paragraphEntity->field_prgf_70_30_left->target_id]
          );
          $newParagraph->set(
            'field_prgf_right_column_block',
            ['target_id' => $paragraphEntity->field_prgf_70_30_right->target_id]
          );
          $newParagraph->save();
          $headerItems->appendItem($newParagraph);

          $headerItems->removeItem($index);

          try {
            $pageEntity->save();
          }
          catch (\Exception $e) {
            watchdog_exception('ymca_master', $e, 'Failed to update header for node :id', [':id' => $pageEntity->id()]);
          }

          \Drupal::logger('ymca_master')->info(sprintf('Header for node %d has been updated.', $pageEntity->id()));
        }
      }
    }

  }

}
