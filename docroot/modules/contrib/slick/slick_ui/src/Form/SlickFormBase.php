<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\slick\Entity\Slick;
use Drupal\slick\Form\SlickAdminInterface;
use Drupal\slick\SlickManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base form for a slick instance configuration form.
 */
abstract class SlickFormBase extends EntityForm {

  /**
   * The slick admin service.
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $admin;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * The JS easing options.
   *
   * @var array
   */
  protected $jsEasingOptions;

  /**
   * Constructs a SlickForm object.
   */
  public function __construct(SlickAdminInterface $admin, SlickManagerInterface $manager) {
    $this->admin = $admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('slick.admin'),
      $container->get('slick.manager')
    );
  }

  /**
   * Returns the slick admin service.
   */
  public function admin() {
    return $this->admin;
  }

  /**
   * Returns the slick manager service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate slick optionset</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit slick optionset</em>: @label', ['@label' => $this->entity->label()]);
    }

    $slick     = $this->entity;
    $path      = drupal_get_path('module', 'slick');
    $tooltip   = ['class' => ['is-tooltip']];
    $readme    = Url::fromUri('base:' . $path . '/README.txt')->toString();
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');

    $form['#attributes']['class'][] = 'form--slick';
    $form['#attributes']['class'][] = 'form--blazy';
    $form['#attributes']['class'][] = 'form--optionset';

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $slick->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the Slick optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $slick->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\slick\Entity\Slick::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$slick->isNew(),
      '#suffix'        => '</div>',
    ];

    $form['skin'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Skin'),
      '#options'       => $this->admin->getSkinsByGroupOptions(),
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $slick->getSkin(),
      '#description'   => $this->t('Skins allow swappable layouts like next/prev links, split image and caption, etc. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. See main <a href="@url">README</a> for details on Skins. Only useful for custom work, and ignored/overridden by slick formatters or sub-modules.', ['@url' => $readme]),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--last has-tooltip clearfix">',
    ];

    $form['group'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Group'),
      '#options'       => [
        'main'      => t('Main'),
        'overlay'   => t('Overlay'),
        'thumbnail' => t('Thumbnail'),
      ],
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $slick->getGroup(),
      '#description'   => $this->t('Group this optionset to avoid confusion for optionset selections. Leave empty to make it available for all.'),
      '#attributes'    => $tooltip,
    ];

    $form['breakpoints'] = [
      '#title'         => $this->t('Breakpoints'),
      '#type'          => 'textfield',
      '#default_value' => $form_state->hasValue('breakpoints') ? $form_state->getValue('breakpoints') : $slick->getBreakpoints(),
      '#description'   => $this->t('The number of breakpoints added to Responsive display, max 9. This is not Breakpoint Width (480px, etc).'),
      '#ajax' => [
        'callback' => '::addBreakpoints',
        'wrapper'  => 'edit-breakpoints-ajax-wrapper',
        'event'    => 'change',
        'progress' => ['type' => 'fullscreen'],
        'effect'   => 'fade',
        'speed'    => 'fast',
      ],
      '#attributes' => $tooltip,
      '#maxlength'  => 1,
    ];

    $form['optimized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Optimized'),
      '#default_value' => $slick->optimized(),
      '#description'   => $this->t('Check to optimize the stored options. Anything similar to defaults will not be stored, except those required by sub-modules and theme_slick(). Like you hand-code/ cherry-pick the needed options, and are smart enough to not repeat defaults, and free up memory. The rest are taken care of by JS. Uncheck only if theme_slick() can not satisfy the needs, and more hand-coded preprocess is needed which is less likely in most cases.'),
      '#access'        => $slick->id() != 'default',
      '#attributes'    => $tooltip,
      '#wrapper_attributes' => ['class' => ['form-item--tooltip-wide']],
    ];

    if ($slick->id() == 'default') {
      $form['breakpoints']['#suffix'] = '</div>';
    }
    else {
      $form['optimized']['#suffix'] = '</div>';
    }

    if ($admin_css) {
      $form['optimized']['#field_suffix'] = '&nbsp;';
      $form['optimized']['#title_display'] = 'before';
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Optimized if so configured.
    $slick   = $this->entity;
    $default = $slick->id() == 'default';
    if (!$default && !$form_state->isValueEmpty('optimized')) {
      $defaults = $slick::defaultSettings();
      $options  = $form_state->getValue('options');
      $required = $this->getOptionsRequiredByTemplate();
      $main     = array_diff_assoc($defaults, $required);
      $settings = $form_state->getValue(['options', 'settings']);

      // Cast the values.
      $this->typecastOptionset($settings);

      // Remove wasted dependent options if disabled, empty or not.
      $slick->removeWastedDependentOptions($settings);

      $main_settings = array_diff_assoc($settings, $main);
      $slick->setSettings($main_settings);

      $responsive_options = ['options', 'responsives', 'responsive'];
      if ($responsives = $form_state->getValue($responsive_options)) {
        foreach ($responsives as $delta => &$responsive) {
          if (!empty($responsive['unslick'])) {
            $slick->setResponsiveSettings([], $delta);
          }
          else {
            $this->typecastOptionset($responsive['settings']);
            $slick->removeWastedDependentOptions($responsive['settings']);

            $responsive_settings = array_diff_assoc($responsive['settings'], $defaults);
            $slick->setResponsiveSettings($responsive_settings, $delta);
          }
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @todo revert #1497268, or use config_update instead.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $slick = $this->entity;

    // Prevent leading and trailing spaces in slick names.
    $slick->set('label', trim($slick->label()));
    $slick->set('id', $slick->id());

    $status        = $slick->save();
    $label         = $slick->label();
    $edit_link     = $slick->toLink($this->t('Edit'), 'edit-form')->toString();
    $config_prefix = $slick->getEntityType()->getConfigPrefix();
    $message       = ['@config_prefix' => $config_prefix, '%label' => $label];

    $notice = [
      '@config_prefix' => $config_prefix,
      '%label' => $label,
      'link' => $edit_link,
    ];

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      // @todo #2278383.
      drupal_set_message($this->t('@config_prefix %label has been updated.', $message));
      $this->logger('slick')->notice('@config_prefix %label has been updated.', $notice);
    }
    else {
      // If we created a new entity.
      drupal_set_message($this->t('@config_prefix %label has been added.', $message));
      $this->logger('slick')->notice('@config_prefix %label has been added.', $notice);
    }
  }

  /**
   * Handles switching the breakpoints based on the input value.
   */
  public function addBreakpoints($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('breakpoints')) {
      $form_state->setValue('breakpoints_count', $form_state->getValue('breakpoints'));
      if ($form_state->getValue('breakpoints') >= 6) {
        $message = $this->t('You are trying to load too many Breakpoints. Try reducing it to reasonable numbers say, between 1 to 5.');
        drupal_set_message($message, 'warning');
      }
    }

    return $form['responsives']['responsive'];
  }

  /**
   * Returns the typecast values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public function typecastOptionset(array &$settings = []) {
    if (empty($settings)) {
      return;
    }

    $defaults = Slick::defaultSettings();

    foreach ($defaults as $name => $value) {
      if (isset($settings[$name])) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($defaults[$name]);
        $type = $type == 'double' ? 'float' : $type;

        // Change float to integer if value is no longer float.
        if ($name == 'edgeFriction') {
          $type = $settings[$name] == '1' ? 'integer' : 'float';
        }

        settype($settings[$name], $type);
      }
    }
  }

  /**
   * List of all easing methods available from jQuery Easing v1.3.
   *
   * @return array
   *   An array of available jQuery Easing options as fallback for browsers that
   *   don't support pure CSS easing.
   */
  public function getJsEasingOptions() {
    if (!isset($this->jsEasingOptions)) {
      $this->jsEasingOptions = [
        'linear'           => 'Linear',
        'swing'            => 'Swing',
        'easeInQuad'       => 'easeInQuad',
        'easeOutQuad'      => 'easeOutQuad',
        'easeInOutQuad'    => 'easeInOutQuad',
        'easeInCubic'      => 'easeInCubic',
        'easeOutCubic'     => 'easeOutCubic',
        'easeInOutCubic'   => 'easeInOutCubic',
        'easeInQuart'      => 'easeInQuart',
        'easeOutQuart'     => 'easeOutQuart',
        'easeInOutQuart'   => 'easeInOutQuart',
        'easeInQuint'      => 'easeInQuint',
        'easeOutQuint'     => 'easeOutQuint',
        'easeInOutQuint'   => 'easeInOutQuint',
        'easeInSine'       => 'easeInSine',
        'easeOutSine'      => 'easeOutSine',
        'easeInOutSine'    => 'easeInOutSine',
        'easeInExpo'       => 'easeInExpo',
        'easeOutExpo'      => 'easeOutExpo',
        'easeInOutExpo'    => 'easeInOutExpo',
        'easeInCirc'       => 'easeInCirc',
        'easeOutCirc'      => 'easeOutCirc',
        'easeInOutCirc'    => 'easeInOutCirc',
        'easeInElastic'    => 'easeInElastic',
        'easeOutElastic'   => 'easeOutElastic',
        'easeInOutElastic' => 'easeInOutElastic',
        'easeInBack'       => 'easeInBack',
        'easeOutBack'      => 'easeOutBack',
        'easeInOutBack'    => 'easeInOutBack',
        'easeInBounce'     => 'easeInBounce',
        'easeOutBounce'    => 'easeOutBounce',
        'easeInOutBounce'  => 'easeInOutBounce',
      ];
    }
    return $this->jsEasingOptions;
  }

  /**
   * List of available CSS easing methods.
   *
   * @param bool $map
   *   Flag to output the array as is for further processing if TRUE.
   *
   * @return array
   *   An array of CSS easings for select options, or all for the mappings.
   *
   * @see https://github.com/kenwheeler/slick/issues/118
   * @see http://matthewlein.com/ceaser/
   * @see http://www.w3.org/TR/css3-transitions/
   */
  public function getCssEasingOptions($map = FALSE) {
    $css_easings = [];
    $available_easings = [

      // Defaults/ Native.
      'ease'           => 'ease|ease',
      'linear'         => 'linear|linear',
      'ease-in'        => 'ease-in|ease-in',
      'ease-out'       => 'ease-out|ease-out',
      'swing'          => 'swing|ease-in-out',

      // Penner Equations (approximated).
      'easeInQuad'     => 'easeInQuad|cubic-bezier(0.550, 0.085, 0.680, 0.530)',
      'easeInCubic'    => 'easeInCubic|cubic-bezier(0.550, 0.055, 0.675, 0.190)',
      'easeInQuart'    => 'easeInQuart|cubic-bezier(0.895, 0.030, 0.685, 0.220)',
      'easeInQuint'    => 'easeInQuint|cubic-bezier(0.755, 0.050, 0.855, 0.060)',
      'easeInSine'     => 'easeInSine|cubic-bezier(0.470, 0.000, 0.745, 0.715)',
      'easeInExpo'     => 'easeInExpo|cubic-bezier(0.950, 0.050, 0.795, 0.035)',
      'easeInCirc'     => 'easeInCirc|cubic-bezier(0.600, 0.040, 0.980, 0.335)',
      'easeInBack'     => 'easeInBack|cubic-bezier(0.600, -0.280, 0.735, 0.045)',
      'easeOutQuad'    => 'easeOutQuad|cubic-bezier(0.250, 0.460, 0.450, 0.940)',
      'easeOutCubic'   => 'easeOutCubic|cubic-bezier(0.215, 0.610, 0.355, 1.000)',
      'easeOutQuart'   => 'easeOutQuart|cubic-bezier(0.165, 0.840, 0.440, 1.000)',
      'easeOutQuint'   => 'easeOutQuint|cubic-bezier(0.230, 1.000, 0.320, 1.000)',
      'easeOutSine'    => 'easeOutSine|cubic-bezier(0.390, 0.575, 0.565, 1.000)',
      'easeOutExpo'    => 'easeOutExpo|cubic-bezier(0.190, 1.000, 0.220, 1.000)',
      'easeOutCirc'    => 'easeOutCirc|cubic-bezier(0.075, 0.820, 0.165, 1.000)',
      'easeOutBack'    => 'easeOutBack|cubic-bezier(0.175, 0.885, 0.320, 1.275)',
      'easeInOutQuad'  => 'easeInOutQuad|cubic-bezier(0.455, 0.030, 0.515, 0.955)',
      'easeInOutCubic' => 'easeInOutCubic|cubic-bezier(0.645, 0.045, 0.355, 1.000)',
      'easeInOutQuart' => 'easeInOutQuart|cubic-bezier(0.770, 0.000, 0.175, 1.000)',
      'easeInOutQuint' => 'easeInOutQuint|cubic-bezier(0.860, 0.000, 0.070, 1.000)',
      'easeInOutSine'  => 'easeInOutSine|cubic-bezier(0.445, 0.050, 0.550, 0.950)',
      'easeInOutExpo'  => 'easeInOutExpo|cubic-bezier(1.000, 0.000, 0.000, 1.000)',
      'easeInOutCirc'  => 'easeInOutCirc|cubic-bezier(0.785, 0.135, 0.150, 0.860)',
      'easeInOutBack'  => 'easeInOutBack|cubic-bezier(0.680, -0.550, 0.265, 1.550)',
    ];

    foreach ($available_easings as $key => $easing) {
      list($readable_easing, $css_easing) = array_pad(array_map('trim', explode("|", $easing, 2)), 2, NULL);
      $css_easings[$key] = $map ? $easing : $readable_easing;
      unset($css_easing);
    }
    return $css_easings;
  }

  /**
   * Defines options required by theme_slick(), used with optimized option.
   */
  public function getOptionsRequiredByTemplate() {
    $options = [
      'lazyLoad'     => 'ondemand',
      'slidesToShow' => 1,
    ];

    $this->manager->getModuleHandler()->alter('slick_options_required_by_template', $options);
    return $options;
  }

  /**
   * Maps existing jQuery easing value to equivalent CSS easing methods.
   *
   * @param string $easing
   *   The name of the human readable easing.
   *
   * @return string
   *   A string of unfriendly bezier equivalent, or NULL.
   */
  public function getBezier($easing = NULL) {
    $css_easing = '';
    if ($easing) {
      $easings = $this->getCssEasingOptions(TRUE);
      list($readable_easing, $bezier) = array_pad(array_map('trim', explode("|", $easings[$easing], 2)), 2, NULL);
      $css_easing = $bezier;
      unset($readable_easing);
    }
    return $css_easing;
  }

}
