<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\GroupexFormLocationRefine.
 */

namespace Drupal\ymca_groupex\Form;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Implements Groupex Location Refine Form.
 */
class GroupexFormLocationRefine extends GroupexFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupex_form_refine';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get current node.
    /** @var Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    $form_state->setRedirect(
      'ymca_groupex.schedules_search_results',
      ['node' => $node->id()],
      ['query' => $this->getRedirectParams($form, $form_state)]
    );
  }

}
