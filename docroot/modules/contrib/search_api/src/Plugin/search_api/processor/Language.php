<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\processor\Language.
 */

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Language\Language as CoreLanguage;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Property\BasicProperty;

/**
 * @SearchApiProcessor(
 *   id = "language",
 *   label = @Translation("Language"),
 *   description = @Translation("Adds the item language to indexed items."),
 *   stages = {
 *     "preprocess_index" = -30
 *   },
 *   locked = true,
 *   hidden = true
 * )
 */
class Language extends ProcessorPluginBase {

  // @todo Config form for setting the field containing the langcode if
  //   language() is not available?

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $definition = array(
      'type' => 'string',
      'label' => $this->t('Item language'),
      'description' => $this->t('The language code of the item'),
    );
    $properties['search_api_language'] = BasicProperty::createFromDefinition($definition)
      ->setIndexedLocked()
      ->setTypeLocked();
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      if (!($field = $item->getField('search_api_language'))) {
        continue;
      }
      $object = $item->getOriginalObject();
      // Workaround for recognizing entities.
      if ($object instanceof EntityAdapter) {
        $object = $object->getValue();
      }
      if ($object instanceof TranslatableInterface) {
        $field->addValue($object->language()->getId());
      }
      else {
        $field->addValue(CoreLanguage::LANGCODE_NOT_SPECIFIED);
      }
    }
  }

}
