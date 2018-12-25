<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the options for scheduling dates to be required during add/edit.
 *
 * @group scheduler
 */
class SchedulerRequiredTest extends SchedulerBrowserTestBase {

  /**
   * Tests creating and editing nodes with required scheduling enabled.
   */
  public function testRequiredScheduling() {
    $this->drupalLogin($this->schedulerUser);

    // Define test scenarios with expected results.
    // @TODO Re-write this with a dataProvider function.
    $test_cases = [
      // The 1-10 numbering used below matches the test cases described in
      // http://drupal.org/node/1198788#comment-7816119
      //
      [
        'id' => 0,
        'required' => '',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'not required',
        'message' => 'By default when a new node is created, the publish on and unpublish on dates are not required.',
      ],
      // A. Test scenarios that require scheduled publishing.
      // When creating a new unpublished node it is required to enter a
      // publication date.
      [
        'id' => 1,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new unpublished node is created, entering a date in the publish on field is required.',
      ],

      // When creating a new published node it is required to enter a
      // publication date. The node will be unpublished on form submit.
      [
        'id' => 2,
        'required' => 'publish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and a new published node is created, entering a date in the publish on field is required.',
      ],

      // When editing a published node it is not needed to enter a publication
      // date since the node is already published.
      [
        'id' => 3,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing published, unscheduled node is edited, entering a date in the publish on field is not required.',
      ],

      // When editing an unpublished node that is scheduled for publication it
      // is required to enter a publication date.
      [
        'id' => 4,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled publishing is required and an existing unpublished, scheduled node is edited, entering a date in the publish on field is required.',
      ],

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter a publication date since this means that
      // the node has already gone through a publication > unpublication cycle.
      [
        'id' => 5,
        'required' => 'publish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled publishing is required and an existing unpublished, unscheduled node is edited, entering a date in the publish on field is not required.',
      ],

      // B. Test scenarios that require scheduled unpublishing.
      // When creating a new unpublished node it is required to enter an
      // unpublication date since it is to be expected that the node will be
      // published at some point and should subsequently be unpublished.
      [
        'id' => 6,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new unpublished node is created, entering a date in the unpublish on field is required.',
      ],

      // When creating a new published node it is required to enter an
      // unpublication date.
      [
        'id' => 7,
        'required' => 'unpublish',
        'operation' => 'add',
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and a new published node is created, entering a date in the unpublish on field is required.',
      ],

      // When editing a published node it is required to enter an unpublication
      // date.
      [
        'id' => 8,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 1,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing published, unscheduled node is edited, entering a date in the unpublish on field is required.',
      ],

      // When editing an unpublished node that is scheduled for publication it
      // it is required to enter an unpublication date.
      [
        'id' => 9,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 1,
        'status' => 0,
        'expected' => 'required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, scheduled node is edited, entering a date in the unpublish on field is required.',
      ],

      // When editing an unpublished node that is not scheduled for publication
      // it is not required to enter an unpublication date since this means that
      // the node has already gone through a publication - unpublication cycle.
      [
        'id' => 10,
        'required' => 'unpublish',
        'operation' => 'edit',
        'scheduled' => 0,
        'status' => 0,
        'expected' => 'not required',
        'message' => 'When scheduled unpublishing is required and an existing unpublished, unscheduled node is edited, entering a date in the unpublish on field is not required.',
      ],
    ];

    $fields = \Drupal::entityManager()->getFieldDefinitions('node', $this->type);

    foreach ($test_cases as $test_case) {
      // Set required (un)publishing as stipulated by the test case.
      if (!empty($test_case['required'])) {
        $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', $test_case['required'] == 'publish')
          ->setThirdPartySetting('scheduler', 'unpublish_required', $test_case['required'] == 'unpublish')
          ->save();
      }

      // To assist viewing and analysing the generated test result pages create
      // a text string showing all the test case parameters.
      $title_data = [];
      foreach ($test_case as $key => $value) {
        if ($key != 'message') {
          $title_data[] = $key . ' = ' . $value;
        }
      }
      $title = implode(', ', $title_data);

      // If the test case requires editing a node, we need to create one first.
      if ($test_case['operation'] == 'edit') {
        // Note: The key names in the $options parameter for drupalCreateNode()
        // are the plain field names i.e. 'title' not title[0][value].
        $options = [
          'title' => $title,
          'type' => $this->type,
          'status' => $test_case['status'],
          'publish_on' => !empty($test_case['scheduled']) ? strtotime('+1 day') : NULL,
        ];
        $node = $this->drupalCreateNode($options);
        // Define the path and button to use for editing the node.
        $path = 'node/' . $node->id() . '/edit';
      }
      else {
        // Set the default status, used when testing creation of the new node.
        $fields['status']->getConfig($this->type)
          ->setDefaultValue($test_case['status'])
          ->save();
        // Define the path and button to use for creating the node.
        $path = 'node/add/' . $this->type;
      }

      // Make sure that both date fields are empty so we can check if they throw
      // validation errors when the fields are required.
      $values = [
        'title[0][value]' => $title,
        'publish_on[0][value][date]' => '',
        'publish_on[0][value][time]' => '',
        'unpublish_on[0][value][date]' => '',
        'unpublish_on[0][value][time]' => '',
      ];
      // Add or edit the node.
      $this->drupalPostForm($path, $values, t('Save'));

      // Check for the expected result.
      switch ($test_case['expected']) {
        case 'required':
          $string = sprintf('The %s date is required.', ucfirst($test_case['required']) . ' on');
          $this->assertText($string, $test_case['id'] . '. ' . $test_case['message']);
          break;

        case 'not required':
          $string = sprintf('%s %s has been %s.', $this->typeName, SafeMarkup::checkPlain($title), ($test_case['operation'] == 'add' ? 'created' : 'updated'));
          $this->assertText($string, $test_case['id'] . '. ' . $test_case['message']);
          break;
      }
    }
  }

}
