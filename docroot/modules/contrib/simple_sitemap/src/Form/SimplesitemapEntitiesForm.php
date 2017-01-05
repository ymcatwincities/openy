<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimplesitemapEntitiesForm.
 *
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapEntitiesForm extends SimplesitemapFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_sitemap_entities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_entities']['#prefix'] = $this->getDonationText();

    $form['simple_sitemap_entities']['entities'] = [
      '#title' => $this->t('Sitemap entities'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . $this->t("Simple XML sitemap settings will be added only to entity forms of entity types enabled here. For all entity types featuring bundles (e.g. <em>node</em>) sitemap settings have to be set on their bundle pages (e.g. <em>page</em>).") . '</p>',
    ];

    $form['#attached']['library'][] = 'simple_sitemap/sitemapEntities';
    $form['#attached']['drupalSettings']['simple_sitemap'] = ['all_entities' => [], 'atomic_entities' => []];

    $entity_type_labels = [];
    foreach ($this->generator->getSitemapEntityTypes() as $entity_type_id => $entity_type) {
      $entity_type_labels[$entity_type_id] = $entity_type->getLabel() ? : $entity_type_id;
    }
    asort($entity_type_labels);

    $this->formHelper->processForm($form_state);

    foreach ($entity_type_labels as $entity_type_id => $entity_type_label) {

      $css_entity_type_id = str_replace('_', '-', $entity_type_id);

      $form['simple_sitemap_entities']['entities'][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type_label,
        '#open' => $this->generator->entityTypeIsEnabled($entity_type_id),
      ];

      $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @entity_type_label <em>(@entity_type_id)</em> support', ['@entity_type_label' => strtolower($entity_type_label), '@entity_type_id' => $entity_type_id]),
        '#description' => $this->t('Sitemap settings for this entity type can be set on its bundle pages and overridden on its entity pages.'),
        '#default_value' => $this->generator->entityTypeIsEnabled($entity_type_id),
      ];

      if ($form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled']['#default_value']) {
        $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled']['#suffix']
          = "<div id='warning-$css_entity_type_id'>"
          . $this->t("<strong>Warning:</strong> This entity type's sitemap settings including per-entity overrides will be deleted after hitting <em>Save</em>.")
          . "</div>";
      }

      $form['#attached']['drupalSettings']['simple_sitemap']['all_entities'][] = $css_entity_type_id;

      if ($this->generator->entityTypeIsAtomic($entity_type_id)) {
        $form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_enabled']['#description'] = $this->t('Sitemap settings for this entity type can be set below and overridden on its entity pages.');
        $this->formHelper->setEntityCategory('bundle')
          ->setEntityTypeId($entity_type_id)
          ->setBundleName($entity_type_id)
          ->displayEntitySettings($form['simple_sitemap_entities']['entities'][$entity_type_id][$entity_type_id . '_settings'], TRUE);
        $form['#attached']['drupalSettings']['simple_sitemap']['atomic_entities'][] = $css_entity_type_id;
      }
    }

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_entities']['entities']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $field_name => $value) {
      if (substr($field_name, -strlen('_enabled')) == '_enabled') {
        $entity_type_id = substr($field_name, 0, -8);
        if ($value) {
          $this->generator->enableEntityType($entity_type_id);
          if ($this->generator->entityTypeIsAtomic($entity_type_id)) {
            $this->generator->setBundleSettings($entity_type_id, $entity_type_id, [
              'index' => TRUE,
              'priority' => $values[$entity_type_id . '_simple_sitemap_priority'],
            ]);
          }
        }
        else {
          $this->generator->disableEntityType($entity_type_id);
        }
      }
    }
    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->generateSitemap();
    }
  }

}
