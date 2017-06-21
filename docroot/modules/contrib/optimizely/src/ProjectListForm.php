<?php

namespace Drupal\optimizely;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Implements the form for the Projects Listing.
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
  
    $form = array();
    
    // Load css and js files specific to optimizely admin pages
    $form['#attached']['library'][] = 'optimizely/optimizely.forms';
    $form['#attached']['library'][] = 'optimizely/optimizely.enable';
    
    $prefix  = '<ul class="admin-links"><li>';
    $prefix .= \Drupal::l(t('Add Project Entry'), new Url('optimizely.add_update'));
    $prefix .= '</li></ul>';

    $header = array(t('Enabled'), t('Project Title'), t('Update / Delete'), 
                    t('Paths'), t('Project Code'));
    
    $form['projects'] = array(
      '#prefix' => $prefix . '<div id="optimizely-project-listing">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => $header,
    );

    $rows_rend = array();

    // Lookup account ID setting to trigger "nag message".
    $account_id =  AccountId::getId();
    
    // Begin building the query.
    $query = db_select('optimizely', 'o', array('target' => 'slave'))
      ->orderBy('oid')
      ->fields('o');
    $result = $query->execute();

    // Build each row of the table
    foreach ($result as $project_count => $row) {
      
      // Listing of target paths for the project entry
      $paths = unserialize($row->path);
      
      // Modify the front page path, if present.
      $front_idx = array_search('<front>', $paths);
      if ($front_idx !== FALSE) {
        $config = \Drupal::config('system.site');
        $front_path = $config->get('page.front');
        $front_path .= ' <-> ';

        $path_alias = $this->lookupPathAlias($front_path);
        $front_path .= $path_alias ? $path_alias : '';

        $paths[$front_idx] = '<front>' . ' (' . $front_path . ')';
      }

      // Build form elements including enable checkbox and data columns
      $form['projects'][$project_count]['enable'] = array(
        '#type' => 'checkbox',
        '#attributes' => array(
          'id' => 'project-enable-' . $row->oid,
          'name' => 'project-' . $row->oid
        ),
        '#default_value' => $row->enabled,
        '#extra_data' => array('field_name' => 'project_enabled'),
        '#suffix' => '<div class="status-container status-' . $row->oid . '"></div>'
      );
      
      if ($row->enabled) {
        $form['projects'][$project_count]['enable']['#attributes']['checked'] = 'checked';
      }

      // Build the Edit / Delete links
      // User may not delete the Default project.
      if ($row->oid == 1) {
        $render_links = array(
            '#type' => 'inline_template',
            '#template' => '<a href="{{ update_url }}">{{ update }}</a> / Default Entry',
            '#context' => array('update' => t('Update'),
                                'update_url' =>
                                   \Drupal::url('optimizely.add_update.oid', array('oid' => $row->oid)),
                            ),
          );
      }
      else {
        $render_links = array(
            '#type' => 'inline_template',
            '#template' => '<a href="{{ update_url }}">{{ update }}</a> / '.
                           '<a href="{{ delete_url }}">{{ delete }}</a>',
            '#context' => array('update' => t('Update'),
                                'delete' => t('Delete'),
                                'update_url' =>
                                   \Drupal::url('optimizely.add_update.oid', array('oid' => $row->oid)),
                                'delete_url' =>
                                   \Drupal::url('optimizely.delete.oid', array('oid' => $row->oid)),
                            ),
          );
      }

      $render_paths = array(
          '#type' => 'inline_template',
          '#template' => '<ul>' .
            '{% for p in paths %}<li>{{ p }}</li>{% endfor %}' .
            '</ul>',
          '#context' => array('paths' => $paths),
        );

      $form['projects'][$project_count]['#project_title'] = $row->project_title;
      $form['projects'][$project_count]['#admin_links'] = $render_links;
      $form['projects'][$project_count]['#paths'] = $render_paths;
      
      if ($account_id == 0 && $row->oid == 1) {
        // Calling the t() function will cause the embedded html
        // markup to be treated correctly as markup, not literal content.
        $project_code = t('Set Optimizely ID in <strong><a href="@url">@acct_info</a>' .
              '</strong> page to enable default project sitewide.',
              array('@url' => \Drupal::url('optimizely.settings'),
                    '@acct_info' => t('Account Info'),
                )
            );
      }
      else {
        $project_code = $row->project_code;
      }
      $form['projects'][$project_count]['#project_code'] = $project_code;
      $form['projects'][$project_count]['#oid'] = $row->oid;

      $rows_rend[] = $this->_optimizely_project_row($form['projects'][$project_count]);
    }

    // Add all the rows to the render array.
    $form['projects']['#rows'] = $rows_rend;

    return $form;
  }

  /**
   * Build render array for one row of the table of projects.
   */
  private function _optimizely_project_row($proj) {
        
    $enabled = (array_key_exists('checked', $proj['enable']['#attributes'])) ?
                TRUE : FALSE;
      
    $render = array(
      'class' => array(
        'project-row-' . $proj['#project_code']
      ),
      'id' => array(
        'project-' . $proj['#oid']
      ),
      'data' => array(
        array(
          'class' => $enabled ? 'enable-column enabled' : 'enable-column disabled',
          'data' => $proj['enable'],
        ),
        array(
          'class' => $enabled ? 'project-title-column enabled' : 'project-title-column disabled',
          // 'data' => render($proj['#project_title']),
          'data' => $proj['#project_title'],
        ),
        array(
          'class' => $enabled ? 'admin-links-column enabled' : 'admin-links-column disabled',
          'data' => $proj['#admin_links'],
        ),
        array(
          'class' => $enabled ? 'paths-column enabled' : 'paths-column disabled',
          'data' => $proj['#paths'],
        ),
        array(
          'class' => $enabled ? 'project-code-column enabled' : 'project-code-column disabled',
          'data' => $proj['#project_code'],
        ),
      ),
    );
    
    return $render;   
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Not used.
    return;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not used.
    return;
  }
}
