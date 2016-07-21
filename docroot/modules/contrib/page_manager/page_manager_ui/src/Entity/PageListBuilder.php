<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Entity\PageListBuilder.
 */

namespace Drupal\page_manager_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\page_manager\PageInterface;

/**
 * Provides a list builder for page entities.
 */
class PageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\page_manager\PageInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['path'] = $this->getPath($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit']['url'] = new Url('entity.page.edit_form', ['machine_name' => $entity->id(), 'step' => 'general']);

    return $operations;
  }

  /**
   * Gets the displayable path of a page entity.
   *
   * @param \Drupal\page_manager\PageInterface $entity
   *   The page entity.
   *
   * @return array|string
   *   The value of the path.
   */
  protected function getPath(PageInterface $entity) {
    // If the page is enabled and not dynamic, show the path as a link,
    // otherwise as plain text.
    $path = $entity->getPath();
    if ($entity->status() && strpos($path, '%') === FALSE) {
      return [
        'data' => [
          '#type' => 'link',
          '#url' => Url::fromUserInput(rtrim($path, '/')),
          '#title' => $path,
        ],
      ];
    }
    else {
      return $path;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are currently no pages. <a href=":url">Add a new page.</a>', [':url' => Url::fromRoute('entity.page.add_form')->toString()]);
    return $build;
  }

}
