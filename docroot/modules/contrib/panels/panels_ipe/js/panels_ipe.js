/**
 * @file
 * Attaches behavior for the Panels IPE module.
 *
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  /**
   * Contains initial Backbone initialization for the IPE.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.panels_ipe = {
    attach: function (context, settings) {
      // Perform initial setup of our app.
      $('body').once('panels-ipe-init').each(Drupal.panels_ipe.init, [settings]);

      // @todo Make every settings-related thing a generic event, or add a
      // panels_ipe event command to Drupal.ajax.

      // We need to add/update a new BlockModel somewhere. Inform the App that
      // this has occurred.
      if (settings['panels_ipe']['updated_block']) {
        var data = settings['panels_ipe']['updated_block'];
        // Remove the setting.
        delete settings['panels_ipe']['updated_block'];
        // Create a BlockModel.
        var block = new Drupal.panels_ipe.BlockModel(data);
        // Trigger the event.
        Drupal.panels_ipe.app.trigger('addBlockPlugin', block, data.region);
      }

      // We need to add/update our Layout Inform the App that this has occurred.
      if (settings['panels_ipe']['updated_layout']) {
        var data = settings['panels_ipe']['updated_layout'];
        // Remove the setting.
        delete settings['panels_ipe']['updated_layout'];
        // Create a LayoutModel.
        data = Drupal.panels_ipe.LayoutModel.prototype.parse(data);
        var layout = new Drupal.panels_ipe.LayoutModel(data);
        // Trigger the event.
        Drupal.panels_ipe.app.trigger('changeLayout', layout);
      }

      // Toggle the preview - We need to do this with drupalSettings as the
      // animation won't work if triggered by a form submit. It must occur after
      // the form is rendered.
      if (context.className == 'panels-ipe-block-plugin-form flip-container'
        && settings['panels_ipe']['toggle_preview']) {
        var $form = $('.ipe-block-plugin-form');

        // Flip the form.
        $form.toggleClass('flipped');

        // Calculate and set new heights, if appropriate.
        Drupal.panels_ipe.setFlipperHeight($form);

        // As images can load late on new content, recalculate the flipper
        // height on image load.
        $form.find('img').each(function() {
          $(this).load(function() {
            Drupal.panels_ipe.setFlipperHeight($form);
          });
        });

        delete settings['panels_ipe']['toggle_preview'];
      }

      // A new Block Content entity has been created. Trigger an app-level event
      // to switch tabs and open the placement form.
      if (settings['panels_ipe']['new_block_content']) {
        Drupal.panels_ipe.app.trigger('addContentBlock', settings['panels_ipe']['new_block_content']);
        delete settings['panels_ipe']['new_block_content'];
      }
    }
  };

  /**
   * @namespace
   */
  Drupal.panels_ipe = {};

  /**
   * Setups up our initial Collection and Views based on the current settings.
   *
   * @param {Object} settings
   *   The contextual drupalSettings.
   */
  Drupal.panels_ipe.init = function (settings) {
    // Set up our initial tabs.
    var tab_collection = new Drupal.panels_ipe.TabCollection();

    if (settings.panels_ipe.layout.changeable) {
      tab_collection.add({title: 'Change Layout', id: 'change_layout'});
    }
    tab_collection.add({title: 'Create Content', id: 'create_content'});
    tab_collection.add({title: 'Place Content', id: 'place_content'});

    // The edit/save/cancel tabs are special, and are tracked by our app.
    var edit_tab = new Drupal.panels_ipe.TabModel({title: 'Edit', id: 'edit'});
    var save_tab = new Drupal.panels_ipe.TabModel({title: 'Save', id: 'save'});
    var cancel_tab = new Drupal.panels_ipe.TabModel({title: 'Cancel', id: 'cancel'});
    tab_collection.add(edit_tab);
    tab_collection.add(save_tab);
    tab_collection.add(cancel_tab);

    // Create a global(ish) AppModel.
    Drupal.panels_ipe.app = new Drupal.panels_ipe.AppModel({
      tabCollection: tab_collection,
      editTab: edit_tab,
      saveTab: save_tab,
      cancelTab: cancel_tab,
      unsaved: settings.panels_ipe.unsaved
    });

    // Set up our initial tab views.
    var tab_views = {};
    if (settings.panels_ipe.layout.changeable) {
      tab_views.change_layout = new Drupal.panels_ipe.LayoutPicker();
    }
    tab_views.create_content = new Drupal.panels_ipe.BlockContentPicker();
    tab_views.place_content = new Drupal.panels_ipe.BlockPicker();

    // Create an AppView instance.
    Drupal.panels_ipe.app_view = new Drupal.panels_ipe.AppView({
      model: Drupal.panels_ipe.app,
      el: '#panels-ipe-tray',
      tabContentViews: tab_views
    });

    // Assemble the initial region and block collections.
    // This logic is a little messy, as traditionally we would never initialize
    // Backbone with existing HTML content.
    var region_collection = new Drupal.panels_ipe.RegionCollection();
    for (var i in settings.panels_ipe.regions) {
      if (settings.panels_ipe.regions.hasOwnProperty(i)) {
        var region = new Drupal.panels_ipe.RegionModel();
        region.set(settings.panels_ipe.regions[i]);

        var block_collection = new Drupal.panels_ipe.BlockCollection();
        for (var j in settings.panels_ipe.regions[i].blocks) {
          if (settings.panels_ipe.regions[i].blocks.hasOwnProperty(j)) {
            // Add a new block model.
            var block = new Drupal.panels_ipe.BlockModel();
            block.set(settings.panels_ipe.regions[i].blocks[j]);
            block_collection.add(block);
          }
        }

        region.set({blockCollection: block_collection});

        region_collection.add(region);
      }
    }

    // Create the Layout model/view.
    var layout = new Drupal.panels_ipe.LayoutModel(settings.panels_ipe.layout);
    layout.set({regionCollection: region_collection});
    var layout_view = new Drupal.panels_ipe.LayoutView({
      model: layout,
      el: '#panels-ipe-content'
    });

    Drupal.panels_ipe.app.set({layout: layout});
    Drupal.panels_ipe.app_view.layoutView = layout_view;

    // Trigger a global Backbone event informing other Views that we're done
    // initializing and ready to render.
    Backbone.trigger('PanelsIPEInitialized');

    // Render our AppView.
    $('body').append(Drupal.panels_ipe.app_view.render().$el);
  };

  Drupal.panels_ipe.setFlipperHeight = function ($form) {
    // The preview could be larger than the form.
    // Manually set the height to be sure that things fit.
    var $new_side, $current_side;
    if ($form.hasClass('flipped')) {
      $new_side = $form.find('.flipper > .back');
      $current_side = $form.find('.flipper > .front');
    }
    else {
      $new_side = $form.find('.flipper > .front');
      $current_side = $form.find('.flipper > .back');
    }

    // If the new side is larger than the current side, change the height.
    if ($new_side.outerHeight() > $current_side.outerHeight()) {
      $current_side.animate({height: $new_side.outerHeight() + 10}, 600);
    }
  };

  /**
   * Returns the urlRoot for all callbacks
   *
   * @param {Object} settings
   *   The contextual drupalSettings.
   *
   * @return {string}
   *   A base path for most other URL callbacks in this App.
   */
  Drupal.panels_ipe.urlRoot = function (settings) {
    var panels_display = settings.panels_ipe.panels_display;
    return settings.path.baseUrl + 'admin/panels_ipe/variant/' + panels_display.storage_type + '/' + panels_display.storage_id;
  };

}(jQuery, _, Backbone, Drupal));
