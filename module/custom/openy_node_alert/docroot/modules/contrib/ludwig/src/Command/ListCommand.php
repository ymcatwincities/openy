<?php

namespace Drupal\ludwig\Command;

use Drupal\ludwig\PackageManagerInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Class ListCommand.
 *
 * @package Drupal\ludwig
 *
 * @DrupalCommand (
 *   extension="ludwig",
 *   extensionType="module"
 * )
 */
class ListCommand extends Command {

  use CommandTrait;

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * Constructs a new ListCommand object.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The package manager.
   */
  public function __construct(PackageManagerInterface $package_manager) {
    parent::__construct();

    $this->packageManager = $package_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('ludwig:list')
      ->setDescription($this->trans('commands.ludwig.list.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $has_missing_packages = FALSE;
    $rows = [];
    foreach ($this->packageManager->getPackages() as $package) {
      $row = [
        $package['name'],
        $package['version'],
        $package['provider'],
      ];
      if ($package['installed']) {
        $row[] = $this->trans('commands.ludwig.list.messages.installed');
      }
      else {
        $row[] = $this->trans('commands.ludwig.list.messages.missing');
        $has_missing_packages = TRUE;
      }
      $rows[] = $row;
    }

    $io = new DrupalStyle($input, $output);
    $io->table([
      $this->trans('commands.ludwig.list.table-headers.package'),
      $this->trans('commands.ludwig.list.table-headers.version'),
      $this->trans('commands.ludwig.list.table-headers.required-by'),
      $this->trans('commands.ludwig.list.table-headers.status'),
    ], $rows, 'default');
    if ($has_missing_packages) {
      $download = $io->confirm($this->trans('commands.ludwig.list.questions.download'));
      if ($download) {
        $this->getApplication()->find('ludwig:download')->run($input, $output);
      }
    }
  }

}
