<?php
/**
 * @file
 * Contains \Drupal\entity_browser\Wizard\EntityBrowserWizard.
 */
namespace Drupal\entity_browser\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\entity_browser\Form\DisplayConfig;
use Drupal\entity_browser\Form\GeneralInfoConfig;
use Drupal\entity_browser\Form\SelectionDisplayConfig;
use Drupal\entity_browser\Form\WidgetsConfig;
use Drupal\entity_browser\Form\WidgetSelectorConfig;

/**
 * Custom form wizard for entity browser configuration.
 */
class EntityBrowserWizard extends EntityFormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Entity browser');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Label');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'entity_browser';
  }

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return 'Drupal\entity_browser\Entity\EntityBrowser::load';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    return [
      'general' => [
        'title' => $this->t('General information'),
        'form' => GeneralInfoConfig::class,
      ],
      'display' => [
        'title' => $this->t('Display'),
        'form' => DisplayConfig::class,
      ],
      'widget_selector' => [
        'title' => $this->t('Widget selector'),
        'form' => WidgetSelectorConfig::class,
      ],
      'selection_display' => [
        'title' => $this->t('Selection display'),
        'form' => SelectionDisplayConfig::class,
      ],
      'widgets' => [
        'title' => $this->t('Widgets'),
        'form' => WidgetsConfig::class,
      ],
    ];
  }

}
