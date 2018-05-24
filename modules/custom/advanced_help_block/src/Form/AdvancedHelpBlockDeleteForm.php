<?php

namespace Drupal\advanced_help_block\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\logger_entity\Entity\LoggerEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a advanced_help_block entity.
 *
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * @var \Drupal\logger_entity\Entity\LoggerEntityInterface $logger;
   */
  private $logger;

  /**
   * AdvancedHelpBlockDeleteForm constructor.
   *
   * @param LoggerEntityInterface $logger
   * ConfigFactory.
   */
  public function __construct(LoggerEntityInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger')
    );
  }
  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return t('Are you sure you want delete this entity?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('view.advanced_help_blocks.ahb_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //this method is the submit handler for our form

    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('advanced_help_block')->notice(
      '@type: deleted %title.',
      array(
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->get->field_ahb_title->value,
      )
    );

    //redirect to the
    $form_state->setRedirect('view.advanced_help_blocks.ahb_list');
  }
}
