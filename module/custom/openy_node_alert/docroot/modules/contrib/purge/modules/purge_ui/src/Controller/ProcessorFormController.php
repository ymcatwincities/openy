<?php

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;

/**
 * Controller for processor configuration forms.
 */
class ProcessorFormController extends ControllerBase {

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * Construct the ProcessorFormController.
   *
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors registry.
   */
  public function __construct(ProcessorsServiceInterface $purge_processors) {
    $this->purgeProcessors = $purge_processors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.processors'));
  }

  /**
   * Render the processor add form.
   *
   * @return array
   */
  public function addForm() {
    if (count($this->purgeProcessors->getPluginsAvailable())) {
      return $this->formBuilder()->getForm("Drupal\purge_ui\Form\ProcessorAddForm");
    }
    throw new NotFoundHttpException();
  }

  /**
   * Render the processor configuration form.
   *
   * @param string $id
   *   The plugin id of the processor to retrieve.
   * @param bool $dialog
   *   Determines if the modal dialog variant of the form should be rendered.
   *
   * @return array
   */
  public function configForm($id, $dialog) {
    if ($this->purgeProcessors->isPluginEnabled($id)) {
      $definition = $this->purgeProcessors->getPlugins()[$id];
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
   *   The plugin id of the processor to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function configFormTitle($id) {
    if ($this->purgeProcessors->isPluginEnabled($id)) {
      $definition = $this->purgeProcessors->getPlugins()[$id];
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        return $this->t('Configure @label', ['@label' => $definition['label']]);
      }
    }
    return $this->t('Configure');
  }

  /**
   * Render the processor delete form.
   *
   * @param string $id
   *   The plugin id of the processor to retrieve.
   *
   * @return array
   */
  public function deleteForm($id) {
    if ($this->purgeProcessors->isPluginEnabled($id)) {
      return $this->formBuilder()->getForm("\Drupal\purge_ui\Form\ProcessorDeleteForm", $id);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The plugin id of the processor to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function deleteFormTitle($id) {
    if ($this->purgeProcessors->isPluginEnabled($id)) {
      $label = $this->purgeProcessors->getPlugins()[$id]['label'];
      return $this->t('Delete @label', ['@label' => $label]);
    }
    return $this->t('Delete');
  }

  /**
   * Render the processor detail form.
   *
   * @param string $id
   *   The plugin id of the processor to retrieve.
   *
   * @return array
   */
  public function detailForm($id) {
    if ($this->purgeProcessors->isPluginEnabled($id)) {
      return $this->formBuilder()->getForm(
        "\Drupal\purge_ui\Form\PluginDetailsForm",
        ['details' => $this->purgeProcessors->getPlugins()[$id]['description']]
      );
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   The plugin id of the processor to retrieve.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function detailFormTitle($id) {
    return $this->purgeProcessors->getPlugins()[$id]['label'];
  }

}
