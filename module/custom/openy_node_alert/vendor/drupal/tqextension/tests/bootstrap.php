<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */

use Drupal\Core\DrupalKernel;
use Drupal\TqExtension\Utils\Database\Operator;
use Symfony\Component\HttpFoundation\Request;

define('TQEXTENSION_ROOT', dirname(__DIR__));
// Drupal configuration.
define('DRUPAL_CORE', (int) getenv('DRUPAL_CORE') ?: 7);
define('DRUPAL_BASE', __DIR__ . '/drupal_tqextension_phpunit_' . DRUPAL_CORE);
define('DRUPAL_HOST', '127.0.0.1:1349');
define('DRUPAL_USER', 'admin');
define('DRUPAL_PASS', 'admin');
// Drupal modules.
define('DRUPAL_MODULES_SOURCE', sprintf('%s/modules/%s', __DIR__, DRUPAL_CORE));
define('DRUPAL_MODULES_DESTINATION', sprintf('%s/sites/default/modules', DRUPAL_BASE));
// Drush configuration.
define('DRUSH_BINARY', realpath('./bin/drush'));
// Database configuration.
define('DRUPAL_DB_HOST', '127.0.0.1');
define('DRUPAL_DB_USER', (string) getenv('DRUPAL_DB_USER') ?: 'root');
define('DRUPAL_DB_PASS', (string) getenv('DRUPAL_DB_PASS'));
define('DRUPAL_DB_NAME', basename(DRUPAL_BASE));
// Behat configuration.
define('CONFIG_FILE', __DIR__ . '/behat/behat.yml');
// Routing configuration.
define('ROUTER_URL', 'https://gist.githubusercontent.com/shawnachieve/4592ea196d1c8519e3b6/raw/0fbf62e5f6ac6eca09f013d8ff9b080f0da50dbb/.ht_router.php');
define('ROUTER_FILE', DRUPAL_BASE . '/' . basename(ROUTER_URL));

if (!in_array(DRUPAL_CORE, [7, 8])) {
    printf("Drupal %s is not supported.\n", DRUPAL_CORE);
    exit(1);
}

/**
 * Execute Shell command.
 *
 * @param string $command
 *   String with placeholders for "sprintf()".
 * @param string[] $placeholders
 *   Placeholder values.
 *
 * @return string
 *   Resulting output.
 */
function shellExec($command, array $placeholders = [])
{
    return trim(shell_exec(vsprintf($command, $placeholders)));
}

/**
 * Execute Drush command.
 *
 * @param string $command
 *   Drush command with arguments.
 * @param array $arguments
 *   Values for placeholders in first parameter.
 *
 * @return string
 */
function drush($command, array $arguments = [])
{
    return shell_exec(sprintf('%s %s -r %s -y', DRUSH_BINARY, vsprintf($command, $arguments), DRUPAL_BASE));
}

/**
 * Prepare configuration file for Behat.
 *
 * @param bool $restore
 *   Replace placeholders by actual data or restore original file.
 *
 * @return bool
 */
function behatConfig($restore = false)
{
    $arguments = [
        '<DRUPAL_HOST>' => DRUPAL_HOST,
        '<DRUPAL_PATH>' => DRUPAL_BASE,
    ];

    if ($restore) {
        $arguments = array_flip($arguments);
    }

    return file_put_contents(CONFIG_FILE, strtr(file_get_contents(CONFIG_FILE), $arguments));
}

// Drop and create database.
(new Operator(DRUPAL_DB_USER, DRUPAL_DB_PASS, DRUPAL_DB_HOST))->clear(DRUPAL_DB_NAME);

// Download Drupal and rename the folder.
if (!file_exists(DRUPAL_BASE)) {
    drush('dl drupal-%s --drupal-project-rename=%s --destination=%s', [
        DRUPAL_CORE,
        DRUPAL_DB_NAME,
        dirname(DRUPAL_BASE),
    ]);
}

// Install Drupal.
drush('si standard --db-url=mysql://%s:%s@%s/%s --account-name=%s --account-pass=%s', [
    DRUPAL_DB_USER,
    DRUPAL_DB_PASS,
    DRUPAL_DB_HOST,
    DRUPAL_DB_NAME,
    DRUPAL_USER,
    DRUPAL_PASS,
]);

if (!file_exists(ROUTER_FILE)) {
    $index = sprintf('%s/index.php', DRUPAL_BASE);

    shellExec('wget -O %s %s', [ROUTER_FILE, ROUTER_URL]);
    file_put_contents($index, str_replace('getcwd()', "'" . DRUPAL_BASE . "'", file_get_contents($index)));
}

// Check for previously launched server. It may stay alive after tests fail.
$processId = (int) shellExec("lsof -Pni4 | grep '%s' | awk '{print $2}'", [DRUPAL_HOST]);

// Kill previously launched server to run it again.
if ($processId > 0) {
    shellExec("kill $processId > /dev/null 2>&1");
}

// Run built-in PHP web-server.
$processId = shellExec('php -S %s -t %s %s >/dev/null 2>&1 & echo $!', [DRUPAL_HOST, DRUPAL_BASE, ROUTER_FILE]);
// Get the list of modules for enabling.
$modulesList = explode("\n", shellExec('ls -d %s', [DRUPAL_MODULES_SOURCE]));

// Bootstrap Drupal to make an API available.
$_SERVER['REMOTE_ADDR'] = 'localhost';
// Change working directory to the Drupal root to programmatically bootstrap the API.
chdir(DRUPAL_BASE);

/**
 * Run necessary processing for kernel bootstrapping and get the callback.
 *
 * @return \Closure
 *   Anonymous function for bootstrapping Drupal.
 */
$bootstrap = function () use (&$modulesList) {
    switch (DRUPAL_CORE) {
        case 7:
            // Required for using metadata wrappers.
            array_unshift($modulesList, 'entity');

            return function () {
                // Needs to be defined here since everywhere used in "bootstrap.inc".
                // Drupal 7 defines this constant in "index.php".
                define('DRUPAL_ROOT', DRUPAL_BASE);

                require_once DRUPAL_BASE . '/includes/bootstrap.inc';
                drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
            };

        case 8:
            return function () {
                // No need to define "DRUPAL_ROOT" for Drupal 8 since it defined in "bootstrap.inc"
                // which will be included by "DrupalKernel::bootEnvironment()".
                $autoloader = require_once DRUPAL_BASE . '/autoload.php';
                $request = Request::createFromGlobals();

                /** @see \Drupal\Driver\Cores\Drupal8::bootstrap() */
                DrupalKernel::createFromRequest($request, $autoloader, 'prod')->prepareLegacyRequest($request);
            };
    }
};

foreach ([
    // Ensure writable permissions for "sites/default".
    'chmod 0755 %s/sites/default' => [DRUPAL_BASE],
    // Remove custom modules to overwrite them fully.
    'rm -rf %s' => [DRUPAL_MODULES_DESTINATION],
    // Copy modules for testing.
    'cp -r %s %s' => [DRUPAL_MODULES_SOURCE, DRUPAL_MODULES_DESTINATION],
] as $command => $arguments) {
    shellExec($command, $arguments);
}

// Get the bootstrapping function and execute the preprocess for core if needed.
$bootstrap = $bootstrap();
// Enable modules.
drush(sprintf('en %s', implode(' ', $modulesList)));
// Bootstrap kernel.
$bootstrap();
// Initialize Behat configuration.
behatConfig();

register_shutdown_function(function () use ($processId) {
    shellExec("kill $processId > /dev/null 2>&1");
    behatConfig(true);
});
