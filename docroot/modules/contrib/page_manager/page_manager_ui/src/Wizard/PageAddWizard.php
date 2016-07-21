<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Wizard\PageAddWizard.
 */

namespace Drupal\page_manager_ui\Wizard;

use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\ctools\Plugin\PluginWizardInterface;
use Drupal\page_manager_ui\Form\PageVariantContextsForm;
use Drupal\page_manager_ui\Form\PageVariantConfigureForm;
use Drupal\page_manager_ui\Form\PageVariantSelectionForm;

class PageAddWizard extends PageWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'entity.page.add_step_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = parent::getOperations($cached_values);

    // Add steps for selection and creating the first variant.
    $operations['contexts'] = [
      'title' => $this->t('Contexts'),
      'form' => PageVariantContextsForm::class,
    ];
    $operations['selection'] = [
      'title' => $this->t('Selection criteria'),
      'form' => PageVariantSelectionForm::class,
    ];
    $operations['display_variant'] = [
      'title' => $this->t('Configure variant'),
      'form' => PageVariantConfigureForm::class,
    ];

    // Hide the Parameters step if there aren't any path parameters.
    if (isset($cached_values['page']) && !$cached_values['page']->getParameterNames()) {
      unset($operations['parameters']);
    }

    // Hide any optional steps that aren't selected.
    $optional_steps = ['access', 'contexts', 'selection'];
    foreach ($optional_steps as $step_name) {
      if (empty($cached_values['wizard_options'][$step_name])) {
        unset($operations[$step_name]);
      }
    }

    // Add any wizard operations from the plugin itself.
    if (!empty($cached_values['page_variant'])) {
      /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
      $page_variant = $cached_values['page_variant'];
      $variant_plugin = $page_variant->getVariantPlugin();
      if ($variant_plugin instanceof PluginWizardInterface) {
        if ($variant_plugin instanceof ContextAwareVariantInterface) {
          $variant_plugin->setContexts($page_variant->getContexts());
        }
        $cached_values['plugin'] = $variant_plugin;
        foreach ($variant_plugin->getWizardOperations($cached_values) as $name => $operation) {
          $operation['values']['plugin'] = $variant_plugin;
          $operation['submit'][] = '::submitVariantStep';
          $operations[$name] = $operation;
        }
      }
    }

    return $operations;
  }

}
