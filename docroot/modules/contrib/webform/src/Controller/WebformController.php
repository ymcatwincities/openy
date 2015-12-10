<?php

/**
 * @file
 * Contains \Drupal\webform\Controller\WebformController.
 */

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Returns responses for Webform routes.
 */
class WebformController extends ControllerBase {

  /**
   * Create an overview of webform content.
   */
  public function contentOverview() {
    // @todo This needs to be removed and the view webform_webforms used instead.
    $query = db_select('webform', 'w');
    $query->join('node', 'n', 'w.nid = n.nid');
    $query->fields('n');
    $nodes = $query->execute()->fetchAllAssoc('nid');
    module_load_include('inc', 'webform', 'includes/webform.admin');

    $header = array(
      t('Title'),
      array('data' => t('View'), 'colspan' => '4'),
      array('data' => t('Operations'), 'colspan' => '3')
    );

    $rows = array();
    if (!empty($nodes)) {
      foreach ($nodes as $node) {
        $node = Node::load($node->id());
        $rows[] = array(
          \Drupal::l($node->getTitle(), Url::fromRoute('entity.node.canonical', ['node' => $node->id()])),
          t('Submissions'), //l(t('Submissions'), 'node/' . $node->id() . '/webform-results'),
          t('Analysis'), //l(t('Analysis'), 'node/' . $node->id() . '/webform-results/analysis'),
          t('Table'), //l(t('Table'), 'node/' . $node->id() . '/webform-results/table'),
          t('Download'), //l(t('Download'), 'node/' . $node->id() . '/webform-results/download'),
          $node->access('update') ? \Drupal::l(t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $node->id()])) : '',
          t('Components'), //$node->access('update') ? l(t('Components'), 'node/' . $node->id() . '/webform') : '',
          t('Clear'), //\Drupal::currentUser()->hasPermission('delete all webform submissions') ? l(t('Clear'), 'node/' . $node->id() . '/webform-results/clear') : '',
        );
      }
    }

    if (empty($nodes)) {
      $webform_types = webform_node_types();
      if (empty($webform_types)) {
        $message = t('Webform is currently not enabled on any content types.') . ' ' . t('Visit the <a href="!url">Webform settings</a> page and enable Webform on at least one content type.', array('!url' => Url::fromRoute('webform.settings')->toString()));
      }
      else {
        $webform_type_list = webform_admin_type_list();
        $message = t('There are currently no webforms on your site. Create a !types piece of content.', array('!types' => $webform_type_list));
      }

      $rows[] = array(
        array('data' => $message, 'colspan' => 7),
      );
    }

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $table;
  }

}
