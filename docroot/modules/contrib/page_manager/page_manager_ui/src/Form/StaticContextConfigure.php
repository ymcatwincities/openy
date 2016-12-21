<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\StaticContextConfigure.
 */

namespace Drupal\page_manager_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\ctools\Form\ContextConfigure;

class StaticContextConfigure extends ContextConfigure {

  /**
   * The machine-name of the variant.
   *
   * @var string
   */
  protected $variantMachineName;

  /**
   * Get the page variant.
   *
   * @param array $cached_values
   *   The cached values from the wizard.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function getPageVariant($cached_values) {
    if (isset($cached_values['page_variant'])) {
      return $cached_values['page_variant'];
    }

    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];
    return $page->getVariant($this->variantMachineName);
  }

  /**
   * {@inheritdoc}
   */
  protected function getParentRouteInfo($cached_values) {
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = $cached_values['page'];

    if ($page->isNew()) {
      return ['entity.page.add_step_form', [
        'machine_name' => $this->machine_name,
        'step' => 'contexts',
      ]];
    }
    else {
      $page_variant = $this->getPageVariant($cached_values);
      return ['entity.page.edit_form', [
        'machine_name' => $this->machine_name,
        'step' => 'page_variant__' . $page_variant->id() . '__contexts',
      ]];
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overridden to set the variantMachineName.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $context_id = NULL, $tempstore_id = NULL, $machine_name = NULL, $variant_machine_name = NULL) {
    $this->variantMachineName = $variant_machine_name;
    return parent::buildForm($form, $form_state, $context_id, $tempstore_id, $machine_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    /** @var $page_variant \Drupal\page_manager\PageVariantInterface */
    $page_variant = !empty($cached_values['page_variant']) ? $cached_values['page_variant'] : NULL;
    if (is_null($page_variant)) {
      $page_variant = $this->getPageVariant($cached_values);
    }
    return $page_variant->getContexts();
  }

  /**
   * {@inheritdoc}
   */
  protected function addContext($cached_values, $context_id, ContextInterface $context) {
    /** @var $page_variant \Drupal\page_manager\PageVariantInterface */
    $page_variant = $this->getPageVariant($cached_values);
    $context_config = [
      'label' => $context->getContextDefinition()->getLabel(),
      'type' => $context->getContextDefinition()->getDataType(),
      'description' => $context->getContextDefinition()->getDescription(),
      'value' => strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0 ? $context->getContextValue()->uuid() : $context->getContextValue(),
    ];
    $page_variant->setStaticContext($context_id, $context_config);
    $cached_values['page_variant'] = $page_variant;
    return $cached_values;
  }

  /**
   * {@inheritdoc}
   */
  public function contextExists($value, $element, $form_state) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function disableMachineName($cached_values, $machine_name) {
    if ($machine_name) {
      return !empty($this->getContexts($cached_values)[$machine_name]);
    }
    return FALSE;
  }

}
