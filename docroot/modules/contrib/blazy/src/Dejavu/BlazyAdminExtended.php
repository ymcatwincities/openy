<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Url;
use Drupal\blazy\Form\BlazyAdminFormatterBase;

/**
 * Provides re-usable admin functions, or form elements.
 */
class BlazyAdminExtended extends BlazyAdminFormatterBase {

  /**
   * Returns shared form elements across field formatter and Views.
   */
  public function openingForm(array &$form, $definition = []) {
    if (!isset($definition['namespace'])) {
      return;
    }

    $namespace = $definition['namespace'];

    if (!empty($definition['vanilla'])) {
      $form['vanilla'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Vanilla @namespace', ['@namespace' => $namespace]),
        '#description' => $this->t('<strong>Check</strong>:<ul><li>To render individual item as is as without extra logic.</li><li>To disable 99% @module features, and most of the mentioned options here, such as layouts, et al.</li><li>When the @module features can not satisfy the need.</li><li>Things may be broken! You are on your own.</li></ul><strong>Uncheck</strong>:<ul><li>To get consistent markups and its advanced features -- relevant for the provided options as @module needs to know what to style/work with.</li></ul>', ['@module' => $namespace]),
        '#weight'      => -109,
        '#enforced'    => TRUE,
        '#attributes'  => ['class' => ['form-checkbox--vanilla']],
        '#wrapper_attributes' => ['class' => ['form-item--full', 'form-item--tooltip-bottom']],
      ];
    }

    if (!empty($definition['optionsets']) && $namespace != 'blazy') {
      $form['optionset'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Optionset'),
        '#options'     => $definition['optionsets'],
        '#enforced'    => TRUE,
        '#description' => $this->t('Enable the optionset UI module to manage the optionsets.'),
        '#weight'      => -108,
      ];

      if ($this->blazyManager()->getModuleHandler()->moduleExists($namespace . '_ui')) {
        $form['optionset']['#description'] = $this->t('Manage optionsets at <a href=":url" target="_blank">the optionset admin page</a>.', [':url' => Url::fromRoute('entity.' . $namespace . '.collection')->toString()]);
      }
    }

    parent::openingForm($form, $definition);
  }

  /**
   * Returns re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, $definition = []) {
    if (isset($definition['images'])) {
      $form['image'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Main stage'),
        '#options'     => is_array($definition['images']) ? $definition['images'] : [],
        '#description' => $this->t('Main background/stage image field.'),
        '#prefix'      => '<h3 class="form__title form__title--fields">' . $this->t('Fields') . '</h3>',
      ];
    }

    if (isset($definition['thumbnails'])) {
      $form['thumbnail'] = array(
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail image'),
        '#options'     => is_array($definition['thumbnails']) ? $definition['thumbnails'] : [],
        '#description' => t("Leave empty to not use thumbnail pager."),
      );
    }

    if (isset($definition['overlays'])) {
      $form['overlay'] = array(
        '#type'        => 'select',
        '#title'       => $this->t('Overlay media'),
        '#options'     => is_array($definition['overlays']) ? $definition['overlays'] : [],
        '#description' => $this->t('Overlay is displayed over the main main.'),
      );
    }

    if (isset($definition['titles'])) {
      $form['title'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Title'),
        '#options'     => is_array($definition['titles']) ? $definition['titles'] : [],
        '#description' => $this->t('If provided, it will bre wrapped with H2.'),
      ];
    }

    if (isset($definition['links'])) {
      $form['link'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Link'),
        '#options'     => is_array($definition['links']) ? $definition['links'] : [],
        '#description' => $this->t('Link to content: Read more, View Case Study, etc.'),
      ];
    }

    if (isset($definition['classes'])) {
      $form['class'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Item class'),
        '#options'     => is_array($definition['classes']) ? $definition['classes'] : [],
        '#description' => $this->t('If provided, individual item will have this class, e.g.: to have different background with transparent images. Be sure its formatter is Key or Label. Accepted field types: list text, string (e.g.: node title), term/entity reference label.'),
        '#weight'      => 6,
      ];
    }

    if (!empty($definition['id'])) {
      $form['id'] = [
        '#type'         => 'textfield',
        '#title'        => $this->t('@namespace ID', ['@namespace' => $definition['namespace']]),
        '#size'         => 40,
        '#maxlength'    => 255,
        '#field_prefix' => '#',
        '#enforced'     => TRUE,
        '#description'  => t("Manually define the container ID. <em>This ID is used for the cache identifier, so be sure it is unique</em>. Leave empty to have a guaranteed unique ID managed by the module."),
        '#weight'       => 94,
      ];
    }

    if (isset($form['caption'])) {
      $form['caption']['#description'] = $this->t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.');
    }

    if (!isset($definition['id'])) {
      if (isset($form['caption'])) {
        $form['caption']['#description'] .= ' ' . $this->t('Be sure to make them visible at their relevant Manage display.');
      }
    }
    else {
      if (isset($form['overlay'])) {
        $form['overlay']['#description'] .= ' ' . $this->t('Be sure to CHECK "Use field template" under its formatter if using Slick field formatter.');
      }
    }
  }

  /**
   * Returns shared ending form elements across field formatter and Views.
   */
  public function closingForm(array &$form, $definition = []) {
    if (!empty($definition['caches'])) {
      $form['cache'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Cache'),
        '#options'     => $this->getCacheOptions(),
        '#weight'      => 98,
        '#enforced'    => TRUE,
        '#description' => $this->t('Ditch all the logic to cached bare HTML. <ol><li><strong>Permanent</strong>: cached contents will persist (be displayed) till the next cron runs.</li><li><strong>Any number</strong>: expired by the selected expiration time, and fresh contents are fetched till the next cache rebuilt.</li></ol>A working cron job is required to clear stale cache. At any rate, cached contents will be refreshed regardless of the expiration time after the cron hits. <br />Leave it empty to disable caching.<br /><strong>Warning!</strong> Be sure no useless/ sensitive data such as Edit links as they are rendered as is regardless permissions. No permissions are changed, just ugly. Only enable it when all is done, otherwise cached options will be displayed while changing them.'),
      ];

      if (!empty($definition['_views'])) {
        $form['cache']['#description'] .= ' ' . $this->t('Also disable Views cache (<strong>Advanced &gt; Caching</strong>) temporarily _only if trouble to see updated settings.');
      }
    }

    parent::closingForm($form, $definition);
  }

}
