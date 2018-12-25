<?php

namespace Drupal\optimizely\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\optimizely\Util\AccountId;
use Drupal\optimizely\Util\PathChecker;
use Drupal\optimizely\Util\CacheRefresher;

/**
 * Implements the form for the Add Projects page.
 */
class AddUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely-add-update';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $target_oid = NULL) {

    $addupdate_form = [];
    $addupdate_form['#theme'] = 'optimizely_add_update_form';
    $form['#attached']['library'][] = 'optimizely/optimizely.forms';

    if ($target_oid == NULL) {

      $form_action = 'Add';

      $intro_message = '';

      $addupdate_form['optimizely_oid'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];

      // Enable form element defaults - blank, unselected.
      $enabled = FALSE;
      $project_code = '';
    }
    else {

      $form_action = 'Update';

      $query = \Drupal::database()->select('optimizely', 'o', ['target' => 'slave'])
        ->fields('o')
        ->condition('o.oid', $target_oid, '=');

      $record = $query->execute()
        ->fetchObject();

      $addupdate_form['optimizely_oid'] = [
        '#type' => 'value',
        '#value' => $target_oid,
      ];

      $addupdate_form['optimizely_original_path'] = [
        '#type' => 'value',
        '#value' => implode("\n", unserialize($record->path)),
      ];

      $enabled = $record->enabled;
      $project_code = ($record->project_code == 0) ? 'Undefined' : $record->project_code;
    }

    // If we are updating the default record,
    // make the form element inaccessible.
    $addupdate_form['optimizely_project_title'] = [
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => $this->t('Project Title'),
      '#default_value' => $target_oid ? $record->project_title : '',
      '#description' => ($target_oid == 1) ?
        $this->t('Default project, this field can not be changed.') :
        $this->t('Descriptive name for the project entry.'),
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
      '#weight' => 10,
    ];

    $account_id = AccountId::getId();

    $addupdate_form['optimizely_project_code'] = [
      '#type' => 'textfield',
      '#disabled' => $target_oid == 1 ? TRUE : FALSE,
      '#title' => $this->t('Optimizely Project Code'),
      '#default_value' => $project_code,
      '#description' => ($account_id == 0) ?
      $this->t('The Optimizely account value has not been set in the
           <a href="@url">Account Info</a> settings form.
           The Optimizely account value is used as
           the project ID for this "default" project entry.',
          ['@url' => Url::fromRoute('optimizely.settings')->toString()]
      ) :
      $this->t('The Optimizely javascript file name used in the snippet
           as provided by the Optimizely website for the project.'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#weight' => 20,
    ];

    $addupdate_form['optimizely_path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Set Path Where Optimizely Code Snippet Appears'),
      '#default_value' => $target_oid ? implode("\n", unserialize($record->path)) : '',
      '#description' => $this->t('Enter the path where you want to insert the Optimizely
         Snippet. For Example: "/clubs/*" causes the snippet to appear on all pages
         below "/clubs" in the URL but not on the actual "/clubs" page itself.'),
      '#cols' => 100,
      '#rows' => 6,
      '#resizable' => FALSE,
      '#required' => FALSE,
      '#weight' => 40,
    ];

    $addupdate_form['optimizely_enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable/Disable Project'),
      '#default_value' => $target_oid ? $record->enabled : 0,
      '#options' => [
        1 => 'Enable project',
        0 => 'Disable project',
      ],
      '#weight' => 25,
      '#attributes' => $enabled ?
        ['class' => ['enabled']] :
        ['class' => ['disabled']],
    ];

    $addupdate_form['submit'] = [
      '#type' => 'submit',
      '#value' => $form_action,
      '#weight' => 100,
    ];

    $addupdate_form['cancel'] = [
      '#markup' => Link::fromTextAndUrl(t('Cancel'), new Url('optimizely.settings'))->toString(),
      '#weight' => 101,
    ];

    return $addupdate_form;
  }

  /**
   * {@inheritdoc}
   *
   * Check to make sure the project code is unique except for the default
   * entry which uses the account ID but should support an additional entry
   * to allow for custom settings.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $proj_code = $form_state->getValue('optimizely_project_code');
    $op = $form_state->getValue('op');

    // Watch for "Undefined" value in Project Code, Account ID needed
    // in Settings page.
    if ($proj_code == "Undefined") {
      $form_state->setErrorByName('optimizely_project_code',
        $this->t('The Optimizely Account ID must be set in the
                   <a href="@url">Account Info</a> page.
                   The account ID is used as the default Optimizely Project Code.',
                  ['@url' => Url::fromRoute('optimizely.settings')->toString()]
                )
        );
    }
    // Validate that the project code entered is a number.
    elseif (!ctype_digit($proj_code)) {
      $form_state->setErrorByName('optimizely_project_code',
        $this->t("The project code %code must only contain digits.",
          ['%code' => $proj_code]));
    }
    elseif ($op == 'Add') {

      // Confirm project_code is unique or the entered project code
      // is also the account ID.
      // SELECT the project title in prep for related form error message.
      $query = \Drupal::database()->query('SELECT project_title FROM {optimizely}
        WHERE project_code = :project_code ORDER BY oid DESC',
        [':project_code' => $proj_code]);

      // Fetch an indexed array of the project titles, if any.
      $results = $query->fetchCol(0);
      $query_count = count($results);

      // Flag submission if existing entry is found with the same project
      // code value AND it's not a SINGLE entry to replace the "default" entry.
      if ($query_count > 0 ||
         ($proj_code != AccountId::getId()
            && $query_count >= 2)) {

        // Get the title of the project that already had the project code.
        $found_entry_title = $results[0];

        // Flag the project code form field.
        $form_state->setErrorByName('optimizely_project_code',
          $this->t('The project code (%project_code) already has an entry
                     in the "%found_entry_title" project.',
                    [
                      '%project_code' => $proj_code,
                      '%found_entry_title' => $found_entry_title,
                    ]));
      }

    }

    // Skip if disabled entry.
    $enabled = $form_state->getValue('optimizely_enabled');
    $paths = $form_state->getValue('optimizely_path');
    $oid = $form_state->getValue('optimizely_oid');

    if ($enabled) {

      // Confirm that the project paths point to valid site URLs.
      $target_paths = preg_split('/[\r\n]+/', $paths, -1, PREG_SPLIT_NO_EMPTY);

      // For uniformity and ease of string matching, ensure each path
      // starts with a slash / except for the site-wide wildcard * or
      // special aliases such as <front>.
      self::checkPaths($target_paths);

      $valid_path = PathChecker::validatePaths($target_paths);
      if (!is_bool($valid_path)) {
        $form_state->setErrorByName('optimizely_path',
          t('The project path "%project_path" could not be resolved as a valid URL for the site,
             or it contains a wildcard * that cannot be handled by this module.',
            ['%project_path' => $valid_path]));

      }

      // There must be only one Optimizely javascript call on a page.
      // Check paths to ensure there are no duplicates.
      list($error_title, $error_path) =
        PathChecker::uniquePaths($target_paths, $oid);

      if (!is_bool($error_title)) {
        $form_state->setErrorByName('optimizely_path',
          t('The path "%error_path" will result in a duplicate entry based on
             the other project path settings. Optimizely does not allow more
             than one project to be run on a page.',
            ['%error_path' => $error_path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Catch form submitted values and prep for processing.
    $oid = $form_state->getValue('optimizely_oid');

    $project_title = $form_state->getValue('optimizely_project_title');
    $project_code = $form_state->getValue('optimizely_project_code');

    $path_array = preg_split('/[\r\n]+/', $form_state->getValue('optimizely_path'),
                              -1, PREG_SPLIT_NO_EMPTY);

    // For uniformity and ease of string matching, ensure each path starts
    // with a slash / except for the site-wide wildcard * or special aliases
    // such as <front>.
    self::checkPaths($path_array);

    $enabled = $form_state->getValue('optimizely_enabled');

    // Process the submission to add or edit.
    // If no ID value is included in submission then add new entry.
    if (!isset($oid)) {

      \Drupal::database()->insert('optimizely')
        ->fields([
          'project_title' => $project_title,
          'path' => serialize($path_array),
          'project_code' => $project_code,
          'enabled' => $enabled,
        ])
        ->execute();

      drupal_set_message(t('The project entry has been created.'), 'status');

      // Rebuild the provided paths to ensure Optimizely javascript
      // is now included on paths.
      if ($enabled) {
        CacheRefresher::doRefresh($path_array);
      }

    }
    // $oid is set, update existing entry.
    else {

      \Drupal::database()->update('optimizely')
        ->fields([
          'project_title' => $project_title,
          'path' => serialize($path_array),
          'project_code' => $project_code,
          'enabled' => $enabled,
        ])
        ->condition('oid', $oid)
        ->execute();

      drupal_set_message(t('The project entry has been updated.'), 'status');

      // Path originally set for project - to be compared to the updated value
      // to determine what cache paths needs to be refreshed.
      $original_path_array = preg_split('/[\r\n]+/', $form_state->getValue('optimizely_original_path'),
                                        -1, PREG_SPLIT_NO_EMPTY);

      CacheRefresher::doRefresh($path_array, $original_path_array);

    }

    // Return to project listing page.
    $form_state->setRedirect('optimizely.listing');
  }

  /**
   * Ensure that for an array of paths, each path starts with a slash.
   */
  private static function checkPaths(&$path_array) {
    foreach ($path_array as &$path) {
      $path = self::checkPathLeadingSlash($path);
    }
  }

  /**
   * Ensure that the path starts with a slash.
   *
   * @param string $path
   *   Path to be checked for having a leading slash. If leading slash
   *   is missing, prefix one. If the path already starts with a special char
   *   such as * or < leave it alone.
   *
   * @return string
   *   The path with a leading slash added, or the original path unchanged.
   */
  private static function checkPathLeadingSlash($path) {
    return (ctype_alnum($path[0])) ? '/' . $path : $path;
  }

}
