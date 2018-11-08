<?php

namespace Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\acquia_purge\HostingInfoInterface;

/**
 * Acquia Purge.
 *
 * @PurgeDiagnosticCheck(
 *   id = "acquia_purge",
 *   title = @Translation("Acquia Purge"),
 *   description = @Translation("Reports the status of the Acquia Purge module."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AcquiaPurgeCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\acquia_purge\HostingInfoInterface
   */
  protected $acquiaPurgeHostinginfo;

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The path to Drupal's main .htaccess file in the app root.
   *
   * @var string
   */
  protected $htaccess;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a AcquiaCloudCheck object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge purgers service.
   * @param \Drupal\acquia_purge\HostingInfoInterface $acquia_purge_hostinginfo
   *   Technical information accessors for the Acquia Cloud environment.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct($root, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, PurgersServiceInterface $purge_purgers, HostingInfoInterface $acquia_purge_hostinginfo, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->htaccess = $root . '/.htaccess';
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
    $this->purgePurgers = $purge_purgers;
    $this->acquiaPurgeHostingInfo = $acquia_purge_hostinginfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('app.root'),
      $container->get('cache.default'),
      $container->get('module_handler'),
      $container->get('purge.purgers'),
      $container->get('acquia_purge.hostinginfo'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Analyze the current Drupal site for signs of applied HTTP Authentication.
   *
   * On Acquia Cloud, all requests using basic HTTP authentication will skip
   * caching and this becomes a problem when still invalidating caches using
   * Acquia Purge. Nothing will fail, but because the invalidations just succeed
   * it creates a false sense of effectiveness.
   *
   * @return bool
   */
  protected function basicHttpAuthenticationFound() {
    $cid = 'acquia_purge_check_basicauth';

    // Attempt to recycle a previously cached answer.
    if ($cache = $this->cache->get($cid)) {
      $found = $cache->data;
    }
    else {
      // Test if the shield module is present, which performs site wide auth!
      $found = $this->moduleHandler->moduleExists('shield');

      // Else, wade through .htaccess for signs of active HTTP auth directives.
      if (!$found && file_exists($this->htaccess) && is_readable($this->htaccess)) {
        $handle = fopen($this->htaccess, "r");
        if ($handle) {
          while (($found == FALSE) && (($line = fgets($handle)) !== FALSE)) {
            $line = trim($line);
            $not_a_comment = strpos($line, '#') === FALSE;
            if ($not_a_comment && (strpos($line, 'AuthType') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'AuthName') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'AuthUserFile') !== FALSE)) {
              $found = TRUE;
            }
            elseif ($not_a_comment && (strpos($line, 'Require valid-user') !== FALSE)) {
              $found = TRUE;
            }
          }
          fclose($handle);
        }
      }

      // Cache the bool for at least two hours to prevent straining the system.
      $this->cache->set($cid, $found, time() + 7200);
    }

    return $found;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $version = system_get_info('module', 'acquia_purge')['version'];
    $version = is_null($version) ? '8.x-1.x-dev' : $version;
    $this->value = $version;

    // Block the entire system when this is a third-party platform.
    if (!$this->acquiaPurgeHostingInfo->isThisAcquiaCloud()) {
      $this->recommendation = $this->t("Acquia Purge only works on your Acquia Cloud environment and doesn't work outside of it.");
      return SELF::SEVERITY_ERROR;
    }

    // Check for the use of basic HTTP authentication.
    if ($this->basicHttpAuthenticationFound()) {
      $this->recommendation = $this->t(
        'Acquia Purge detected that you are protecting your website with basic'
        . ' HTTP authentication. However, on Acquia Cloud all HTTP responses'
        . ' with access authentication deliberately MISS cache to prevent'
        . ' sensitive content from getting served to prying eyes. Acquia Purge'
        . ' cannot detect if specific parts of the site are protected or all'
        . ' pages, but does recommend you to temporarily disable invalidating'
        . " caches if indeed your full site is protected. Please wipe Drupal's"
        . ' "default" cache bin when this warning persists after you updated'
        . ' your .htaccess file or uninstalled the Shield module!'
      );
      return SELF::SEVERITY_WARNING;
    }

    // Issue a warning when the user forgot to add the AcquiaCloudPurger.
    if (!in_array('acquia_purge', $this->purgePurgers->getPluginsEnabled())) {
      $this->recommendation = $this->t("The 'Acquia Cloud' purger is not installed!");
      return SELF::SEVERITY_WARNING;
    }

    // Under normal operating conditions, we'll report site info and version.
    $this->value = $this->t(
      "@site_group.@site_env (@version)",
      [
        '@site_group' => $this->acquiaPurgeHostingInfo->getSiteGroup(),
        '@site_env' => $this->acquiaPurgeHostingInfo->getSiteEnvironment(),
        '@version' => $version,
      ]
    );
    $this->recommendation = " ";
    return SELF::SEVERITY_OK;
  }

}
