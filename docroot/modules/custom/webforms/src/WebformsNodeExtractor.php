<?php

namespace Drupal\webforms;

use Drupal\contact\Entity\Message;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Allows to extract referenced entity from EmailOptions field type.
 */
class WebformsNodeExtractor {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Node Extractor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Type Manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Extracts a node, referenced by title.
   *
   * @param \Drupal\contact\Entity\Message $submission
   *   Submission object.
   * @param string $field_name
   *   Field name, containing reference.
   * @param string $bundle
   *   Bundle of the node to be extracted.
   *
   * @return mixed
   *   An instance of \Drupal\node\Entity\Node or null.
   */
  public function extractNode(Message $submission, $field_name, $bundle) {
    $reference_field_value = $submission->{$field_name}->getValue();

    $field_definition = $submission->getFieldDefinition($field_name);
    $field_default_values = $field_definition->getDefaultValue($submission);
    $node_title = $field_default_values[$reference_field_value['0']['option_emails']]['option_name'];

    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'title' => $node_title,
        'type' => $bundle,
      ]);

    if (!$nodes) {
      return NULL;
    }
    $node = reset($nodes);

    return $node;
  }

}
