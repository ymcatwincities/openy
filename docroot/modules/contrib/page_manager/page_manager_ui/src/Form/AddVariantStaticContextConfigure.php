<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\AddVariantStaticContextConfigure.
 */

namespace Drupal\page_manager_ui\Form;


use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\ctools\Form\ContextConfigure;

class AddVariantStaticContextConfigure extends ContextConfigure {

  /**
   * Get the page variant.
   *
   * @param array $cached_values
   *   The cached values from the wizard.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function getPageVariant($cached_values) {
    return $cached_values['page_variant'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getParentRouteInfo($cached_values) {
    $page_variant = $this->getPageVariant($cached_values);
    return ['entity.page_variant.add_step_form', [
      'page' => $page_variant->getPage()->id(),
      'machine_name' => $this->machine_name,
      'step' => 'contexts',
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    return $this->getPageVariant($cached_values)->getContexts();
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
