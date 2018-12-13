<?php

namespace Drupal\openy_system\Form;

use Drupal\system\Form\ModulesUninstallConfirmForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds a confirmation form to uninstall selected Open Y project packages.
 */
class OpenyModulesUninstallConfirmForm extends ModulesUninstallConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Would you like to continue with uninstalling the above?</br><div class="messages messages--warning">Data and content within these packages can <b>not</b> be recovered from the Open Y Admin Interface after clicking "Uninstall".</div>');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('openy_system.modules_uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'openy_system/openy_system';
    $form['actions']['submit']['#id'] = 'packages-uninstall-confirm';
    // Retrieve the list of modules and packages names from the key value store.
    $account = $this->currentUser()->id();
    $this->modules = $this->keyValueExpirable->get($account);
    $packages = $this->keyValueExpirable->get($account . '_packages');

    // Prevent this page from showing when the packages list is empty.
    if (empty($packages)) {
      drupal_set_message($this->t('The selected packages could not be uninstalled, either due to a website problem or due to the uninstall confirmation form timing out. Please try again.'), 'error');
      return $this->redirect('openy_system.modules_uninstall');
    }

    $form['text']['#markup'] = '<p>' . $this->t('The following Open Y packages will be completely uninstalled from your site, and <em>all data and content from these packages will be lost</em>!') . '</p>';
    $form['modules'] = [
      '#theme' => 'item_list',
      '#items' => $packages,
    ];

    // List the dependent entities.
    $this->addDependencyListsToForm($form, 'module', $this->modules, $this->configManager, $this->entityManager);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear the key value store entry.
    $account = $this->currentUser()->id();
    $this->keyValueExpirable->delete($account);
    $this->keyValueExpirable->delete($account. '_packages');
    // Uninstall the modules.
    $this->moduleInstaller->uninstall($this->modules);
    drupal_set_message($this->t('The selected packages have been uninstalled.'));
    // Set redirect to project uninstall page
    $form_state->setRedirect('openy_system.modules_uninstall');
  }

}
