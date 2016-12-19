<?php

namespace Drupal\metatag;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class MetatagManager.
 *
 * @package Drupal\metatag
 */
class MetatagManager implements MetatagManagerInterface {

  protected $groupPluginManager;
  protected $tagPluginManager;

  protected $tokenService;

  /**
   * Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor for MetatagManager.
   *
   * @param MetatagGroupPluginManager $groupPluginManager
   * @param MetatagTagPluginManager $tagPluginManager
   * @param MetatagToken $token
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   */
  public function __construct(MetatagGroupPluginManager $groupPluginManager,
                              MetatagTagPluginManager $tagPluginManager,
                              MetatagToken $token,
                              LoggerChannelFactoryInterface $channelFactory) {
    $this->groupPluginManager = $groupPluginManager;
    $this->tagPluginManager = $tagPluginManager;
    $this->tokenService = $token;
    $this->logger = $channelFactory->get('metatag');
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromEntity(ContentEntityInterface $entity) {
    $tags = [];

    $fields = $this->getFields($entity);

    /* @var FieldConfig $field_info */
    foreach ($fields as $field_name => $field_info) {
      // Get the tags from this field.
      $tags = $this->getFieldTags($entity, $field_name);
    }

    return $tags;
  }

  /**
   * Gets the group plugin definitions.
   *
   * @return array
   *   Group definitions
   */
  protected function groupDefinitions() {
    return $this->groupPluginManager->getDefinitions();
  }

  /**
   * Gets the tag plugin definitions.
   *
   * @return array
   *   Tag definitions
   */
  protected function tagDefinitions() {
    return $this->tagPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroups() {
    $metatag_groups = $this->groupDefinitions();

    // Pull the data from the definitions into a new array.
    $groups = [];
    foreach ($metatag_groups as $group_name => $group_info) {
      $id  = $group_info['id'];
      $groups[$id]['label'] = $group_info['label']->render();
      $groups[$id]['description'] = $group_info['description'];
      $groups[$id]['weight'] = $group_info['weight'];
    }

    // Create the 'sort by' array.
    $sort_by = [];
    foreach ($groups as $group) {
      $sort_by[] = $group['weight'];
    }

    // Sort the groups by weight.
    array_multisort($sort_by, SORT_ASC, $groups);

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedTags() {
    $metatag_tags = $this->tagDefinitions();

    // Pull the data from the definitions into a new array.
    $tags = [];
    foreach ($metatag_tags as $tag_name => $tag_info) {
      $id  = $tag_info['id'];
      $tags[$id]['label'] = $tag_info['label']->render();
      $tags[$id]['group'] = $tag_info['group'];
      $tags[$id]['weight'] = $tag_info['weight'];
    }

    // Create the 'sort by' array.
    $sort_by = [];
    foreach ($tags as $key => $tag) {
      $sort_by['group'][$key] = $tag['group'];
      $sort_by['weight'][$key] = $tag['weight'];
    }

    // Sort the tags by weight.
    array_multisort($sort_by['group'], SORT_ASC, $sort_by['weight'], SORT_ASC, $tags);

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroupsWithTags() {
    $groups = $this->sortedGroups();
    $tags = $this->sortedTags();

    foreach ($tags as $tag_id => $tag) {
      $tag_group = $tag['group'];

      if (!isset($groups[$tag_group])) {
        // If the tag is claiming a group that has no matching plugin, log an
        // error and force it to the basic group.
        $this->logger->error("Undefined group '%group' on tag '%tag'", ['%group' => $tag_group, '%tag' => $tag_id]);
        $tag['group'] = 'basic';
        $tag_group = 'basic';
      }

      $groups[$tag_group]['tags'][$tag_id] = $tag;
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $values, array $element, array $token_types = [], array $included_groups = NULL, array $included_tags = NULL) {

    // Add the outer fieldset.
    $element += [
      '#type' => 'details',
    ];

    $element += $this->tokenService->tokenBrowser($token_types);

    $groups_and_tags = $this->sortedGroupsWithTags();

    $first = TRUE;
    foreach ($groups_and_tags as $group_id => $group) {
      // Only act on groups that have tags and are in the list of included
      // groups (unless that list is null).
      if (isset($group['tags']) && (is_null($included_groups) || in_array($group_id, $included_groups))) {
        // Create the fieldset.
        $element[$group_id]['#type'] = 'details';
        $element[$group_id]['#title'] = $group['label'];
        $element[$group_id]['#description'] = $group['description'];
        $element[$group_id]['#open'] = $first;
        $first = FALSE;

        foreach ($group['tags'] as $tag_id => $tag) {
          // Only act on tags in the included tags list, unless that is null.
          if (is_null($included_tags) || in_array($tag_id, $included_tags)) {
            // Make an instance of the tag.
            $tag = $this->tagPluginManager->createInstance($tag_id);

            // Set the value to the stored value, if any.
            $tag_value = isset($values[$tag_id]) ? $values[$tag_id] : NULL;
            $tag->setValue($tag_value);

            // Create the bit of form for this tag.
            $element[$group_id][$tag_id] = $tag->form($element);
          }
        }
      }
    }

    return $element;
  }

  /**
   * Returns a list of the metatag fields on an entity.
   */
  protected function getFields(ContentEntityInterface $entity) {
    $field_list = [];

    if ($entity instanceof ContentEntityInterface) {
      // Get a list of the metatag field types.
      $field_types = $this->fieldTypes();

      // Get a list of the field definitions on this entity.
      $definitions = $entity->getFieldDefinitions();

      // Iterate through all the fields looking for ones in our list.
      foreach ($definitions as $field_name => $definition) {
        // Get the field type, ie: metatag.
        $field_type = $definition->getType();

        // Check the field type against our list of fields.
        if (isset($field_type) && in_array($field_type, $field_types)) {
          $field_list[$field_name] = $definition;
        }
      }
    }

    return $field_list;
  }

  /**
   * Returns a list of the metatags with values from a field.
   *
   * @param $entity
   * @param $field_name
   */
  protected function getFieldTags(ContentEntityInterface $entity, $field_name) {
    $tags = [];
    foreach ($entity->{$field_name} as $item) {
      // Get serialized value and break it into an array of tags with values.
      $serialized_value = $item->get('value')->getValue();
      if (!empty($serialized_value)) {
        $tags += unserialize($serialized_value);
      }
    }

    return $tags;
  }

  /**
   * Generate the elements that go in the attached array in
   * hook_page_attachments.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   * @return array
   *   Render array with tag elements.
   */
  public function generateElements($tags, $entity = NULL) {
    $metatag_tags = $this->tagPluginManager->getDefinitions();
    $elements = [];

    // Each element of the $values array is a tag with the tag plugin name
    // as the key.
    foreach ($tags as $tag_name => $value) {
      // Check to ensure there is a matching plugin.
      if (isset($metatag_tags[$tag_name])) {
        // Get an instance of the plugin.
        $tag = $this->tagPluginManager->createInstance($tag_name);

        // Render any tokens in the value.
        $token_replacements = [];
        if ($entity) {
          $token_replacements = [$entity->getEntityTypeId() => $entity];
        }

        // Set the value as sometimes the data needs massaging, such as when
        // field defaults are used for the Robots field, which come as an array
        // that needs to be filtered and converted to a string.
        // @see @Robots::setValue().
        $tag->setValue($value);
        $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        if ($tag->type() === 'image') {
          $processed_value = $this->tokenService->replace($tag->value(), $token_replacements, ['langcode' => $langcode]);
        }
        else {
          $processed_value = PlainTextOutput::renderFromHtml(htmlspecialchars_decode($this->tokenService->replace($tag->value(), $token_replacements, ['langcode' => $langcode])));
        }

        // Now store the value with processed tokens back into the plugin.
        $tag->setValue($processed_value);

        // Have the tag generate the output based on the value we gave it.
        $output = $tag->output();

        if (!empty($output)) {
          $elements['#attached']['html_head'][] = [
            $output,
            $tag_name
          ];
        }
      }
    }

    return $elements;
  }

  /**
   * Returns a list of fields handled by Metatag.
   *
   * @return array
   */
  protected function fieldTypes() {
    //@TODO: Either get this dynamically from field plugins or forget it and just hardcode metatag where this is called.
    return ['metatag'];
  }

}
