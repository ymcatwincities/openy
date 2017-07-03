<?php

namespace Drupal\slick_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\slick\Entity\Slick;

/**
 * Extends base form for slick instance configuration form.
 */
class SlickForm extends SlickFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form      = parent::form($form, $form_state);
    $slick     = $this->entity;
    $options   = $slick->getOptions() ?: [];
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');
    $tooltip   = ['class' => ['is-tooltip']];

    // Options.
    $form['options'] = [
      '#type'    => 'vertical_tabs',
      '#tree'    => TRUE,
      '#parents' => ['options'],
    ];

    // Main JS options.
    $form['settings'] = [
      '#type'       => 'details',
      '#tree'       => TRUE,
      '#title'      => $this->t('Settings'),
      '#attributes' => ['class' => ['details--settings', 'has-tooltip']],
      '#group'      => 'options',
      '#parents'    => ['options', 'settings'],
    ];

    foreach ($this->getFormElements() as $name => $element) {
      $element['default'] = isset($element['default']) ? $element['default'] : '';
      $form['settings'][$name] = [
        '#title'         => isset($element['title']) ? $element['title'] : '',
        '#default_value' => (NULL !== $slick->getSetting($name)) ? $slick->getSetting($name) : $element['default'],
      ];

      if (isset($element['type'])) {
        $form['settings'][$name]['#type'] = $element['type'];
        if ($element['type'] != 'hidden') {
          $form['settings'][$name]['#attributes'] = $tooltip;
        }

        if ($element['type'] == 'textfield') {
          $form['settings'][$name]['#size'] = 20;
          $form['settings'][$name]['#maxlength'] = 255;
        }
      }

      if (isset($element['options'])) {
        $form['settings'][$name]['#options'] = $element['options'];
      }

      if (isset($element['empty_option'])) {
        $form['settings'][$name]['#empty_option'] = $element['empty_option'];
      }

      if (isset($element['description'])) {
        $form['settings'][$name]['#description'] = $element['description'];
      }

      if (isset($element['states'])) {
        $form['settings'][$name]['#states'] = $element['states'];
      }

      // Expand textfield for easy edit.
      if (in_array($name, ['prevArrow', 'nextArrow'])) {
        $form['settings'][$name]['#attributes']['class'][] = 'js-expandable';
      }

      if (isset($element['field_suffix'])) {
        $form['settings'][$name]['#field_suffix'] = $element['field_suffix'];
      }

      if (is_int($element['default'])) {
        $form['settings'][$name]['#maxlength'] = 60;
        $form['settings'][$name]['#attributes']['class'][] = 'form-text--int';
      }

      if ($admin_css && !isset($element['field_suffix']) && is_bool($element['default'])) {
        $form['settings'][$name]['#field_suffix'] = '&nbsp;';
        $form['settings'][$name]['#title_display'] = 'before';
      }
    }

    // Responsive JS options.
    // https://github.com/kenwheeler/slick/issues/951
    $form['responsives'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Responsive display'),
      '#open'        => TRUE,
      '#tree'        => TRUE,
      '#group'       => 'options',
      '#parents'     => ['options', 'responsives'],
      '#description' => $this->t('Containing breakpoints and settings objects. Settings set at a given breakpoint/screen width is self-contained and does not inherit the main settings, but defaults. Be sure to set Breakpoints option above.'),
    ];

    $form['responsives']['responsive'] = [
      '#type'       => 'details',
      '#title'      => $this->t('Responsive'),
      '#open'       => TRUE,
      '#group'      => 'responsives',
      '#parents'    => ['options', 'responsives', 'responsive'],
      '#prefix'     => '<div id="edit-breakpoints-ajax-wrapper">',
      '#suffix'     => '</div>',
      '#attributes' => ['class' => ['has-tooltip', 'details--responsive--ajax']],
    ];

    // Add some information to the form state for easier form altering.
    $form_state->set('breakpoints_count', 0);
    $breakpoints_count = $form_state->hasValue('breakpoints') ? $form_state->getValue('breakpoints') : $slick->getBreakpoints();

    if (!$form_state->hasValue('breakpoints_count')) {
      $form_state->setValue('breakpoints_count', $breakpoints_count);
    }

    $user_input = $form_state->getUserInput();
    $breakpoints_input = isset($user_input['breakpoints']) ? (int) $user_input['breakpoints'] : $breakpoints_count;

    if ($breakpoints_input && ($breakpoints_input != $breakpoints_count)) {
      $form_state->setValue('breakpoints_count', $breakpoints_input);
    }

    if ($form_state->getValue('breakpoints_count') > 0) {
      $slick_responsive_options = $this->getResponsiveFormElements($form_state->getValue('breakpoints_count'));

      foreach ($slick_responsive_options as $i => $responsives) {
        // Individual breakpoint details depends on the breakpoints amount.
        $form['responsives']['responsive'][$i] = [
          '#type'       => $responsives['type'],
          '#title'      => $responsives['title'],
          '#open'       => FALSE,
          '#group'      => 'responsive',
          '#attributes' => [
            'class' => [
              'details--responsive',
              'details--breakpoint-' . $i,
              'has-tooltip',
            ],
          ],
        ];

        unset($responsives['title'], $responsives['type']);
        foreach ($responsives as $key => $responsive) {
          switch ($key) {
            case 'breakpoint':
            case 'unslick':
              $form['responsives']['responsive'][$i][$key] = [
                '#type'          => $responsive['type'],
                '#title'         => $responsive['title'],
                '#default_value' => isset($options['responsives']['responsive'][$i][$key]) ? $options['responsives']['responsive'][$i][$key] : $responsive['default'],
                '#description'   => $responsive['description'],
                '#attributes'    => $tooltip,
              ];

              if ($responsive['type'] == 'textfield') {
                $form['responsives']['responsive'][$i][$key]['#size'] = 20;
                $form['responsives']['responsive'][$i][$key]['#maxlength'] = 255;
              }

              if (is_int($responsive['default'])) {
                $form['responsives']['responsive'][$i][$key]['#maxlength'] = 60;
              }

              if (isset($responsive['field_suffix'])) {
                $form['responsives']['responsive'][$i][$key]['#field_suffix'] = $responsive['field_suffix'];
              }

              if ($admin_css && !isset($responsive['field_suffix']) && $responsive['type'] == 'checkbox') {
                $form['responsives']['responsive'][$i][$key]['#field_suffix'] = '&nbsp;';
                $form['responsives']['responsive'][$i][$key]['#title_display'] = 'before';
              }
              break;

            case 'settings':
              $form['responsives']['responsive'][$i][$key] = [
                '#type'       => $responsive['type'],
                '#title'      => $responsive['title'],
                '#open'       => TRUE,
                '#group'      => $i,
                '#states'     => ['visible' => [':input[name*="[responsive][' . $i . '][unslick]"]' => ['checked' => FALSE]]],
                '#attributes' => [
                  'class' => [
                    'details--settings',
                    'details--breakpoint-' . $i,
                    'has-tooltip',
                  ],
                ],
              ];

              unset($responsive['title'], $responsive['type']);

              // @fixme, boolean default is ignored at index 0 only.
              foreach ($responsive as $k => $item) {
                $item['default'] = isset($item['default']) ? $item['default'] : '';
                $form['responsives']['responsive'][$i][$key][$k] = [
                  '#title'         => isset($item['title']) ? $item['title'] : '',
                  '#default_value' => isset($options['responsives']['responsive'][$i][$key][$k]) ? $options['responsives']['responsive'][$i][$key][$k] : $item['default'],
                  '#description'   => isset($item['description']) ? $item['description'] : '',
                  '#attributes'    => $tooltip,
                ];

                if (isset($item['type'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#type'] = $item['type'];
                }

                // Specify proper states for the breakpoint form elements.
                if (isset($item['states'])) {
                  $states = '';
                  switch ($k) {
                    case 'pauseOnHover':
                    case 'pauseOnDotsHover':
                    case 'autoplaySpeed':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][autoplay]"]' => ['checked' => TRUE]]];
                      break;

                    case 'centerPadding':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][centerMode]"]' => ['checked' => TRUE]]];
                      break;

                    case 'touchThreshold':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][touchMove]"]' => ['checked' => TRUE]]];
                      break;

                    case 'swipeToSlide':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][swipe]"]' => ['checked' => TRUE]]];
                      break;

                    case 'verticalSwiping':
                      $states = ['visible' => [':input[name*="[' . $i . '][settings][vertical]"]' => ['checked' => TRUE]]];
                      break;
                  }

                  if ($states) {
                    $form['responsives']['responsive'][$i][$key][$k]['#states'] = $states;
                  }
                }

                if (isset($item['options'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#options'] = $item['options'];
                }

                if (isset($item['empty_option'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#empty_option'] = $item['empty_option'];
                }

                if (isset($item['field_suffix'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#field_suffix'] = $item['field_suffix'];
                }

                if ($admin_css && !isset($item['field_suffix']) && is_bool($item['default'])) {
                  $form['responsives']['responsive'][$i][$key][$k]['#field_suffix'] = '&nbsp;';
                  $form['responsives']['responsive'][$i][$key][$k]['#title_display'] = 'before';
                }
              }
              break;

            default:
              break;
          }
        }
      }
    }

    // Attach Slick admin library.
    if ($admin_css) {
      $form['#attached']['library'][] = 'slick_ui/slick.admin.vtabs';
    }
    return $form;
  }

  /**
   * Defines available options for the main and responsive settings.
   *
   * @return array
   *   All available Slick options.
   *
   * @see http://kenwheeler.github.io/slick
   */
  public function getFormElements() {
    $elements = &drupal_static(__METHOD__, NULL);

    if (!isset($elements)) {
      $elements = [];

      $elements['mobileFirst'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Mobile first'),
        'description' => $this->t('Responsive settings use mobile first calculation, or equivalent to min-width query.'),
      ];

      $elements['asNavFor'] = [
        'type'        => 'textfield',
        'title'       => $this->t('asNavFor target'),
        'description' => $this->t('Leave empty if using sub-modules to have it auto-matched. Set the slider to be the navigation of other slider (Class or ID Name). Use selector identifier ("." or "#") accordingly. See HTML structure section at README.txt for more info. Overriden by field formatter, or Views style.'),
      ];

      $elements['accessibility'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Accessibility'),
        'description' => $this->t('Enables tabbing and arrow key navigation'),
      ];

      $elements['adaptiveHeight'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Adaptive height'),
        'description' => $this->t('Enables adaptive height for SINGLE slide horizontal carousels. This is useless with variableWidth.'),
      ];

      $elements['autoplay'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Autoplay'),
        'description' => $this->t('Enables autoplay'),
      ];

      $elements['autoplaySpeed'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Autoplay speed'),
        'description' => $this->t('Autoplay speed in milliseconds'),
      ];

      $elements['pauseOnHover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on hover'),
        'description' => $this->t('Pause autoplay on hover'),
      ];

      $elements['pauseOnDotsHover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on dots hover'),
        'description' => $this->t('Pause autoplay when a dot is hovered.'),
      ];

      $elements['arrows'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Arrows'),
        'description' => $this->t('Show prev/next arrows'),
      ];

      $elements['prevArrow'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Previous arrow'),
        'description' => $this->t("Customize the previous arrow markups. Be sure to keep the expected class: slick-prev."),
      ];

      $elements['nextArrow'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Next arrow'),
        'description' => $this->t("Customize the next arrow markups. Be sure to keep the expected class: slick-next."),
      ];

      $elements['downArrow'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Use arrow down'),
        'description' => $this->t('Arrow down to scroll down into a certain page section. Be sure to provide its target selector.'),
      ];

      $elements['downArrowTarget'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Arrow down target'),
        'description' => $this->t('Valid CSS selector to scroll to, e.g.: #main, or #content.'),
      ];

      $elements['downArrowOffset'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Arrow down offset'),
        'description'  => $this->t('Offset when scrolled down from the top.'),
        'field_suffix' => 'px',
      ];

      $elements['centerMode'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Center mode'),
        'description' => $this->t('Enables centered view with partial prev/next slides. Use with odd numbered slidesToShow counts.'),
      ];

      $elements['centerPadding'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Center padding'),
        'description' => $this->t('Side padding when in center mode (px or %). Be aware, too large padding at small breakpoint will screw the slide calculation with slidesToShow.'),
      ];

      $elements['dots'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Dots'),
        'description' => $this->t('Show dot indicators.'),
      ];

      $elements['dotsClass'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Dot class'),
        'description' => $this->t('Class for slide indicator dots container. Do not prefix with a dot (.). If you change this, edit its CSS accordingly.'),
      ];

      $elements['appendDots'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Append dots'),
        'description' => $this->t('Change where the navigation dots are attached (Selector, htmlString). If you change this, be sure to provide its relevant markup. Try <strong>.slick__arrow</strong> to achieve this style: <br />&lt; o o o o o o o &gt;<br />Be sure to enable Arrows in such a case.'),
      ];

      $elements['draggable'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Draggable'),
        'description' => $this->t('Enable mouse dragging.'),
      ];

      $elements['fade'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Fade'),
        'description' => $this->t('Enable fade. Warning! This wants slidesToShow 1. Larger than 1, and Slick may be screwed up.'),
      ];

      $elements['focusOnSelect'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Focus on select'),
        'description' => $this->t('Enable focus on selected element (click).'),
      ];

      $elements['infinite'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Infinite'),
        'description' => $this->t('Infinite loop sliding.'),
      ];

      $elements['initialSlide'] = [
        'type'        => 'number',
        'title'       => $this->t('Initial slide'),
        'description' => $this->t('Slide to start on.'),
      ];

      $elements['lazyLoad'] = [
        'type'         => 'select',
        'title'        => $this->t('Lazy load'),
        'options'      => $this->getLazyloadOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t("Set lazy loading technique. Ondemand will load the image as soon as you slide to it. Progressive loads one image after the other when the page loads. Anticipated preloads images, and requires Slick 1.6.1+. To share images for Pinterest, leave empty, otherwise no way to read actual image src. It supports Blazy module to delay loading below-fold images until 100px before they are visible at viewport, and/or have a bonus lazyLoadAhead when the beforeChange event fired.", ['@url' => '//www.drupal.org/project/imageinfo_cache']),
      ];

      $elements['mouseWheel'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Enable mousewheel'),
        'description' => $this->t('Be sure to download the <a href="@mousewheel" target="_blank">mousewheel</a> library, and it is available at <em>/libraries/mousewheel/jquery.mousewheel.min.js</em>.', ['@mousewheel' => '//github.com/brandonaaron/jquery-mousewheel']),
      ];

      $elements['randomize'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Randomize'),
        'description' => $this->t('Randomize the slide display, useful to manipulate cached blocks.'),
      ];

      $responds = ['window', 'slider', 'min'];
      $elements['respondTo'] = [
        'type'        => 'select',
        'title'       => $this->t('Respond to'),
        'description' => $this->t("Width that responsive object responds to. Can be 'window', 'slider' or 'min' (the smaller of the two)."),
        'options'     => array_combine($responds, $responds),
      ];

      $elements['rtl'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('RTL'),
        'description' => $this->t("Change the slider's direction to become right-to-left."),
      ];

      $elements['rows'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Rows'),
        'description' => $this->t("Setting this to more than 1 initializes grid mode. Use slidesPerRow to set how many slides should be in each row."),
      ];

      $elements['slidesPerRow'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Slides per row'),
        'description' => $this->t("With grid mode intialized via the rows option, this sets how many slides are in each grid row."),
      ];

      $elements['slide'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Slide element'),
        'description' => $this->t("Element query to use as slide. Slick will use any direct children as slides, without having to specify which tag or selector to target."),
      ];

      $elements['slidesToShow'] = [
        'type'        => 'number',
        'title'       => $this->t('Slides to show'),
        'description' => $this->t('Number of slides to show at a time. If 1, it will behave like slideshow, more than 1 a carousel. Provide more if it is a thumbnail navigation with asNavFor. Only works with odd number slidesToShow counts when using centerMode (e.g.: 3, 5, 7, etc.). Not-compatible with variableWidth.'),
      ];

      $elements['slidesToScroll'] = [
        'type'        => 'number',
        'title'       => $this->t('Slides to scroll'),
        'description' => $this->t('Number of slides to scroll at a time, or steps at each scroll.'),
      ];

      $elements['speed'] = [
        'type'         => 'number',
        'title'        => $this->t('Speed'),
        'description'  => $this->t('Slide/Fade animation speed in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['swipe'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Swipe'),
        'description' => $this->t('Enable swiping.'),
      ];

      $elements['swipeToSlide'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Swipe to slide'),
        'description' => $this->t('Allow users to drag or swipe directly to a slide irrespective of slidesToScroll.'),
      ];

      $elements['edgeFriction'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Edge friction'),
        'description' => $this->t("Resistance when swiping edges of non-infinite carousels. If you don't want resistance, set it to 1."),
      ];

      $elements['touchMove'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Touch move'),
        'description' => $this->t('Enable slide motion with touch.'),
      ];

      $elements['touchThreshold'] = [
        'type'        => 'number',
        'title'       => $this->t('Touch threshold'),
        'description' => $this->t('Swipe distance threshold.'),
      ];

      $elements['useCSS'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Use CSS'),
        'description' => $this->t('Enable/disable CSS transitions.'),
      ];

      $elements['cssEase'] = [
        'type'        => 'textfield',
        'title'       => $this->t('CSS ease'),
        'description' => $this->t('CSS3 animation easing. <a href="@ceaser">Learn</a> <a href="@bezier">more</a>. Ignored if <strong>CSS ease override</strong> is provided.', ['@ceaser' => '//matthewlein.com/ceaser/', '@bezier' => '//cubic-bezier.com']),
      ];

      $elements['cssEaseBezier'] = [
        'type'        => 'hidden',
      ];

      $elements['cssEaseOverride'] = [
        'title'        => $this->t('CSS ease override'),
        'type'         => 'select',
        'options'      => $this->getCssEasingOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t('If provided, this will override the CSS ease with the pre-defined CSS easings based on <a href="@ceaser">CSS Easing Animation Tool</a>. Leave it empty to use your own CSS ease.', ['@ceaser' => 'http://matthewlein.com/ceaser/']),
      ];

      $elements['useTransform'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Use CSS Transforms'),
        'description' => $this->t('Enable/disable CSS transforms.'),
      ];

      $elements['easing'] = [
        'title'        => $this->t('jQuery easing'),
        'type'         => 'select',
        'options'      => $this->getJsEasingOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t('Add easing for jQuery animate as fallback. Use with <a href="@easing">easing</a> libraries or default easing methods. Optionally install <a href="@jqeasing">jqeasing module</a>. This will be ignored and replaced by CSS ease for supporting browsers, or effective if useCSS is disabled.', ['@jqeasing' => '//drupal.org/project/jqeasing', '@easing' => '//gsgd.co.uk/sandbox/jquery/easing/']),
      ];

      $elements['variableWidth'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Variable width'),
        'description' => $this->t('Disables automatic slide width calculation. Best with uniform image heights, use scale height image effect. Useless with adaptiveHeight, and non-uniform image heights. Useless with slidesToShow > 1 if the container is smaller than the amount of visible slides. Troubled with lazyLoad ondemand.'),
      ];

      $elements['vertical'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Vertical'),
        'description' => $this->t('Vertical slide direction. See <a href="@url" target="_blank">relevant issue</a>.', ['@url' => '//github.com/kenwheeler/slick/issues/1001']),
      ];

      $elements['verticalSwiping'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Vertical swiping'),
        'description' => $this->t('Changes swipe direction to vertical.'),
      ];

      $elements['waitForAnimate'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Wait for animate'),
        'description' => $this->t('Ignores requests to advance the slide while animating.'),
      ];

      // Defines the default values if available.
      $defaults = Slick::defaultSettings();
      foreach ($elements as $name => $element) {
        $default = $element['type'] == 'checkbox' ? FALSE : '';
        $elements[$name]['default'] = isset($defaults[$name]) ? $defaults[$name] : $default;
      }

      foreach (Slick::getDependentOptions() as $parent => $items) {
        foreach ($items as $name) {
          if (isset($elements[$name])) {
            $states = ['visible' => [':input[name*="options[settings][' . $parent . ']"]' => ['checked' => TRUE]]];
            if (!isset($elements[$name]['states'])) {
              $elements[$name]['states'] = $states;
            }
            else {
              $elements[$name]['states'] = array_merge($elements[$name]['states'], $states);
            }
          }
        }
      }
    }
    return $elements;
  }

  /**
   * Removes problematic options for the responsive Slick.
   *
   * The problematic options are those that should exist once for a given Slick
   *   instance, or no easy way to deal with in the responsive context.
   *   JS takes care of the relevant copy on each responsive setting instead.
   *
   * @return array
   *   An array of cleaned out options.
   */
  public function cleanFormElements() {
    $excludes = [
      'accessibility',
      'appendArrows',
      'appendDots',
      'asNavFor',
      'cssEase',
      'cssEaseBezier',
      'cssEaseOverride',
      'dotsClass',
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'easing',
      'lazyLoad',
      'mobileFirst',
      'mouseWheel',
      'nextArrow',
      'prevArrow',
      'randomize',
      'rtl',
      'slide',
      'useCSS',
      'useTransform',
    ];
    return array_diff_key($this->getFormElements(), array_combine($excludes, $excludes));
  }

  /**
   * Defines available options for the responsive Slick.
   *
   * @param int $count
   *   The number of breakpoints.
   *
   * @return array
   *   An array of Slick responsive options.
   */
  public function getResponsiveFormElements($count = 0) {
    $elements = [];
    $range = range(0, ($count - 1));
    $breakpoints = array_combine($range, $range);

    foreach ($breakpoints as $key => $breakpoint) {
      $elements[$key] = [
        'type'  => 'details',
        'title' => $this->t('Breakpoint #@key', ['@key' => ($key + 1)]),
      ];

      $elements[$key]['breakpoint'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Breakpoint'),
        'description'  => $this->t('Breakpoint width in pixel. If mobileFirst enabled, equivalent to min-width, otherwise max-width.'),
        'default'      => '',
        'field_suffix' => 'px',
      ];

      $elements[$key]['unslick'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Unslick'),
        'description' => $this->t("Disable Slick at a given breakpoint. Note, you can't window shrink this, once you unslick, you are unslicked."),
        'default'     => FALSE,
      ];

      $elements[$key]['settings'] = [
        'type'  => 'details',
        'title' => $this->t('Settings'),
      ];

      // Duplicate relevant main settings.
      foreach ($this->cleanFormElements() as $name => $responsive) {
        $elements[$key]['settings'][$name] = $responsive;
      }
    }
    return $elements;
  }

  /**
   * Returns modifiable lazyload options.
   */
  public function getLazyloadOptions() {
    $options = [
      'anticipated' => $this->t('Anticipated'),
      'blazy'       => $this->t('Blazy'),
      'ondemand'    => $this->t('On demand'),
      'progressive' => $this->t('Progressive'),
    ];

    $this->manager->getModuleHandler()->alter('slick_lazyload_options_info', $options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Update CSS Bezier version.
    $override = $form_state->getValue(['options', 'settings', 'cssEaseOverride']);
    if ($override) {
      $override = $this->getBezier($override);
    }

    // Update cssEaseBezier value based on cssEaseOverride.
    $form_state->setValue(['options', 'settings', 'cssEaseBezier'], $override);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
