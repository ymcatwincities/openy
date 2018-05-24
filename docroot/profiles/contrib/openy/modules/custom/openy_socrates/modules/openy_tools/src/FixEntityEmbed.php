<?php

namespace Drupal\openy_tools;

use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;

/**
 * Class FieldHelper.
 */
class FixEntityEmbed {

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * FixEntityEmbed constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   * Logger channel.
   */
  public function __construct(LoggerChannelInterface $loggerChannel) {
    $this->loggerChannel = $loggerChannel;
    $this->menuLinkStorage = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content');
    $this->fileStorage = \Drupal::entityTypeManager()
      ->getStorage('file');
  }

  /**
   * Get tables for replacement.
   *
   * @return array
   *   A list of tables & fields with embed_entity instances.
   */
  protected function getTables() {
    return [
      'node__field_lead_description' => ['field_lead_description_value'],
      'node__field_secondary_sidebar' => ['field_secondary_sidebar_value'],
      'node__field_sidebar' => ['field_sidebar_value'],
      'node__field_summary' => ['field_summary_value'],
      'node__field_ygtc_content' => ['field_ygtc_content_value'],
      'paragraph_revision__field_prgf_description' => ['field_prgf_description_value'],
      'paragraph__field_prgf_description' => ['field_prgf_description_value'],
      'block_content_revision__body' => ['body_value'],
      'block_content_revision__field_block_content' => ['field_block_content_value'],
      'block_content_r__6de56f762b' => ['field_ygtc_content_date_between_value'],
      'block_content_r__d31902f689' => ['field_ygtc_content_date_before_value'],
      'block_content_r__df5185bd6d' => ['field_ygtc_content_date_end_value'],
      'block_content__body' => ['body_value'],
      'block_content__field_block_content' => ['field_block_content_value'],
      'block_content__field_ygtc_content_date_before' => ['field_ygtc_content_date_before_value'],
      'block_content__field_ygtc_content_date_between' => ['field_ygtc_content_date_between_value'],
      'block_content__field_ygtc_content_date_end' => ['field_ygtc_content_date_end_value'],
      'node_revision__field_lead_description' => ['field_lead_description_value'],
      'node_revision__field_secondary_sidebar' => ['field_secondary_sidebar_value'],
      'node_revision__field_sidebar' => ['field_sidebar_value'],
      'node_revision__field_summary' => ['field_summary_value'],
      'node_revision__field_ygtc_content' => ['field_ygtc_content_value'],
    ];
  }

  /**
   * Find media UUID by file UUID.
   *
   * @param string $uuid
   *   UUID.
   *
   * @return NULL|\Drupal\media_entity\Entity\Media
   *   Entity or NULL.
   */
  protected function findMediaUuidByFileUuid($uuid) {
    $entityTypeManager = \Drupal::entityTypeManager();
    $mediaStorage = $entityTypeManager->getStorage('media');
    $fileStorage = $entityTypeManager->getStorage('file');

    // Get File ID.
    $fileIds = $fileStorage->loadByProperties(['uuid' => $uuid]);

    if (empty($fileIds)) {
      return NULL;
    }

    $fileId = reset($fileIds)->id();

    // Try to find document.
    $mediaEntities = $mediaStorage->loadByProperties(
      [
        'field_media_document' => ['target_id' => $fileId]
      ]
    );

    if (!empty($mediaEntities)) {
      return reset($mediaEntities);
    }

    // Try to find image.
    $mediaEntities = $mediaStorage->loadByProperties(
      [
        'field_media_image' => ['target_id' => $fileId]
      ]
    );

    if (!empty($mediaEntities)) {
      return reset($mediaEntities);
    }

    // Try to find image.
    return NULL;
  }

