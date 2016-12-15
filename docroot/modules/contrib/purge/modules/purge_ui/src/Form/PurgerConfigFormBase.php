<?php

namespace Drupal\purge_ui\Form;

use Drupal\purge_ui\Form\PluginConfigFormBase;

/**
 * Provides a base class for purger configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links at
 * /admin/config/development/performance/purge/purger/ID/config/dialog. You
 * can use /admin/config/development/performance/purge/purger/config/ID as
 * testing variant that works outside modal dialogs.
 */
abstract class PurgerConfigFormBase extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $parent_id = 'edit-purgers';

}
