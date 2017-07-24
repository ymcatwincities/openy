<?php

namespace Drupal\custom_formatters\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\custom_formatters\FormatterInterface;
use Drupal\custom_formatters\FormatterTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomFormattersController.
 *
 * @package Drupal\custom_formatters\Controller
 */
class CustomFormattersController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The Formatter type plugin manager.
   *
   * @var FormatterTypeManager
   */
  protected $formatterTypeManager = NULL;

  /**
   * Constructs a CustomFormattersController object.
   */
  public function __construct(FormatterTypeManager $formatter_type_manager) {
    $this->formatterTypeManager = $formatter_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.custom_formatters.formatter_type')
    );
  }

  /**
   * Provides the formatter creation form.
   *
   * @return array
   *   A node submission form.
   */
  public function add($formatter_type) {
    $formatter = $this->entityTypeManager()->getStorage('formatter')->create([
      'type' => $formatter_type,
    ]);

    $form = $this->entityFormBuilder()->getForm($formatter);

    return $form;
  }

  /**
   * Displays add content links for available formatter types.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the formatter types that can be added;
   *   however, if there is only one formatter type available for the user, the
   *   function will return a RedirectResponse to the formatter add page for
   *   that one formatter type.
   */
  public function addList() {
    $build = [
      '#theme' => 'formatter_add_list',
    ];

    $content = [];

    // Only use formatter types the user has access to.
    // @TODO - Add granular permissions system.
    foreach ($this->formatterTypeManager->getDefinitions() as $formatter_type) {
      $content[$formatter_type['id']] = $formatter_type;
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Page title callback for a formatter edit form.
   *
   * @return string
   *   The formatter edit page title.
   */
  public function editTitle(FormatterInterface $formatter) {
    return $this->t('<em>Edit formatter</em> :title', [':title' => $formatter->label()]);
  }

}
