<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiUser.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a filter for filtering on user references.
 *
 * Based on \Drupal\user\Plugin\views\filter\Name.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_user")
 */
class SearchApiUser extends SearchApiFilterEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Set autocompletion.
    $path = $this->isMultiValued() ? 'admin/views/ajax/autocomplete/user' : 'user/autocomplete';
    $form['value']['#autocomplete_path'] = $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function idsToString(array $ids) {
    $names = array();
    $args[':uids'] = array_filter($ids);
    $result = Database::getConnection()->query("SELECT uid, name FROM {users} u WHERE uid IN (:uids)", $args);
    $result = $result->fetchAllKeyed();
    foreach ($ids as $uid) {
      if (!$uid) {
        $names[] = \Drupal::config('user.settings')->get('anonymous');
      }
      elseif (isset($result[$uid])) {
        $names[] = $result[$uid];
      }
    }
    return implode(', ', $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntityStrings(array &$form, array $values, FormStateInterface $form_state) {
    $uids = array();
    $missing = array();
    foreach ($values as $value) {
      if (Unicode::strtolower($value) === Unicode::strtolower(\Drupal::config('user.settings')->get('anonymous'))) {
        $uids[] = 0;
      }
      else {
        $missing[strtolower($value)] = $value;
      }
    }

    if (!$missing) {
      return $uids;
    }

    $result = Database::getConnection()->query("SELECT * FROM {users} WHERE name IN (:names)", array(':names' => array_values($missing)));
    foreach ($result as $account) {
      unset($missing[strtolower($account->name)]);
      $uids[] = $account->uid;
    }

    if ($missing) {
      $form_state->setError($form, $this->formatPlural(count($missing), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $missing))));
    }

    return $uids;
  }

}
