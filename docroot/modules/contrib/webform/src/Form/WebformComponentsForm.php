<?php

/**
 * @file
 * Contains \Drupal\webform\Form\WebformComponentsForm.
 */

namespace Drupal\webform\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Provides a table-based listing of all components for a webform.
 */
class WebformComponentsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_components_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form = [
      '#tree' => TRUE,
      '#node' => $node,
      '#component_options' => webform_component_options(),
      '#component_weights' => [],
      'components' => [],
    ];

    $form['nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['components'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Value'),
        $this->t('Required'),
        $this->t('Weight'),
        $this->t('Parent'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'webform-pid',
          'subgroup' => 'webform-pid',
          'source' => 'webform-cid',
          'hidden' => TRUE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'webform-components',
      ],
      '#attached' => [
        'library' => [
          'webform/webform.admin',
        ],
      ]
    ];

    // Get max weight and set default weights for all components. Defaults
    // needed to adjust later if the add component form needs to be inserted
    // directly after newly added component.
    foreach ($node->webform['components'] as $cid => $component) {
      $form['#component_weights'][$cid] = $component['weight'];
      if (!isset($max_weight) || $component['weight'] > $max_weight) {
        $max_weight = $component['weight'];
      }
    }

    // Create an add form.
    $add_form = [
      '#attributes' => [
        'class' => ['draggable', 'webform-add-form', 'tabledrag-leaf'],
      ]
    ];
    $add_form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New component name'),
      '#title_display' => 'invisible',
      '#size' => 24,
      '#maxlength' => NULL,
      '#attributes' => [
        'class' => ['webform-component-name'],
        'placeholder' => $this->t('New component name'),
      ],
    ];
    $add_form['type'] = [
      '#type' => 'select',
      '#options' => $form['#component_options'],
      '#default_value' => (isset($_GET['cid']) && isset($node->webform['components'][$_GET['cid']])) ? $node->webform['components'][$_GET['cid']]['type'] : 'textfield',
      '#attributes' => [
        'class' => ['webform-component-type'],
      ],
    ];
    $add_form['value'] = [
      '#markup' => '',
      '#attributes' => [
        'class' => ['webform-component-value'],
      ],
    ];
    $add_form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['webform-component-required'],
      ],
    ];
    $add_form['weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weight for new component'),
      '#title_display' => 'invisible',
      '#size' => 4,
      '#delta' => count($node->webform['components']) > 10 ? count($node->webform['components']) : 10,
      '#attributes' => [
        'class' => ['webform-weight'],
      ],
    ];

    if (isset($_GET['cid']) && isset($node->webform['components'][$_GET['cid']])) {
      // Make the add form appear by default directly after the one that was
      // just added.
      $add_form['weight']['#default_value'] = $form['#component_weights'][$_GET['cid']]['weight'] + 1;
      foreach (array_keys($node->webform['components']) as $cid) {
        // Adjust all later components also, to make sure none of them have the
        // same weight as the new component.
        if ($form['#component_weights'][$cid] >= $add_form['weight']['#default_value']) {
          $form['#component_weights'][$cid]++;
        }
      }
    }
    else {
      // If no component was just added, the new component should appear by
      // default at the end of the list.
      $add_form['weight']['#default_value'] = isset($max_weight) ? $max_weight + 1 : 0;
    }

    $add_form['parent']['cid'] = [
      '#parents' => ['components', 'add', 'cid'],
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => [
        'class' => ['webform-cid'],
      ],
    ];
    $add_form['parent']['pid'] = [
      '#parents' => ['components', 'add', 'pid'],
      '#type' => 'hidden',
      '#default_value' => (isset($_GET['cid']) && isset($node->webform['components'][$_GET['cid']])) ? $node->webform['components'][$_GET['cid']]['pid'] : 0,
      '#attributes' => [
        'class' => ['webform-pid'],
      ],
    ];
    $add_form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::validateComponentAddForm', '::validateComponentsForm'],
      '#submit' => ['::submitComponentAddForm'],
      '#wrapper_attributes' => [
        'class' => ['webform-component-add'],
      ],
    ];

    // Output all existing components.
    if (!empty($node->webform['components'])) {
      $component_tree = [];
      $page_count = 1;
      _webform_components_tree_build($node->webform['components'], $component_tree, 0, $page_count);
      $component_tree = _webform_components_tree_sort($component_tree);
      // Build the table rows recursively.
      foreach ($component_tree['children'] as $cid => $component) {
        $this->buildComponentsTableRow($node, $cid, $component, 0, $form, $add_form);
      }
    }
    else {
      $form['components'][] = [
        [
          '#markup' => $this->t('No Components, add a component below.'),
          '#wrapper_attributes' => [
            'colspan' => 7,
          ],
        ]
      ];
    }

    // Append the add form if not already output.
    if ($add_form) {
      $form['components']['add'] = $add_form;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#access' => count($node->webform['components']) > 0,
      '#validate' => ['::validateComponentsForm'],
    ];

    $form['warning'] = [
      '#weight' => -1,
    ];
    webform_input_vars_check($form, $form_state, 'components', 'warning');

    return $form;
  }

  /**
   * Form validation handler for adding a new component.
   */
  public function validateComponentAddForm($form, &$form_state) {
    // Check that the entered component name is valid.
    if (Unicode::strlen(trim($form_state->getValue(['components', 'add', 'name']))) <= 0) {
      $form_state->setErrorByName('components][add][name', $this->t('When adding a new component, the name field is required.'));
    }
  }

  /**
   * Form validation handler for updating components.
   */
  public function validateComponentsForm($form, &$form_state) {
    // Check that no two components end up with the same form key.
    $duplicates = [];
    $parents = [];
    $components = $form_state->getValue('components');
    unset($components['add']);
    if ($components) {
      foreach ($components as $cid => $component) {
        $form_key = $form['#node']->webform['components'][$cid]['form_key'];
        if (isset($parents[$component['pid']]) && ($existing = array_search($form_key, $parents[$component['pid']])) && $existing !== FALSE) {
          if (!isset($duplicates[$form_key])) {
            $duplicates[$form_key] = [$existing];
          }
          $duplicates[$form_key][] = $cid;
        }
        $parents[$component['pid']][$cid] = $form_key;
      }
    }

    if (!empty($duplicates)) {
      $items = [];
      foreach ($duplicates as $form_key => $cids) {
        foreach ($cids as $cid) {
          $items[] = webform_filter_xss($form['#node']->webform['components'][$cid]['name']);
        }
      }
      $list = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];

      $form_state->setErrorByName('', $this->t('The form order failed to save because the following elements have same form keys and are under the same parent. Edit each component and give them a unique form key, then try moving them again. !list_components', ['!list_components' => drupal_render($list)]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = Node::load($form_state->getValue('nid'));

    // Update all weight, required, and pid values.
    $changes = FALSE;
    foreach ($node->webform['components'] as $cid => $component) {
      if ($component['pid'] != $form_state->getValue(['components', $cid, 'pid']) || $component['weight'] != $form_state->getValue(['components', $cid, 'weight']) || $component['required'] != $form_state->getValue(['components', $cid, 'required'])) {
        $changes = TRUE;
        $node->webform['components'][$cid]['weight'] = $form_state->getValue(['components', $cid, 'weight']);
        $node->webform['components'][$cid]['required'] = $form_state->getValue(['components', $cid, 'required']);
        $node->webform['components'][$cid]['pid'] = $form_state->getValue(['components', $cid, 'pid']);
      }
    }

    if ($changes) {
      $node->save();
    }

    drupal_set_message($this->t('The component positions and required values have been updated.'));
  }

  /**
   * Form submission handler to redirect to the new component form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitComponentAddForm(array &$form, FormStateInterface $form_state) {
    $node = Node::load($form_state->getValue('nid'));

    $component = $form_state->getValue(['components', 'add']);

    // Set the values in the query string for the add component page.
    $query = [
      'name' => $component['name'],
      'required' => $component['required'],
      'pid' => $component['pid'],
      'weight' => $component['weight'],
      'type' => $component['type'],
    ];

    /* @TODO Maintain destination query string between forms.
    // Forward the "destination" query string value to the next form.
    if (isset($_GET['destination'])) {
      $query['destination'] = $_GET['destination'];
      unset($_GET['destination']);
      drupal_static_reset('drupal_get_destination');
    }
    */

    $form_state->setRedirect(
      'webform.component_add_form',
      array(
        'node' => $form_state->getValue('nid'),
        'component' => $component['type'],
      ),
      array( 'query' => $query )
    );
  }

  /**
   * Helper to recursively build table rows to hold existing components.
   *
   * @param object $node
   *   A node object the components belong to.
   * @param int $cid
   *   A cid of the component.
   * @param array $component
   *   A component.
   * @param int $level
   *   The nesting level of this component.
   * @param array $form
   *   The form that is being modified, passed by reference.
   * @param array $add_form
   *   The add form which will be inserted under any previously added/edited
   *   component.
   *
   * @see self::buildForm()
   */
  protected function buildComponentsTableRow($node, $cid, $component, $level, &$form, &$add_form) {
    $row_class = ['draggable'];
    if (!webform_component_feature($component['type'], 'group')) {
      $row_class[] = 'tabledrag-leaf';
    }
    if ($component['type'] == 'pagebreak') {
      $row_class[] = 'tabledrag-root';
      $row_class[] = 'webform-pagebreak';
    }
    $form['components'][$cid]['#attributes']['class'] = $row_class;
    $form['components'][$cid]['#attributes']['data-cid'] = $cid;

    $indentation = '';
    if ($level >= 1) {
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $level,
      ];
      $indentation = drupal_render($indentation);
    }
    $form['components'][$cid]['name'] = [
      '#prefix' => $indentation,
      '#markup' => Xss::filter($component['name']),
      '#attributes' => [
        'class' => ['webform-component-name', $component['type'] == 'pagebreak' ? 'webform-pagebreak' : ''],
      ],
    ];

    $form['components'][$cid]['type'] = [
      '#markup' => $form['#component_options'][$component['type']],
      '#attributes' => [
        'class' => ['webform-component-type'],
      ],
    ];

    // Create a presentable value.
    if (Unicode::strlen($component['value']) > 30) {
      $component['value'] = Unicode::substr($component['value'], 0, 30);
      $component['value'] .= '...';
    }
    $component['value'] = SafeMarkup::checkPlain($component['value']);
    $form['components'][$cid]['value'] = [
      '#markup' => ($component['value'] == '') ? '-' : $component['value'],
      '#attributes' => [
        'class' => ['webform-component-value'],
      ],
    ];

    $form['components'][$cid]['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#title_display' => 'invisible',
      '#default_value' => $component['required'],
      '#access' => webform_component_feature($component['type'], 'required'),
      '#attributes' => [
        'class' => ['webform-component-required'],
      ],
    ];

    $form['components'][$cid]['weight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weight for @title', ['@title' => $component['name']]),
      '#title_display' => 'invisible',
      '#size' => 4,
      '#delta' => count($node->webform['components']) > 10 ? count($node->webform['components']) : 10,
      '#default_value' => $form['#component_weights'][$cid],
      '#attributes' => [
        'class' => ['webform-weight'],
      ],
    ];

    $form['components'][$cid]['parent']['cid'] = [
      '#parents' => ['components', $cid, 'cid'],
      '#type' => 'hidden',
      '#default_value' => $component['cid'],
      '#attributes' => [
        'class' => ['webform-cid'],
      ],
    ];

    $form['components'][$cid]['parent']['pid'] = [
      '#parents' => ['components', $cid, 'pid'],
      '#type' => 'hidden',
      '#default_value' => $component['pid'],
      '#attributes' => [
        'class' => ['webform-pid'],
      ],
    ];

    $form['components'][$cid]['operations'] = [
      '#type' => 'operations',
      '#links' => [],
    ];
    // @todo Fix these links once the routes exist.
    $form['components'][$cid]['operations']['#links']['edit'] = [
      'title' => $this->t('Edit'),
      'url' => Url::fromRoute('webform.component_edit_form', ['node' => $node->id(), 'component' => $cid]),
    ];
    $form['components'][$cid]['operations']['#links']['clone'] = [
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.node.webform', ['node' => $node->id()]),
    ];
    $form['components'][$cid]['operations']['#links']['delete'] = [
      'title' => $this->t('Delete'),
      'url' => Url::fromRoute('entity.node.webform', ['node' => $node->id()]),
    ];

    if (isset($component['children']) && is_array($component['children'])) {
      foreach ($component['children'] as $cid => $component) {
        $this->buildComponentsTableRow($node, $cid, $component, $level + 1, $form, $add_form);
      }
    }

    // Add the add form if this was the last edited component.
    if (isset($_GET['cid']) && $component['cid'] == $_GET['cid'] && $add_form) {
      $add_form['name']['#prefix'] = $indentation;
      $form['components']['add'] = $add_form;
      $add_form = FALSE;
    }
  }

}
