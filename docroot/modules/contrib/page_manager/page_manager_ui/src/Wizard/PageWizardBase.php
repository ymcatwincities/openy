<?php
/**
 * @file
 * Contains \Drupal\page_manager_ui\Wizard\PageWizardBase.
 */

namespace Drupal\page_manager_ui\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\page_manager_ui\Access\PageManagerPluginAccess;
use Drupal\page_manager_ui\Form\PageGeneralForm;
use Drupal\page_manager_ui\Form\PageParametersForm;
use Drupal\page_manager_ui\Form\PageAccessForm;

class PageWizardBase extends EntityFormWizardBase {

  public function initValues() {
    $cached_values = parent::initValues();
    $cached_values['access'] = new PageManagerPluginAccess();
    return $cached_values;
  }


  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'page';
  }

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return '\Drupal\page_manager\Entity\Page::load';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Page Manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Administrative title');
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $operations = [];
    $operations['general'] = [
      'title' => $this->t('Page information'),
      'form' => PageGeneralForm::class,
    ];
    /** @var $page \Drupal\page_manager\Entity\Page */
    $page = $cached_values['page'];

    if ($page) {
      $matches = [];
      preg_match_all('|\{\w+\}|', $page->getPath(), $matches);
      if (array_filter($matches)) {
        $operations['parameters'] = [
          'title' => $this->t('Page parameters'),
          'form' => PageParametersForm::class,
        ];
      }
    }
    $operations['access'] = [
      'title' => $this->t('Page access'),
      'form' => PageAccessForm::class,
    ];

    return $operations;
  }

  /**
   * Submission callback for the variant plugin steps.
   */
  public function submitVariantStep(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = $cached_values['page_variant'];
    /** @var \Drupal\Core\Display\VariantInterface $plugin */
    $plugin = $cached_values['plugin'];

    // Make sure the variant plugin on the page variant gets the configuration
    // from the 'plugin' which should have been setup by the variant's steps.
    if (!empty($plugin) && !empty($page_variant)) {
      $page_variant->getVariantPlugin()->setConfiguration($plugin->getConfiguration());
    }
  }

  public function finish(array &$form, FormStateInterface $form_state) {
    parent::finish($form, $form_state);

    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\page_manager\Entity\Page $page */
    $page = $cached_values['page'];
    foreach($page->getVariants() as $variant) {
      $variant->save();
    }

    $form_state->setRedirectUrl(new Url('entity.page.edit_form', [
      'machine_name' => $this->machine_name,
      'step' => $this->step
    ]));
  }

}
