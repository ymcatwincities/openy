<?php

/**
 * @file
 * Views integration for the file_entity module.
 */

/**
 * Implements hook_views_data().
 */
function file_entity_views_data_alter(&$data) {
  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['file_managed']['table']['group'] = t('Media');

  // Add access tag for all queries against file_managed.
  $data['file_managed']['table']['base']['access query tag'] = 'file_access';

  // File Name.
  $data['file_managed']['filename'] = array(
    'title' => t('Filename'),
    'help' => t('File name with optional link to view.'),
    'field' => array(
      'id' => 'file_name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // File type.
  $data['file_managed']['type'] = array(
    'title' => t('Type'),
    'help' => t('The type of the file (for example, "audio", "image", "video", etc).'),
    'field' => array(
      'id' => 'file_entity_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'bundle',
    ),
  );

  // File schema type.
  $data['file_managed']['schema_type'] = array(
    'title' => t('Schema type'),
    'help' => t('Filter files by schema, such as public or private.'),
    'filter' => array(
      'handler' => 'views_handler_filter_schema_type',
    ),
  );

  // Rendered file.
  $data['file_managed']['rendered'] = array(
    'title' => t('Rendered'),
    'help' => t('Display the file in a specific view mode.'),
    'field' => array(
      'handler' => 'views_handler_field_file_rendered',
      'click sortable' => TRUE,
      'real field' => 'fid',
      'additional fields' => array(
        'fid',
      ),
    ),
  );

  // View link.
  $data['file_managed']['link'] = array(
    'title' => t('Link'),
    'help' => t('Provide a simple link to the file entity.'),
    'field' => array(
      'handler' => 'views_handler_field_file_link',
      'real field' => 'fid',
      'additional fields' => array(
        'fid',
      ),
    ),
  );

  // View link.
  $data['file_managed']['view'] = array(
    'field' => array(
      'title' => t('Link to file'),
      'help' => t('Provide a simple link to the file.'),
      'id' => 'file_entity_link',
    ),
  );

  // Edit link.
  $data['file_managed']['edit'] = array(
    'title' => t('Edit link'),
    'help' => t('Provide a simple link to edit the file entity.'),
    'field' => array(
      'id' => 'file_entity_link_edit',
      'real field' => 'fid',
    ),
  );

  // Delete link.
  $data['file_managed']['delete'] = array(
    'title' => t('Delete link'),
    'help' => t('Provide a simple link to delete the file entity.'),
    'field' => array(
      'id' => 'file_entity_link_delete',
      'real field' => 'fid',
    ),
  );

  // Download link.
  $data['file_managed']['download'] = array(
    'title' => t('Download link'),
    'help' => t('Provide a simple link to download the file entity.'),
    'field' => array(
      'id' => 'file_entity_link_download',
      'real field' => 'fid',
    ),
  );

  // Usage link.
  $data['file_managed']['usage'] = array(
    'title' => t('Usage link'),
    'help' => t('Provide a simple link to view the usage of the file entity.'),
    'field' => array(
      'handler' => 'views_handler_field_file_link_usage',
      'click sortable' => TRUE,
      'real field' => 'fid',
      'additional fields' => array(
        'fid',
      ),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['file_managed']['bulk_form'] = array(
    'title' => 'File operations bulk form',
    'help' => 'Form elements to perform operations on multiple files at once.',
    'field' => array(
      'id' => 'bulk_form',
    ),
  );

  // @todo This should really be added in file.views.inc
  $data['file_usage']['table']['join'] = array(
    'file_managed' => array(
      'field' => 'fid',
      'left_field' => 'fid',
    ),
  );
}
