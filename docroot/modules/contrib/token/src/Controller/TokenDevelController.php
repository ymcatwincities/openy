<?php

/**
 * @file
 * Contains \Drupal\token\Controller\TokenDevelController.
 */

namespace Drupal\token\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Devel integration for tokens.
 */
class TokenDevelController implements ContainerInjectionInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new TokenDevelController.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  public function devel_token_node($node, Request $request) {
    return $this->devel_token_object('node', $node, $request);
  }

  public function devel_token_comment($comment, Request $request) {
    return $this->devel_token_object('comment', $comment, $request);
  }

  public function devel_token_user($user, Request $request) {
    return $this->devel_token_object('user', $user, $request);
  }

  public function devel_token_taxonomy_term($taxonomy_term, Request $request) {
    return $this->devel_token_object('taxonomy_term', $taxonomy_term, $request);
  }

  private function devel_token_object($entity_type, $entity_id, Request $request) {
    $this->moduleHandler->loadInclude('token', 'pages.inc');
    $entity = entity_load($entity_type, $entity_id);

    $header = array(
      t('Token'),
      t('Value'),
    );
    $rows = array();

    $options = array(
      'flat' => TRUE,
      'values' => TRUE,
      'data' => array($entity_type => $entity),
    );
    $tree = token_build_tree($entity_type, $options);
    foreach ($tree as $token => $token_info) {
      if (!empty($token_info['restricted'])) {
        continue;
      }
      if (!isset($token_info['value']) && !empty($token_info['parent']) && !isset($tree[$token_info['parent']]['value'])) {
        continue;
      }
      $row = _token_token_tree_format_row($token, $token_info);
      unset($row['data']['description']);
      unset($row['data']['name']);
      $rows[] = $row;
    }

    $build['tokens'] = array(
      '#theme' => 'tree_table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('class' => array('token-tree')),
      '#empty' => t('No tokens available.'),
      '#attached' => array(
        'library' => array('token/token'),
      ),
    );

    return $build;
  }
}
