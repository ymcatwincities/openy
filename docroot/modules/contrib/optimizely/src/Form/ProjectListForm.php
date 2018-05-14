<?php

namespace Drupal\optimizely\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\optimizely\Util\LookupPath;
use Drupal\optimizely\Util\AccountId;

/**
 * Implements the form for the Projects Listing.
 *
 * The term "form" is used loosely here.
 */
class ProjectListForm extends FormBase {

  use LookupPath;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely-project-listing';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    // Load css and js files specific to optimizely admin pages.
    $form['#attached']['library'][] = 'optimizely/optimizely.forms';
    $form['#attached']['library'][] = 'optimizely/optimizely.enable';

    $prefix = '<ul class="admin-links"><li>';
    $prefix .= Link::fromTextAndUrl(t('Add Project Entry'), new Url('optimizely.add_update'))->toString();
    $prefix .= '</li></ul>';

    $header = [t('Enabled'), t('Project Title'), t('Update / Delete'),
      t('Paths'), t('Project Code'),
    ];

    $form['projects'] = [
      '#prefix' => $prefix . '<div id="optimizely-project-listing">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => $header,
    ];

    $rows_rend = [];

    // Lookup account ID setting to trigger "nag message".
    $account_id = AccountId::getId();

    // Begin building the query.
    $query = \Drupal::database()->select('optimizely', 'o', ['target' => 'slave'])
      ->orderBy('oid')
      ->fields('o');
    $result = $query->execute();

    // Build each row of the table.
    foreach ($result as $project_count => $row) {

      // Listing of target paths for the project entry.
      $paths = unserialize($row->path);

      // Modify the front page path, if present.
      $front_idx = array_search('<front>', $paths);
      if ($front_idx !== FALSE) {
        $config = \Drupal::config('system.site');
        $front_path = $config->get('page.front');
        $front_path .= ' <-> ';

        $path_alias = $this->lookupPathAlias($front_path);
        $front_path .= $path_alias ? $path_alias : '';

        $paths[$front_idx] = '<front> (' . $front_path . ')';
      }

      // Build form elements including enable checkbox and data columns.
      $form['projects'][$project_count]['enable'] = [
        '#type' => 'checkbox',
        '#attributes' => [
          'id' => 'project-enable-' . $row->oid,
          'name' => 'project-' . $row->oid,
        ],
        '#default_value' => $row->enabled,
        '#extra_data' => ['field_name' => 'project_enabled'],
        '#suffix' => '<div class="status-container status-' . $row->oid . '"></div>',
      ];

      if ($row->enabled) {
        $form['projects'][$project_count]['enable']['#attributes']['checked'] = 'checked';
      }

      // Build the Edit / Delete links
      // User may not delete the Default project.
      if ($row->oid == 1) {
        $render_links = [
          '#type' => 'inline_template',
          '#template' => '<a href="{{ update_url }}">{{ update }}</a> / Default Entry',
          '#context' => [
            'update' => t('Update'),
            'update_url' =>
            Url::fromRoute('optimizely.add_update.oid', ['oid' => $row->oid])->toString(),
          ],
        ];
      }
      else {
        $render_links = [
          '#type' => 'inline_template',
          '#template' => '<a href="{{ update_url }}">{{ update }}</a> /
                            <a href="{{ delete_url }}">{{ delete }}</a>',
          '#context' => [
            'update' => t('Update'),
            'delete' => t('Delete'),
            'update_url' =>
            Url::fromRoute('optimizely.add_update.oid', ['oid' => $row->oid])->toString(),
            'delete_url' =>
            Url::fromRoute('optimizely.delete.oid', ['oid' => $row->oid])->toString(),
          ],
        ];
      }

      $render_paths = [
        '#type' => 'inline_template',
        '#template' => '<ul>
            {% for p in paths %}<li>{{ p }}</li>{% endfor %}
            </ul>',
        '#context' => ['paths' => $paths],
      ];

      $form['projects'][$project_count]['#project_title'] = $row->project_title;
      $form['projects'][$project_count]['#admin_links'] = $render_links;
      $form['projects'][$project_count]['#paths'] = $render_paths;

      if ($account_id == 0 && $row->oid == 1) {
        // Calling the t() function will cause the embedded html
        // markup to be treated correctly as markup, not literal content.
        $project_code = t('Set Optimizely ID in <strong><a href="@url">@acct_info</a>
              </strong> page to enable default project sitewide.',
              [
                '@url' => Url::fromRoute('optimizely.settings')->toString(),
                '@acct_info' => t('Account Info'),
              ]
            );
      }
      else {
        $project_code = $row->project_code;
      }
      $form['projects'][$project_count]['#project_code'] = $project_code;
      $form['projects'][$project_count]['#oid'] = $row->oid;

      $rows_rend[] = $this->optimizelyProjectRow($form['projects'][$project_count]);
    }

    // Add all the rows to the render array.
    $form['projects']['#rows'] = $rows_rend;

    return $form;
  }

  /**
   * Build render array for one row of the table of projects.
   */
  private function optimizelyProjectRow($proj) {

    $enabled = (array_key_exists('checked', $proj['enable']['#attributes'])) ?
                TRUE : FALSE;

    $render = [
      'class' => [
        'project-row-' . $proj['#project_code'],
      ],
      'id' => [
        'project-' . $proj['#oid'],
      ],
      'data' => [
        [
          'class' => $enabled ? 'enable-column enabled' : 'enable-column disabled',
          'data' => $proj['enable'],
        ],
        [
          'class' => $enabled ? 'project-title-column enabled' : 'project-title-column disabled',
          'data' => $proj['#project_title'],
        ],
        [
          'class' => $enabled ? 'admin-links-column enabled' : 'admin-links-column disabled',
          'data' => $proj['#admin_links'],
        ],
        [
          'class' => $enabled ? 'paths-column enabled' : 'paths-column disabled',
          'data' => $proj['#paths'],
        ],
        [
          'class' => $enabled ? 'project-code-column enabled' : 'project-code-column disabled',
          'data' => $proj['#project_code'],
        ],
      ],
    ];

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Not used.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not used.
  }

}
