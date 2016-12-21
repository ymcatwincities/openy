<?php

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * Controller for queuer configuration forms.
 */
class QueuerFormController extends ControllerBase {

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Construct the QueuerFormController.
   *
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   */
  public function __construct(QueuersServiceInterface $purge_queuers) {
    $this->purgeQueuers = $purge_queuers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.queuers'));
  }

  /**
   * Render the queuer add form.
   *
   * @return array
   */
  public function addForm() {
    if (count($this->purgeQueuers->getPluginsAvailable())) {
      return $this->formBuilder()->getForm("Drupal\purge_ui\Form\QueuerAddForm");
    }
    throw new NotFoundHttpException();
  }

  /**
   * Render the queuer configuration form.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   * @param bool $dialog
   *   Determines if the modal dialog variant of the form should be rendered.
   *
   * @return array
   */
  public function configForm($id, $dialog) {
    if ($this->purgeQueuers->isPluginEnabled($id)) {
      $definition = $this->purgeQueuers->getPlugins()[$id];
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        return $this->formBuilder()->getForm(
          $definition['configform'],
          [
            'id' => $id,
            'dialog' => $dialog,
          ]
        );
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function configFormTitle($id) {
    if ($this->purgeQueuers->isPluginEnabled($id)) {
      $definition = $this->purgeQueuers->getPlugins()[$id];
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        return $this->t('Configure @label', ['@label' => $definition['label']]);
      }
    }
    return $this->t('Configure');
  }

  /**
   * Render the queuer delete form.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   *
   * @return array
   */
  public function deleteForm($id) {
    if ($this->purgeQueuers->isPluginEnabled($id)) {
      return $this->formBuilder()->getForm("\Drupal\purge_ui\Form\QueuerDeleteForm", $id);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function deleteFormTitle($id) {
    if ($this->purgeQueuers->isPluginEnabled($id)) {
      $label = $this->purgeQueuers->getPlugins()[$id]['label'];
      return $this->t('Delete @label', ['@label' => $label]);
    }
    return $this->t('Delete');
  }

  /**
   * Render the queuer detail form.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   *
   * @return array
   */
  public function detailForm($id) {
    if ($this->purgeQueuers->isPluginEnabled($id)) {
      return $this->formBuilder()->getForm(
        "\Drupal\purge_ui\Form\PluginDetailsForm",
        ['details' => $this->purgeQueuers->getPlugins()[$id]['description']]
      );
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The plugin id of the queuer to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function detailFormTitle($id) {
    return $this->purgeQueuers->getPlugins()[$id]['label'];
  }

}
