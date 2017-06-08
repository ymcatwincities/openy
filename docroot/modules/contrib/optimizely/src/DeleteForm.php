<?php

namespace Drupal\optimizely;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Implements the confirmation form for deleting a project.
 */
class DeleteForm extends ConfirmFormBase {

  private $oid = NULL;

  public function getFormId() {
    return 'optimizely-delete-page-confirm';
  }

  public function getQuestion() {
    return $this->t('Delete');  // More like a heading.
  }

  public function getDescription() {
    return $this->t('Are you sure you want to delete this configuration?' .
                    '<p>This action cannot be undone.</p>');
  }

  public function getConfirmText() {
    return $this->t('Delete');  // Default is 'Confirm'
  }

  public function getCancelUrl() {
    return new Url('optimizely.listing');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $oid = NULL) {

    // Implement this method so we can record the project id for submitForm().
    $this->oid = $oid;
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Prevent deletion of default project.
    if ($this->oid == 1) {
      drupal_set_message($this->t('Default project cannot be deleted.'), 'error');
      // Return to project listing page
      $form_state->setRedirect('optimizely.listing');
      return;
    }

    // Lookup entry details before delete
    $query = db_select('optimizely', 'o', array('target' => 'slave'))
      ->fields('o', array('path', 'enabled'))
      ->condition('o.oid', $this->oid, '=');

    $record = $query->execute()
      ->fetchObject();
    
    // Delete entry in database based on the target $oid
    $query = db_delete('optimizely')
      ->condition('oid', $this->oid);
    $query->execute();

    // Only clear page cache for entries that are active when deleted
    if ($record->enabled) {
      
      // Always serialized when saved 
      $path_array = unserialize($record->path);
      CacheRefresher::doRefresh($path_array);
      
    }

    drupal_set_message(t('The project entry has been deleted.'), 'status');

    // Return to project listing page
    $form_state->setRedirect('optimizely.listing');
  }

}
