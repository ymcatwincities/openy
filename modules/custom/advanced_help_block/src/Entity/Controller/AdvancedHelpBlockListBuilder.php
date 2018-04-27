<?php

namespace Drupal\advanced_help_block\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for advanced_help_block entity.
 *
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Advanced Help Block implements a Help blocks filtered by url. These entities are fieldable entities. You can manage the fields on the <a href="@adminlink">Advanced Help Blocks admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('advanced_help_block.advanced_help_block_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Block Id');
    $header['title'] = $this->t('Title');
    $header['video'] = $this->t('Video');
    $header['url'] = $this->t('Url');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\advanced_help_block\Entity\AdvancedHelpBlock */
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['video'] = $entity->video->value;
    $row['url'] = $entity->url->value;
    return $row + parent::buildRow($entity);
  }

}
