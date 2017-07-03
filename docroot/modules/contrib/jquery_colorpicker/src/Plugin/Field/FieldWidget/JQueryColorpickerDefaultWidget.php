<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jquery_colorpicker\Service\JQueryColorpickerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The default jquery_colorpicker field widget
 *
 * @FieldWidget(
 *   id = "jquery_colorpicker",
 *   label = @Translation("jQuery Colorpicker"),
 *   field_types = {
 *      "jquery_colorpicker"
 *   }
 * )
 */
class JQueryColorpickerDefaultWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface
{
	/**
	 * The JQuery Colorpicker service
	 *
	 * @var \Drupal\jquery_colorpicker\Service\JQueryColorpickerServiceInterface
	 */
	protected $JQueryColorpickerService;

	/**
	 * @param string $plugin_id
	 * @param mixed $plugin_definition
	 * @param Drupal\Core\Field\FieldDefinitionInterface $field_definition
	 * @param array $settings
	 * @param array $third_party_settings
	 * @param Drupal\jquery_colorpicker\Service\JQueryColorpickerServiceInterface $JQueryColorpickerService
	 */
	public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, JQueryColorpickerServiceInterface $JQueryColorpickerService)
	{
		parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

		$this->JQueryColorpickerService = $JQueryColorpickerService;
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @param array $configuration
	 * @param string $plugin_id
	 * @param mixed $plugin_definition
	 * @return static
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
	{
		return new static(
			$plugin_id,
			$plugin_definition,
			$configuration['field_definition'],
			$configuration['settings'],
			$configuration['third_party_settings'],
			$container->get('jquery_colorpicker.service')
		);
	}

	public static function defaultSettings()
	{
		return [
			'color' => 'FFFFFF',
		] + parent::defaultSettings();
	}

	public function settingsForm(array $form, FormStateInterface $form_state)
	{
		$element['color'] = [
			'#type' => 'textfield',
			'#field_prefix' => '#',
			'#title' => t('Color'),
			'#default_value' => $this->getSetting('color'),
			'#required' => TRUE,
			'#element_validate' => [
				[$this, 'settingsFormValidate'],
			],
		];

		return $element;
	}

	public function settingsFormValidate($element, FormStateInterface $form_state)
	{
		$color = $form_state->getValue($element['#parents']);

		$results = $this->JQueryColorpickerService->validateColor($color);

		$form_state->setValueForElement($element, $results['color']);
		if(isset($results['error']))
		{
			$form_state->setError($element, $results['error']);
		}
	}

	public function settingsSummary()
	{
		$summary = array();

		$summary[] = t('Default Color: @color', array('@color' => '#' . $this->getSetting('color')));

		return $summary;
	}

	/**
	 * Build the form element shown when creating the entity
	 */
	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
	{
 		$cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
		$element['value'] = $element + [
			'#type' => 'jquery_colorpicker',
			'#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : 'FFFFFF',
			'#description' => $element['#description'],
			'#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
		];

		return $element;
	}
}
