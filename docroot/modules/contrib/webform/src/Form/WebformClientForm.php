<?php

/**
 * @file
 * Contains \Drupal\webform\Form\WebformClientForm.
 */

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a webform client form.
 */
class WebformClientForm extends FormBase {

  /**
   * The node the client form belongs to.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * An object containing information about the form submission.
   *
   * @var object
   */
  protected $submission;

  /**
   * Is a draft being resumed.
   *
   * @var boolean
   */
  protected $resume_draft;

  /**
   * Should the contents of descriptions and values be filtered.
   *
   * @var boolean
   */
  protected $filter;

  /**
   * Constructs a new WebformClientForm.
   *
   * If this is displaying an existing submission, pass in the $submission
   * variable with the contents of the submission to be displayed.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node the client form belongs to.
   * @param bool|object $submission
   *   (optional) An object containing information about the form submission if
   *   we're displaying a result.
   * @param boolean $resume_draft
   *   (optional) Set to TRUE when resuming a draft and skipping past
   *   previously validated pages is desired.
   * @param boolean $filter
   *   (optional) Whether or not to filter the contents of descriptions and
   *   values when building the form. Values need to be unfiltered to be
   *   editable by Form Builder.
   */
  public function __construct(NodeInterface $node, $submission = FALSE, $resume_draft = FALSE, $filter = TRUE) {
    $this->node = $node;
    $this->submission = $submission;
    $this->resume_draft = $resume_draft;
    $this->filter = $filter;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_client_form_' . $this->node->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account = \Drupal::currentUser();

    $form = [
      '#attached' => [
        'library' => [
          'webform/webform',
        ],
      ],
    ];

    $form_state->loadInclude('webform', 'inc', 'includes/webform.components');
    $form_state->loadInclude('webform', 'inc', 'includes/webform.submissions');

    $node = $this->node;
    $submission = $this->node;
    $resume_draft = $this->resume_draft;
    $filter = $this->filter;

    // If in a multi-step form, a submission ID may be specified in form state.
    // Load this submission. This allows anonymous users to use auto-save.
    if (empty($submission) && !empty($form_state->getValue(['details', 'sid']))) {
      $submission = webform_get_submission($node->id(), $form_state->getValue(['details', 'sid']));
    }

    $finished = isset($submission->is_draft) ? (!$submission->is_draft) : 0;
    $submit_button_text = $finished
      ? $this->t('Save')
      : (empty($node->webform['submit_text']) ? $this->t('Submit') : $node->webform['submit_text']);

    // Bind arguments to $form to make them available in theming and form_alter.
    $form['#node'] = $node;
    $form['#submission'] = $submission;
    $form['#filter'] = $filter;

    // Add a theme function for this form.
    $form['#theme'] = ['webform_form_' . $node->id(), 'webform_form'];

    // Add a CSS class for all client forms.
    $form['#attributes']['class'][] = 'webform-client-form';
    $form['#attributes']['class'][] = 'webform-client-form-' . $node->id();

    // Set the encoding type (necessary for file uploads).
    $form['#attributes']['enctype'] = 'multipart/form-data';

    // Sometimes when displaying a webform as a teaser or block, a custom action
    // property is set to direct the user to the node page.
    if (!empty($node->webform['action'])) {
      $form['#action'] = $node->webform['action'];
    }

    // @todo Convert these to methods.
    $form['#submit'] = ['webform_client_form_pages', 'webform_client_form_submit'];
    $form['#validate'] = ['webform_client_form_validate'];
    // Add includes for used component types and pre/post validation handlers
    $form['#process'] = ['webform_client_form_process'];

    if (is_array($node->webform['components']) && !empty($node->webform['components'])) {
      // Prepare a new form array.
      $form['submitted'] = [
        '#tree' => TRUE
      ];
      $form['details'] = [
        '#tree' => TRUE,
      ];

      // Put the components into a tree structure.
      if (!isset($form_state->getStorage()['component_tree'])) {
        $form_state->set(['webform', 'component_tree'], []);
        $form_state->set(['webform', 'page_count'], 1);
        $form_state->set(['webform', 'page_num'], 1);
        _webform_components_tree_build($node->webform['components'], $form_state->get(['webform', 'component_tree']), 0, $form_state->get(['webform', 'page_count']));

        // If preview is enabled, increase the page count by one.
        if ($node->webform['preview']) {
          $form_state->set(['webform', 'page_count'], ($form_state->get(['webform', 'page_count']) + 1));
        }
        $form_state->set(['webform', 'preview'], $node->webform['preview']);

        // If this is the first time this draft has been restore and presented
        // to the user, let them know that they are looking at a draft, rather
        // than a new form. This applies to the node view page, but not to a
        // submission edit page (where they presumably know what submission they
        // are editing).
        if ($resume_draft && empty($form_state->getUserInput())) {
          drupal_set_message($this->t('A partially-completed form was found. Please complete the remaining portions.'));
        }
      }
      else {
        $form_state->set(['webform', 'component_tree'], $form_state->getStorage()['component_tree']);
        $form_state->set(['webform', 'page_count'], $form_state->getStorage()['page_count']);
        $form_state->set(['webform', 'page_num'], $form_state->getStorage()['page_num']);
        $form_state->set(['webform', 'preview'], $form_state->getStorage()['preview']);
      }

      // Set the input values based on whether we're editing an existing
      // submission or not.
      $input_values = isset($submission->data) ? $submission->data : [];

      // Form state storage override any default submission information. Convert
      // the value structure to always be an array, matching $submission->data.
      if (isset($form_state->getStorage()['submitted'])) {
        foreach ($form_state->getStorage()['submitted'] as $cid => $data) {
          $input_values[$cid] = is_array($data) ? $data : [$data];
        }
      }

      // Form state values override any default submission information. Convert
      // the value structure to always be an array, matching $submission->data.
      if ($form_state->getValue('submitted')) {
        foreach ($form_state->getValue('submitted') as $cid => $data) {
          $input_values[$cid] = is_array($data) ? $data : [$data];
        }
      }

      // Generate conditional topological order & report any errors.
      $sorter = webform_get_conditional_sorter($node);
      $sorter->reportErrors();

      // Execute the conditionals on the current input values
      $input_values = $sorter->executeConditionals($input_values);

      // Allow values from other pages to be sent to browser for conditionals.
      $form['#conditional_values'] = $input_values;

      // For resuming a previous draft, find the next page after the last
      // validated page.
      if (!isset($form_state->getStorage()['page_num']) && $submission && $submission->is_draft && $submission->highest_valid_page) {
        // Find the
        //    1) previous/next non-empty page, or
        //    2) the preview page, or
        //    3) the preview page, forcing its display if the form would
        //       unexpectedly submit, or
        //    4) page 1 even if empty, if no other previous page would be shown
        $form_state->set(['webform', 'page_num'], $submission->highest_valid_page);
        do {
          $form_state->set(['webform', 'page_num'], ($form_state->get(['webform', 'page_num']) + 1));
        } while (!webform_get_conditional_sorter($node)->pageVisibility($form_state->get(['webform', 'page_num'])));
        if (!$form_state->get(['webform', 'preview']) && $form_state->get(['webform', 'page_num']) == $form_state->get(['webform', 'page_count']) + (int)!$form_state->get(['webform', 'preview'])) {
          // Force a preview to avert an unintended submission via Next.
          $form_state->set(['webform', 'preview'], TRUE);
          $form_state->set(['webform', 'page_count'], ($form_state->get(['webform', 'page_count']) + 1));
          // The form hasn't been submitted (ever) and the preview code will
          // expect $form_state['values']['submitted'] to be set from a previous
          // submission, so provide these values here.
          $form_state->setValue('submitted', $input_values);
        }
        $form_state->setStorage(['submitted' => $input_values]);
      }

      // Shorten up our variable names.
      $component_tree = $form_state->get(['webform', 'component_tree']);
      $page_count = $form_state->get(['webform', 'page_count']);
      $page_num = $form_state->get(['webform', 'page_num']);
      $preview = $form_state->get(['webform', 'preview']);

      if ($page_count > 1) {
        $page_labels = webform_page_labels($node, $form_state);
        $form['progressbar'] = [
          '#theme' => 'webform_progressbar',
          '#node' => $node,
          '#page_num' => $page_num,
          '#page_count' => count($page_labels),
          '#page_labels' => $page_labels,
          '#weight' => -100,
        ];
      }

      // Check whether a previous submission was truncated. The length of the
      // client form is not estimated before submission because a) the
      // determination may not be accurate for some webform components and b)
      // the error will be apparent upon submission.
      webform_input_vars_check($form, $form_state, 'submitted');

      // Recursively add components to the form. The unfiltered version of the
      // form (typically used in Form Builder), includes all components.
      foreach ($component_tree['children'] as $cid => $component) {
        if ($component['type'] == 'pagebreak') {
          $next_page_labels[$component['page_num'] - 1] = !empty($component['extra']['next_page_label']) ? $component['extra']['next_page_label'] : $this->t('Next Page >');
          $prev_page_labels[$component['page_num']] = !empty($component['extra']['prev_page_label']) ? $component['extra']['prev_page_label'] : $this->t('< Previous Page');
        }
        if (!$filter || $sorter->componentVisibility($cid, $page_num)) {
          $component_value = isset($input_values[$cid]) ? $input_values[$cid] : NULL;
          _webform_client_form_add_component($node, $component, $component_value, $form['submitted'], $form, $input_values, 'form', $page_num, $filter);
        }
      }
      if ($preview) {
        $next_page_labels[$page_count - 1] = $node->webform['preview_next_button_label'] ? $node->webform['preview_next_button_label'] : $this->t('Preview');
        $prev_page_labels[$page_count] = $node->webform['preview_prev_button_label'] ? $node->webform['preview_prev_button_label'] : $this->t('< Previous');
      }

      // Add the preview if needed.
      if ($preview && $page_num === $page_count) {
        $preview_submission = webform_submission_create($node, $account, $form_state, TRUE, $submission);
        $preview_message = $node->webform['preview_message'];
        if (strlen(trim(strip_tags($preview_message))) === 0) {
          $preview_message = $this->t('Please review your submission. Your submission is not complete until you press the "!button" button!', ['!button' => $submit_button_text]);
        }
        $form['preview_message'] = [
          '#type' => 'markup',
          '#markup' => webform_replace_tokens($preview_message, $node, $preview_submission, NULL, $node->webform['preview_message_format']),
        ];

        $form['preview'] = webform_submission_render($node, $preview_submission, NULL, 'html', $node->webform['preview_excluded_components']);
        $form['#attributes']['class'][] = 'preview';
      }

      // These form details help managing data upon submission.
      $form['details']['nid'] = [
        '#type' => 'value',
        '#value' => $node->id(),
      ];
      $form['details']['sid'] = [
        '#type' => 'hidden',
        '#value' => isset($submission->sid) ? $submission->sid : NULL,
      ];
      $form['details']['uid'] = [
        '#type' => 'value',
        '#value' => isset($submission->uid) ? $submission->uid : $account->id(),
      ];
      $form['details']['page_num'] = [
        '#type'  => 'hidden',
        '#value' => $page_num,
      ];
      $form['details']['page_count'] = [
        '#type'  => 'hidden',
        '#value' => $page_count,
      ];
      $form['details']['finished'] = [
        '#type' => 'hidden',
        '#value' => $finished,
      ];

      // Add process functions to remove the IDs forced upon buttons and wrappers.
      $actions_pre_render = array_merge(element_info_property('actions', '#pre_render', []), ['webform_pre_render_remove_id']);
      $buttons_pre_render = array_merge(element_info_property('submit', '#pre_render', []), ['webform_pre_render_remove_id']);

      // Add buttons for pages, drafts, and submissions.
      $form['actions'] = [
        '#type' => 'actions',
        '#weight' => 1000,
        '#pre_render' => $actions_pre_render,
      ];

      // Add the draft button.
      if ($node->webform['allow_draft'] && (empty($submission) || $submission->is_draft) && $account->id() != 0) {
        $form['actions']['draft'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save Draft'),
          '#weight' => -2,
          // Prevalidation only; no element validation for Save Draft.
          '#validate' => ['webform_client_form_prevalidate'],
          '#attributes' => [
            'formnovalidate' => 'formnovalidate',
            'class' => ['webform-draft'],
          ],
          '#pre_render' => $buttons_pre_render,
        ];
      }

      // Add the submit button(s).
      if ($page_num > 1) {
        $form['actions']['previous'] = [
          '#type' => 'submit',
          '#value' => $prev_page_labels[$page_num],
          '#weight' => 5,
          '#validate' => [],
          '#attributes' => [
            'formnovalidate' => 'formnovalidate',
            'class' => ['webform-previous'],
          ],
          '#pre_render' => $buttons_pre_render,
        ];
      }
      if ($page_num == $page_count) {
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $submit_button_text,
          '#weight' => 10,
          '#attributes' => [
            'class' => ['webform-submit', 'button-primary'],
          ],
          '#pre_render' => $buttons_pre_render,
        ];
      }
      elseif ($page_num < $page_count) {
        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $next_page_labels[$page_num],
          '#weight' => 10,
          '#attributes' => [
            'class' => ['webform-next', 'button-primary'],
          ],
          '#pre_render' => $buttons_pre_render,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
