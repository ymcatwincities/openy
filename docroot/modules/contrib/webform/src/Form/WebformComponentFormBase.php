<?php
/**
 * @file
 * Contains \Drupal\webform\Form\WebformComponentFormBase.
 */


namespace Drupal\webform\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\ComponentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class WebformComponentFormBase extends FormBase {
  /**
   * Prepares the component used by this form.
   *
   * @param string $component
   *   Either a component ID, or the plugin ID used to create a new
   *   component.
   *
   * @return \Drupal\webform\ComponentInterface
   *   The condition object.
   */
  abstract protected function prepareComponent($node, $component);

  /**
   * The component manager service.
   *
   * @var \Drupal\webform\ComponentManager
   */
  protected $componentManager;

  /**
   * Constructs a WebformComponentsForm object.
   *
   * @param ComponentManager $component_manager
   *   The component manager service.
   */
  public function __construct(ComponentManager $component_manager) {
    $this->componentManager = $component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.component')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Change this because $component could be component type or component id
   *       Would make sense to make our own ParamConverterInterface's so component is loaded at this point?
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL, $component = NULL) {
    // Load the component plugin.
     $form_state->getFormObject();
    if (empty($form['#component'])) {
      $component = $this->prepareComponent($node, $component);
    }
    else {
      $component = $form['#component'];
    }
    // Get the form from the component plugin.
    $form = $component->buildForm($form, $form_state, $node);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $form['#node'];

    /** @var \Drupal\webform\ComponentBase $component */
    $component = $form['#component'];

    $values = $form_state->cleanValues()->getValues();

    $component->setConfiguration($values);

    $component->save();
    drupal_set_message($this->t('Changes to the webform have been saved.'));
    $config = $component->getConfiguration();
    // @todo Is value actually being used in the form after save()?
    $form['cid'] = array(
      '#type' => 'value',
      '#value' => $config['cid'],
    );

    $form_state->setRedirect( 'webform.components', array( 'node' => $node->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    //parent::submitForm($form, $form_state);
  }



}
