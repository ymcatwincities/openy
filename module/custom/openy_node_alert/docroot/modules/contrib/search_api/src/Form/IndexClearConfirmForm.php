<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\search_api\SearchApiException;

/**
 * Defines a confirm form for clearing an index.
 */
class IndexClearConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clear the indexed data for the search index %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('All indexed data for this index will be deleted from the search server. Searches on this index will not return any results until items are reindexed. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_api_index.canonical', ['search_api_index' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getEntity();

    try {
      $index->clear();
      drupal_set_message($this->t('All items were successfully deleted from search index %name.', ['%name' => $index->label()]));
    }
    catch (SearchApiException $e) {
      drupal_set_message($this->t('Failed to clear the search index %name.', ['%name' => $index->label()]), 'error');
      $message = '%type while trying to clear the index %name: @message in %function (line %line of %file)';
      $variables = [
        '%name' => $index->label(),
      ];
      $variables += Error::decodeException($e);
      $this->getLogger('search_api')->error($message, $variables);
    }

    $form_state->setRedirect('entity.search_api_index.canonical', ['search_api_index' => $index->id()]);
  }

}
