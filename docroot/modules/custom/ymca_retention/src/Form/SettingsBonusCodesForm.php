<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for managing module settings.
 */
class SettingsBonusCodesForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct SettingsBonusCodesForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_bonus_codes_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_retention.bonus_codes_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.bonus_codes_settings');
    $general_config = $this->config('ymca_retention.general_settings');

    $date = new \DateTime($general_config->get('date_campaign_open'));
    $date->setTime(0, 0);

    $date_end = new \DateTime($general_config->get('date_campaign_close'));
    $date_end->setTime(0, 0);

    $bonus_codes = $config->get('bonus_codes');
    $form['bonus_codes'] = ['#type' => 'container', '#tree' => TRUE];

    while ($date <= $date_end) {
      $title = $date->format('m/d/Y');

      if (!isset($bonus_codes[$title])) {
        $bonus_codes[$title] = ['code' => '', 'reference' => ''];
      }

      $form['bonus_codes'][$title] = [
        '#type' => 'details',
        '#title' => $title,
        '#tree' => TRUE,
      ];
      $form['bonus_codes'][$title]['code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bonus code'),
        '#default_value' => $bonus_codes[$title]['code'],
      ];

      $nid = $bonus_codes[$title]['reference'];
      $form['bonus_codes'][$title]['reference'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Blog post'),
        '#target_type' => 'node',
        '#default_value' => empty($nid) ? '' : $this->entityTypeManager->getStorage('node')->load($nid),
        '#selection_settings' => [
          'target_bundles' => ['blog'],
        ],
      ];

      $date->modify('+1 day');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $bonus_codes = $form_state->getValue('bonus_codes');
    foreach ($bonus_codes as $date => $data) {
      if (empty($data['code']) && empty($data['reference'])) {
        continue;
      }

      foreach (['code', 'reference'] as $key) {
        if (!empty($data[$key])) {
          continue;
        }
        $form_state->setErrorByName('bonus_codes][' . $date . '][' . $key, $this->t('%field is required.', [
          '%field' => $form['bonus_codes'][$date][$key]['#title'],
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ymca_retention.bonus_codes_settings')
      ->set('bonus_codes', $form_state->getValue('bonus_codes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
