<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'geolocation_html5' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_html5",
 *   label = @Translation("Geolocation HTML5"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationHTML5Widget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    foreach ($violations as $violation) {
      if ($violation->getMessageTemplate() == 'This value should not be null.') {
        $form_state->setErrorByName($items->getName(), $this->t('No location could be determined for required field %field.', ['%field' => $items->getFieldDefinition()->getLabel()]));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $lat = $items[$delta]->lat;
    $lng = $items[$delta]->lng;

    // Get the default values for existing field.
    $lat_default_value = isset($lat) ? $lat : NULL;
    $lng_default_value = isset($lng) ? $lng : NULL;

    // The 'Get my location' button.
    $button_html = '<div class="geolocation-html5-button">';
    $button_html .= '<span class="default">' . $this->t('Get browser location') . '</span>';
    $button_html .= '<span class="location"></span>';
    $button_html .= '<div class="search"></div>';
    $button_html .= '<div class="clear"></div>';
    $button_html .= '</div>';

    $element['get_location'] = [
      '#markup' => $button_html,
    ];

    // Hidden lat,lng input fields.
    $element['lat'] = [
      '#type' => 'hidden',
      '#default_value' => $lat_default_value,
      '#attributes' => ['class' => ['geolocation-hidden-lat']],
    ];
    $element['lng'] = [
      '#type' => 'hidden',
      '#default_value' => $lng_default_value,
      '#attributes' => ['class' => ['geolocation-hidden-lng']],
    ];

    // Attach the html5 library.
    $element['#attached'] = [
      'library' => [
        'geolocation/geolocation.widgets.html5',
      ],
    ];

    // Wrap the whole form in a container.
    $element += [
      '#type' => 'item',
      '#title' => $element['#title'],
    ];

    return $element;
  }

}