  /**
   * Fix file.
   */
  public function fixFile() {
    $db = \Drupal::database();
    $tables = $this->getTables();

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition(
            't.' . $field,
            '%' . $db->escapeLike('drupal-entity-inline') . '%',
            'LIKE'
          )
          ->execute();

        while ($data = $result->fetchObject()) {
          $replace = [];

          preg_match_all(
            "/<drupal-entity-inline.*<\/drupal-entity-inline>/miU",
            $data->$field,
            $test
          );

          if (count($test[0])) {
            foreach ($test[0] as $drupalEntityInline) {

              // Check if there is more than one drupal-entity-inline with menu_link .
              preg_match_all("/data-entity-type=\"file\"/miU", $drupalEntityInline, $fail);
              if (count($fail[0]) >= 2) {
                $this->loggerChannel->error(sprintf('Failed to parse entities for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
                throw new \Exception('Regex is wrong');
              }
              else {
                if (count($fail[0]) == 0) {
                  continue;
                }
              }

              // Load entity properties via DOM.
              $dom = Html::load($drupalEntityInline);
              $xpath = new \DOMXPath($dom);
              foreach ($xpath->query(
                '//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]'
              ) as $node) {
                // Get real uuid from Media.
                $fileUuid = $node->getAttribute('data-entity-uuid');
                $fileDescription = $node->getAttribute('data-entity-embed-settings');
                $fileDescriptionData = json_decode($fileDescription, TRUE);
                $fileAlt = $node->getAttribute('alt');
                $dataCaption = '';
                $title = '';
                if (isset($fileDescriptionData['description'])) {
                  $dataCaption = ' data-caption="' . Html::escape($fileDescriptionData['description']) . '" ';
                  $title = Html::escape($fileDescriptionData['description']);
                }
                else {
                  if (isset($fileDescriptionData['file_title'])) {
                    // Create replacement for display "entity_reference:file_entity_reference_label_url"
                    $dataCaption = ' data-caption="' . Html::escape($fileDescriptionData['file_title']) . '" ';
                    $title = Html::escape($fileDescriptionData['file_title']);
                  }
                  else {
                    if ($fileAlt != '') {
                      // Create replacement for display "image:image"
                      $dataCaption = ' data-caption="' . $fileAlt . '" ';
                      $title = $fileAlt;
                    }
                  }
                }
                if ($dataCaption == '' && !(isset($fileDescriptionData['image_style']) && isset($fileDescriptionData['image_link']) && $fileDescriptionData['image_style'] == $fileDescriptionData['image_link'])) {
                  $this->loggerChannel->error(sprintf("Failed to find data-caption for old embed: %s for entity ID %d", $drupalEntityInline, $data->entity_id));
                }
                $mediaEntity = $this->findMediaUuidByFileUuid($fileUuid);

                if (!$mediaEntity) {
                  // If file was not found let's just replace abandoned embed with empty string.
                  $replacement = '';
                  $this->loggerChannel->info(sprintf("Failed to find Media entity by file UUID: %s", $fileUuid));
                  $abandoned[] = [
                    'table' => $table,
                    'field' => $field,
                    'entity_id' => $data->entity_id,
                    'uuid' => $fileUuid,
                  ];
                }
                else {
                  $media_types[$mediaEntity->bundle()][] = $mediaEntity;

                  $filesArray = $this->fileStorage->loadByProperties(['uuid' => $fileUuid]);
                  $alias = '';
                  if (!empty($filesArray)){
                    /** @var \Drupal\file_entity\Entity\FileEntity $file */
                    $file = reset($filesArray);
                    $absoluteUrl = \Drupal::service('ymca_entity_embed.link_finder')->getFileLinkByMediaUuid($mediaEntity->uuid());
                    if (!$absoluteUrl){
                      $replacement = '';
                      // Prepare replacement array.
                      $replace['from'][] = $drupalEntityInline;
                      $replace['to'][] = $replacement;
                      $abandoned[] = [
                        'table' => $table,
                        'field' => $field,
                        'entity_id' => $data->entity_id,
                        'uuid' => $fileUuid,
                      ];
                      continue;
                    }

                    $url = parse_url($absoluteUrl);
                    $alias = $url['path'];
                  }
                  $dataEntityTypeId = 'data-drupal-entity-type-id="file"';
                  $dataEntityUuid = $fileUuid ? 'data-drupal-entity-uuid="' . $fileUuid . '"' : '';
                  $replacement = '<a ' . $dataEntityTypeId . ' ' . $dataEntityUuid . '
                   href="' . $alias . '"
                   title="' . $title . '">' . htmlspecialchars_decode($title) . '</a>';

                  if ($mediaEntity->bundle() == 'image') {

                    $replacement = '<drupal-entity
                      data-embed-button="embed_image"' . $dataCaption . '
                      data-entity-embed-display="entity_reference:entity_reference_entity_view"
                      data-entity-embed-display-settings="{&quot;view_mode&quot;:&quot;embedded_full&quot;}"
                      data-entity-type="media"
                      data-entity-uuid="' . $mediaEntity->uuid() . '"></drupal-entity>';
                  }
                }

                // Prepare replacement array.
                $replace['from'][] = $drupalEntityInline;
                $replace['to'][] = $replacement;
              }
            }
          }

          // Replace all entities in the text.
          if ($replace) {
            $updated = str_replace($replace['from'], $replace['to'], $data->$field);
            $db->update($table)
              ->fields([
                $field => $updated,
              ])
              ->condition('entity_id', $data->entity_id)
              ->condition('revision_id', $data->revision_id)
              ->execute();

            $this->loggerChannel->info(sprintf('Fixed entity embed for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
          }
        }

      }
    }

    if (!empty($abandoned)) {
      $this->loggerChannel->info(sprintf("Found and cleared %d abandoned File UUIDs", count($abandoned)));
    }
  }

  /**
   * Fix menu_link entity.
   */
  public function fixMenuLink() {
    $db = \Drupal::database();
    $tables = $this->getTables();
    $abandoned = [];

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition(
            't.' . $field,
            '%' . $db->escapeLike('drupal-entity-inline') . '%',
            'LIKE'
          )
          ->execute();

        while ($data = $result->fetchObject()) {
          $replace = [];

          preg_match_all(
            "/<drupal-entity-inline.*<\/drupal-entity-inline>/miU",
            $data->$field,
            $test
          );

          if (count($test[0])) {
            foreach ($test[0] as $drupalEntityInline) {

              // Check if there is more than one drupal-entity-inline with menu_link .
              preg_match_all("/data-embed-button=\"menu_link\"/miU", $drupalEntityInline, $fail);
              if (count($fail[0]) >= 2) {
                $this->loggerChannel->error(sprintf('Failed to parse entities for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
                throw new \Exception('Regex is wrong');
              }
              else {
                if (count($fail[0]) == 0) {
                  continue;
                }
              }

              // Load entity properties via DOM.
              $dom = Html::load($drupalEntityInline);
              $xpath = new \DOMXPath($dom);
              foreach ($xpath->query(
                '//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]'
              ) as $node) {
                $uuid = $node->getAttribute('data-entity-uuid');
                $label = $node->getAttribute('data-entity-label');
                /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $referencedEntity */
                $referencedEntityArray = $this->menuLinkStorage->loadByProperties(['uuid' => $uuid]);
                if (empty($referencedEntityArray)) {
                  $abandoned[] = [
                    'table' => $table,
                    'field' => $field,
                    'entity_id' => $data->entity_id,
                    'uuid' => $uuid,
                  ];
                  $replacement = '';
                }
                else {
                  $entity_type = NULL;
                  $entityUuid = NULL;
                  /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $referencedEntity */
                  $referencedEntity = reset($referencedEntityArray);
                  $linkData = $referencedEntity->link->getValue();
                  $title = '';
                  $alias = '';
                  if ($linkData) {
                    $l = $linkData[0]['uri'];
                    if (strpos($l, 'entity:') !== FALSE) {
                      // We've found entity:node/ID.
                      $entityData = Url::fromUri($l)->getRouteParameters();
                      $entity_type = key($entityData);
                      $entity = \Drupal::entityTypeManager()
                        ->getStorage($entity_type)
                        ->load($entityData[$entity_type]);
                      $title = $entity->getTitle();
                      $alias = $entity->toUrl()->toString();
                      $entityUuid = $entity->uuid();
                    }
                    elseif (strpos($l, 'internal:') !== FALSE) {
                      $alias = Url::fromUri($l)->toString();
                    }
                  }
                  // Handling external links.
                  if ($l && !$alias) {
                    $alias = $l;
                  }
                  if ($alias == '') {
                    throw new \Exception(sprintf('Alias can"t be null. Possibly broken menu_link_content ID: %d', $referencedEntity->id()));
                  }
                  if ($label == 'Menu Link' && $title) {
                    $this->loggerChannel->info(sprintf('Detected wrong label %s for Menu Link %s', (string) $referencedEntity->uuid(), $title));
                    $label = $title;
                  }
                  if ($title == '') {
                    $title = $label;
                  }
                  $dataEntityTypeId = $entity_type ? 'data-drupal-entity-type-id="' . $entity_type . '"' : '';
                  $dataEntityUuid = $entityUuid ? 'data-drupal-entity-uuid="' . $entityUuid . '"' : '';
                  $replacement = '<a ' . $dataEntityTypeId . ' ' . $dataEntityUuid . '
                   href="' . $alias . '"
                   title="' . $title . '">' . htmlspecialchars_decode($label) . '</a>';
                }

                // Prepare replacement array.
                $replace['from'][] = $drupalEntityInline;
                $replace['to'][] = $replacement;
              }
            }
          }

          // Replace all entities in the text.
          if ($replace) {
            $updated = str_replace($replace['from'], $replace['to'], $data->$field);
            $db->update($table)
              ->fields([
                $field => $updated,
              ])
              ->condition('entity_id', $data->entity_id)
              ->condition('revision_id', $data->revision_id)
              ->execute();

            $this->loggerChannel->info(sprintf('Fixed entity embed for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
          }
        }

      }
    }
    if (!empty($abandoned)) {
      $this->loggerChannel->info(sprintf("Found and cleared %d abandoned File UUIDs", count($abandoned)));
    }
  }

  /**
   * Fix block content.
   */
  public function fixBlockContent() {
    $db = \Drupal::database();
    $tables = $this->getTables();

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition(
            't.' . $field,
            '%' . $db->escapeLike('drupal-entity-inline') . '%',
            'LIKE'
          )
          ->execute();

        while ($data = $result->fetchObject()) {
          $replace = [];

          preg_match_all(
            "/<drupal-entity-inline.*<\/drupal-entity-inline>/miU",
            $data->$field,
            $test
          );

          if (count($test[0])) {
            foreach ($test[0] as $drupalEntityInline) {

              // Check if there is more than one drupal-entity-inline with block_content.
              preg_match_all("/data-entity-type=\"block_content\"/miU", $drupalEntityInline, $fail);
              if (count($fail[0]) >= 2) {
                $this->loggerChannel->error(sprintf('Failed to parse entities for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
                throw new \Exception('Regex is wrong');
              }
              else {
                if (count($fail[0]) == 0) {
                  continue;
                }
              }

              // Load entity properties via DOM.
              $dom = Html::load($drupalEntityInline);
              $xpath = new \DOMXPath($dom);
              foreach ($xpath->query(
                '//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]'
              ) as $node) {
                $uuid = $node->getAttribute('data-entity-uuid');
                $align = $node->getAttribute('data-align');
                $replacement = '<drupal-entity
                  data-align="' . $align . '"
                  data-embed-button="block"
                  data-embed-button="embed_document"
                  data-entity-embed-display="entity_reference:entity_reference_entity_view"
                  data-entity-embed-display-settings="{&quot;view_mode&quot;:&quot;default&quot;}"
                  data-entity-type="block_content"
                  data-entity-uuid="' . $uuid . '"></drupal-entity>';

                // Prepare replacement array.
                $replace['from'][] = $drupalEntityInline;
                $replace['to'][] = $replacement;
              }
            }
          }

          // Replace all entities in the text.
          if ($replace) {
            $updated = str_replace($replace['from'], $replace['to'], $data->$field);
            $db->update($table)
              ->fields([
                $field => $updated,
              ])
              ->condition('entity_id', $data->entity_id)
              ->condition('revision_id', $data->revision_id)
              ->execute();

            $this->loggerChannel->info(sprintf('Fixed entity embed for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
          }
        }

      }
    }

  }

}
