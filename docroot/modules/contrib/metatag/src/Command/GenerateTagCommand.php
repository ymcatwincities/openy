<?php
/**
 * @file
 * Contains Drupal\metatag\Command\GenerateTagCommand.
 */

namespace Drupal\metatag\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\metatag\Generator\MetatagTagGenerator;

/**
 * Class GenerateTagCommand.
 *
 * Generate a Metatag tag plugin.
 *
 * @package Drupal\metatag
 */
class GenerateTagCommand extends GeneratorCommand {
  use ServicesTrait;
  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($translator) {
    parent::__construct($translator);

    $this->metatagManager = \Drupal::service('metatag.manager');
  }
  
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:metatag:tag')
      ->setDescription($this->trans('commands.generate.metatag.tag.description'))
      ->setHelp($this->trans('commands.generate.metatag.tag.help'))
      ->addOption('module', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module'))
      ->addOption('name', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.name'))
      ->addOption('label', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.label'))
      ->addOption('description', '', InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.metatag.tag.options.description'))
      ->addOption('plugin-id', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.plugin_id'))
      ->addOption('class-name', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.class_name'))
      ->addOption('group', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.group'))
      ->addOption('weight', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.weight'))
      ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();

    // @see use Drupal\AppConsole\Command\Helper\ConfirmationTrait::confirmationQuestion
    if ($this->confirmationQuestion($input, $output, $dialog)) {
      return;
    }

    $module = $input->getOption('module');
    $name = $input->getOption('name');
    $label = $input->getOption('label');
    $description = $input->getOption('description');
    $plugin_id = $input->getOption('plugin-id');
    $class_name = $input->getOption('class-name');
    $group = $input->getOption('group');
    $weight = $input->getOption('weight');

    $this
      ->getGenerator()
      ->generate($module, $name, $label, $description, $plugin_id, $class_name, $group, $weight);

    $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'discovery']);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();

    // --module option.
    $module = $input->getOption('module');
    if (empty($module)) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --name option.
    $name = $input->getOption('name');
    if (empty($name)) {
      $name = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.name'), ''),
        function($class_name) {
          // @todo Validation?
          return $class_name;
        },
        FALSE,
        '',
        NULL
      );
    }
    $input->setOption('name', $name);

    // --label option.
    $label = $input->getOption('label');
    if (empty($label)) {
      $label = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.label'), ''),
        ''
      );
    }
    $input->setOption('label', $label);

    // --description option.
    $description = $input->getOption('description');
    if (empty($label)) {
      $description = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.description'), ''),
        ''
      );
    }
    $input->setOption('description', $description);

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (empty($plugin_id)) {
      $plugin_id = $this->nameToPluginId($name);
      $plugin_id = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.plugin_id'), $plugin_id),
        $plugin_id
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --class-name option.
    $class_name = $input->getOption('class-name');
    if (empty($class_name)) {
      $class_name = $this->nameToClassName($name);
      $class_name = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.class_name'), $class_name),
        $class_name
      );
    }
    $input->setOption('class-name', $class_name);

    // --group option.
    $group = $input->getOption('group');
    if (empty($group)) {
      $group = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.group'), ''),
        function($group) {
          return $this->validateGroupExist($group);
        },
        FALSE,
        '',
        $this->getGroups()
      );
    }
    $input->setOption('group', $group);

    // --weight option.
    $weight = $input->getOption('weight');
    if (is_null($weight)) {
      $weight = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.metatag.tag.questions.weight'), '0'),
        function($weight) {
          return is_int($weight);
        },
        FALSE,
        '0',
        NULL
      );
    }
    $input->setOption('weight', $weight);
  }

  /**
   * {@inheritdoc}
   */
  protected function createGenerator() {
    return new MetatagTagGenerator();
  }

  /**
   * Convert the meta tag's name to a plugin ID.
   *
   * @param string $name
   *   The meta tag name to convert.
   *
   * @return string
   *   The original string with all non-alphanumeric characters converted to
   *   underline chars.
   */
  private function nameToPluginId($name) {
    $string_utils = $this->getStringUtils();

    return $string_utils->createMachineName($name);
  }

  /**
   * Convert the meta tag's name to a class name.
   *
   * @param string $name
   *   The meta tag name to convert.
   *
   * @return string
   *   The original string with all non-alphanumeric characters removed and
   *   converted to CamelCase.
   */
  private function nameToClassName($name) {
    $string_utils = $this->getStringUtils();

    // Convert some characters to spaces so that each portion of the string can
    // then be considered separate words and collapsed together nicely by the
    // humanToCamelCase() method.
    $name = preg_replace($string_utils::REGEX_MACHINE_NAME_CHARS, ' ', $name);
    return $string_utils->humanToCamelCase($name);
  }

  /**
   * All of the meta tag groups.
   *
   * @return array
   *   A list of the available groups.
   */
  private function getGroups() {
    return array_keys($this->metatagManager->groupDefinitions());
  }

  /**
   * Confirm that a requested group exists.
   *
   * @param string $group
   *   A group's machine name.
   *
   * @return string
   *   The group's name, if available, otherwise an empty string.
   */
  private function validateGroupExist($group) {
    $groups = $this->getGroups();
    if (isset($groups[$group])) {
      return $group;
    }
    return '';
  }

}
