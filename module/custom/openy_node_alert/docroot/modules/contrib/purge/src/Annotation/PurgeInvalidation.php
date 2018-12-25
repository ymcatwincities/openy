<?php

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgeInvalidation annotation object.
 *
 * @Annotation
 */
class PurgeInvalidation extends Plugin {

  /**
   * The plugin ID of the invalidation type.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the invalidation type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the invalidation type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Example expression strings that this type supports, shown to the user in
   * user interfaces as usability hints. Leave empty for types that don't
   * need expressions for instantiation, see $expression_required.
   *
   * @var string[]
   */
  public $examples = [];

  /**
   * Whether invalidation objects of this type require a string expression that
   * describes what needs to be purged. If put to FALSE, the fact this type is
   * instantiated is deemed enough information for purgers to execute it.
   *
   * @var bool
   */
  public $expression_required = TRUE;

  /**
   * When expression_required = TRUE, this determines whether a string
   * expression can be equal to "" (empty string). If FALSE, this invalidation
   * type can not be instantiated with empty expression strings.
   *
   * @var bool
   */
  public $expression_can_be_empty = FALSE;

  /**
   * When expression got passed but when it is not a string, this will result in
   * an error when its set to TRUE.
   *
   * @var bool
   */
  public $expression_must_be_string = FALSE;

}
