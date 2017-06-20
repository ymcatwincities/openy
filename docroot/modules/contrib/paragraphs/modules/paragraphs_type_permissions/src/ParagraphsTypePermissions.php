<?php

namespace Drupal\paragraphs_type_permissions;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Defines a class containing permission callbacks.
 */
class ParagraphsTypePermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of content permissions.
   *
   * @return array
   */
  public function globalPermissions() {
    return array(
      'bypass paragraphs type content access' => array(
        'title' => $this->t('Bypass paragraphs type content access control'),
        'description' => $this->t('Is able to administer content for all paragraph types'),
      ),
    );
  }

  /**
   * Returns an array of paragraphs type permissions.
   *
   * @return array
   */
  public function paragraphTypePermissions() {
    $perms = array();

    // Generate paragraph permissions for all paragraphs types.
    foreach (ParagraphsType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of node permissions for a given type.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphsType $type
   *   The machine name of the node type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(ParagraphsType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      'view paragraph content ' .$type_id => array(
        'title' => $this->t('%type_name: View content', $type_params),
        'description' => $this->t('Is able to view paragraphs content of type %type_name', $type_params),
      ),
      'create paragraph content ' . $type_id => array(
        'title' => $this->t('%type_name: Create content', $type_params),
        'description' => $this->t('Is able to create paragraphs content of type %type_name', $type_params),
      ),
      'update paragraph content ' . $type_id => array(
        'title' => $this->t('%type_name: Edit content', $type_params),
        'description' => $this->t('Is able to update paragraphs content of type %type_name', $type_params),
      ),
      'delete paragraph content ' . $type_id => array(
        'title' => $this->t('%type_name: Delete content', $type_params),
        'description' => $this->t('Is able to delete paragraphs content of type %type_name', $type_params),
      ),
    );
  }

}
