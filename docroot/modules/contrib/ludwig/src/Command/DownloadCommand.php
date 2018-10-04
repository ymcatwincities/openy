<?php

namespace Drupal\ludwig\Command;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\ludwig\PackageDownloaderInterface;
use Drupal\ludwig\PackageManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DownloadCommand.
 *
 * @package Drupal\ludwig
 *
 * @DrupalCommand(
 *   extension="ludwig",
 *   extensionType="module"
 * )
 */
class DownloadCommand extends Command {

  use CommandTrait;

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The package downloader.
   *
   * @var \Drupal\ludwig\PackageDownloaderInterface
   */
  protected $packageDownloader;

  /**
   * The chain queue.
   *
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * Constructs a new ListCommand object.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The package manager.
   * @param \Drupal\ludwig\PackageDownloaderInterface $package_downloader
   *   The package downloader.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chain_queue
   *   The chain queue.
   */
  public function __construct(PackageManagerInterface $package_manager, PackageDownloaderInterface $package_downloader, ChainQueue $chain_queue) {
    parent::__construct();

    $this->packageManager = $package_manager;
    $this->packageDownloader = $package_downloader;
    $this->chainQueue = $chain_queue;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('ludwig:download')
      ->setDescription($this->trans('commands.ludwig.download.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $packages = array_filter($this->packageManager->getPackages(), function ($package) {
      return empty($package['installed']);
    });
    foreach ($packages as $name => $package) {
      if (empty($package['download_url'])) {
        $io->error(sprintf($this->trans('commands.ludwig.download.errors.no-download-url'), $name));
        continue;
      }

      try {
        $this->packageDownloader->download($package);
        $io->success(sprintf($this->trans('commands.ludwig.download.messages.success'), $name));
      }
      catch (FileTransferException $e) {
        $io->error(new FormattableMarkup($e->getMessage(), $e->arguments));
        return;
      }
      catch (\Exception $e) {
        $io->error($e->getMessage());
        return;
      }
    }

    if (!empty($packages)) {
      $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
    else {
      $io->success($this->trans('commands.ludwig.download.messages.no-download'));
    }
  }

}
