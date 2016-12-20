<?php

namespace Drupal\metatag;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Metatag defaults entities.
 */
class MetatagDefaultsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    // Move the Global defaults to the top.
    return ['global' => $entities['global']] + $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabelAndConfig($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    // Set the defaults that should not be deletable
    $protected_defaults = ['global', '403', '404', 'node', 'front', 'taxonomy_term', 'user'];

    // Global and entity defaults can be reverted but not deleted.
    if (in_array($entity->id(), $protected_defaults)) {
      unset($operations['delete']);
      $operations['revert'] = [
        'title' => t('Revert'),
        'weight' => $operations['edit']['weight'] + 1,
        'url' => $entity->toUrl('revert-form'),
      ];
    }

    return $operations;
  }

  /**
   * Renders the Metatag defaults label plus its configuration.
   *
   * @param EntityInterface $entity
   *   The Metatag defaults entity.
   *
   * @return
   *   Render array for a table cell.
   */
  public function getLabelAndConfig(EntityInterface $entity) {
    $output = '<div>';
    $prefix = '';
    $inherits = '';
    if ($entity->id() != 'global') {
      $prefix = '<div class="indentation"></div>';
      $inherits .= 'Global';
    }
    if (strpos($entity->id(), '__') !== FALSE) {
      $prefix .= '<div class="indentation"></div>';
      list($entity_label, $bundle_label) = explode(': ', $entity->get('label'));
      $inherits .= ', ' . $entity_label;
    }

    if (!empty($inherits)) {
      $output .= '<div><p>' . t('Inherits meta tags from: @inherits', ['@inherits' => $inherits]) . '</p></div>';
    }
    $tags = $entity->get('tags');
    if (count($tags)) {
      $output .= '<table>
                    <tbody>';
      foreach ($tags as $tag_id => $tag_value) {
        $output .= '<tr><td>' . $tag_id . ':</td><td>' . $tag_value . '</td></tr>';
      }
      $output .= '</tbody></table>';
    }

    $output .= '</div></div>';

    return [
      'data' => [
        '#type' => 'details',
        '#prefix' => $prefix,
        '#title' => $entity->label(),
        'config' => [
          '#markup' => $output,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['header'] = [
      '#markup' => '<p>' . t("Configure global meta tags below using the available field tokens. Fields must be added to the content type prior to meta tag configuration. This allows for custom or standard fields to be utilized as meta tags for more effective results. The standard meta tag field may be used if it has been added to the content type if needed, though this may require greater effort from content managers.") . '</p>'
        . '<p>' . t("If the top-level configuration is not specific enough, additional default meta tag configuration can be added to a specific node or taxonomy type.") . '</p>'
        . '<p>' . t("To view a summary of the default meta tags and the inheritance, click on a meta tag type. If you need to set metatags for a specific entity, edit its bundle and add the Metatag field.") . '</p>',
    ];
    return $build + parent::render();
  }

}
