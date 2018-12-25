<?php

namespace Drupal\purge_ui\Form;

use Drupal\purge_ui\Form\PluginConfigFormBase;

/**
 * Provides a base class for processor configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links at
 * /admin/config/development/performance/purge/processor/ID/config/dialog. You
 * can use /admin/config/development/performance/purge/processor/config/ID as
 * testing variant that works outside modal dialogs.
 */
abstract class ProcessorConfigFormBase extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $parent_id = 'edit-queue';

}
