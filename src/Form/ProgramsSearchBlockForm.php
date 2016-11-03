<?php

namespace Drupal\ygh_programs_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\daxko\DaxkoClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "programs_search_block_form" form.
 */
class ProgramsSearchBlockForm extends FormBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Daxko client.
   *
   * @var \Drupal\daxko\DaxkoClientInterface
   */
  protected $client;

  /**
   * ProgramsSearchBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\daxko\DaxkoClient $client
   *   The Daxko client.
   */
  public function __construct(RendererInterface $renderer, DaxkoClient $client) {
    $this->renderer = $renderer;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('daxko.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'programs_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['locations'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getLocations(),
      '#title' => $this->t('Location'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submit.
  }

  /**
   * Get locations.
   *
   * @return array
   *   Locations.
   */
  private function getLocations() {
    $locations = [];
    $branches = $this->client->getData('branches?limit=100');
    foreach ($branches as $branch) {
      $locations[$branch->id] = $branch->name;
    }

    return $locations;
  }

}
