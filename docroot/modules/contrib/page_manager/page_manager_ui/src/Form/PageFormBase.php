<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\PageFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for editing and adding a page entity.
 */
abstract class PageFormBase extends EntityForm {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $entity;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Construct a new PageFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this page.'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#maxlength' => '255',
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getPath(),
      '#required' => TRUE,
      '#element_validate' => [[$this, 'validatePath']],
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the page entity already exists.
   *
   * @param string $id
   *   The page entity ID.
   *
   * @return bool
   *   TRUE if the format exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) $this->entityQuery->get('page')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validatePath(&$element, FormStateInterface $form_state) {
    // Ensure the path has a leading slash.
    $value = '/' . trim($element['#value'], '/');
    $form_state->setValueForElement($element, $value);

    // Ensure each path is unique.
    $path = $this->entityQuery->get('page')
      ->condition('path', $value)
      ->condition('id', $form_state->getValue('id'), '<>')
      ->execute();
    if ($path) {
      $form_state->setErrorByName('path', $this->t('The page path must be unique.'));
    }
  }

}
