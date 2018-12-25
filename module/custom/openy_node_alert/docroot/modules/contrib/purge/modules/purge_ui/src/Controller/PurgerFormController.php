<?php

namespace Drupal\purge_ui\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Controller for purger configuration forms.
 */
class PurgerFormController extends ControllerBase {

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Construct the PurgerFormController.
   *
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(PurgersServiceInterface $purge_purgers) {
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.purgers'));
  }

  /**
   * Render the purger add form.
   *
   * @return array
   */
  public function addForm() {
    if (count($this->purgePurgers->getPluginsAvailable())) {
      return $this->formBuilder()->getForm("Drupal\purge_ui\Form\PurgerAddForm");
    }
    throw new NotFoundHttpException();
  }

  /**
   * Retrieve the plugin definition for the given instance ID.
   *
   * @param string $id
   *   Unique instance ID for the purger instance requested.
   *
   * @return array|false
   *   The definition or FALSE when it doesn't exist.
   */
  protected function getPurgerPluginDefinition($id) {
    $enabled = $this->purgePurgers->getPluginsEnabled();
    if (!isset($enabled[$id])) {
      return FALSE;
    }
    return $this->purgePurgers->getPlugins()[$enabled[$id]];
  }

  /**
   * Retrieve the plugin definition for the given instance ID.
   *
   * @param string $id
   *   Unique instance ID for the purger instance requested.
   *
   * @return array|false
   *   The definition or FALSE when it doesn't exist.
   */
  protected function getPluginDefinition($id, $service) {
    $enabled = $this->purgePurgers->getPluginsEnabled();
    if (!isset($enabled[$id])) {
      return FALSE;
    }
    return $this->purgePurgers->getPlugins()[$enabled[$id]];
  }

  /**
   * Render the purger configuration form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   * @param bool $dialog
   *   Determines if the modal dialog variant of the form should be rendered.
   *
   * @return array
   */
  public function configForm($id, $dialog) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
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
   *   Unique instance ID for the purger instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function configFormTitle($id) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      if (isset($definition['configform']) && !empty($definition['configform'])) {
        $label = $this->purgePurgers->getLabels()[$id];
        return $this->t('Configure @label', ['@label' => $label]);
      }
    }
    return $this->t('Configure');
  }

  /**
   * Render the purger delete form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return array
   */
  public function deleteForm($id) {
    // Although it might look like a logic bug that we aren't checking whether
    // the ID exists and always return the form, this is a must. Else submitting
    // the form never works as the purger has been deleted before.
    if (!($definition = $this->getPurgerPluginDefinition($id))) {
      $definition = ['label' => ''];
    }
    return $this->formBuilder()->getForm(
      "\Drupal\purge_ui\Form\PurgerDeleteForm",
      ['id' => $id, 'definition' => $definition]
    );
  }

  /**
   * Render the purger detail form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return array
   */
  public function detailForm($id) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      return $this->formBuilder()->getForm(
        "\Drupal\purge_ui\Form\PluginDetailsForm",
        ['details' => $definition['description']]
      );
    }
    throw new NotFoundHttpException();
  }

  /**
   * Route title callback.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function detailFormTitle($id) {
    return $this->purgePurgers->getLabels()[$id];
  }

  /**
   * Render the purger move form.
   *
   * @param string $id
   *   Unique instance ID for the purger instance.
   * @param string $direction
   *   Either 'up' or 'down' are valid directions to move execution order in.
   *
   * @return array
   */
  public function moveForm($id, $direction) {
    if ($definition = $this->getPurgerPluginDefinition($id)) {
      if (in_array($direction, ['up', 'down'])) {
        return $this->formBuilder()->getForm(
          "\Drupal\purge_ui\Form\PurgerMoveForm",
          ['id' => $id, 'direction' => $direction, 'definition' => $definition]
        );
      }
    }
    throw new NotFoundHttpException();
  }

}
