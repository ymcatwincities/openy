<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\ServiceContainer;

use Behat\EnvironmentLoader;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\DebugExtension\ServiceContainer\DebugExtension;
use Drupal\Driver\DrupalDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class TqExtension.
 *
 * @package Drupal\TqExtension\ServiceContainer
 */
class TqExtension implements Extension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigKey()
    {
        return 'tq';
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        if (null === $extensionManager->getExtension(DebugExtension::TAG)) {
            $extensionManager->activateExtension('Behat\DebugExtension');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        if (!$container->has('drupal.driver.drupal')) {
            throw new \LogicException(
                'TqExtension is completely depends on DrupalExtension and must be configured after it.'
            );
        }

        /** @var DrupalDriver $drupalDriver */
        $drupalDriver = $container->get('drupal.driver.drupal');
        $drupalDriver->setCoreFromVersion();

        if (!defined('DRUPAL_CORE')) {
            define('DRUPAL_CORE', (int) $drupalDriver->version);
        }

        $loader = new EnvironmentLoader($this, $container, $config);
        $loader->load();
    }

    /**
     * {@inheritDoc}
     *
     * @see EnvironmentExtension::getEnvironmentReaderId()
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @link http://symfony.com/doc/current/components/config/definition.html
     *
     * @example
     * Drupal\TqExtension:
     *   wait_for_redirect: 60
     *   email_account_strings: get_account_strings_for_email
     *   email_accounts:
     *     account_alias:
     *       imap: imap.gmail.com:993/imap/ssl
     *       email: example1@email.com
     *       password: p4sswDstr_1
     *     administrator:
     *       imap: imap.gmail.com:993/imap/ssl
     *       email: example2@email.com
     *       password: p4sswDstr_2
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $config = $builder->children();

        foreach ([
            'wait_for_redirect' => [
                'defaultValue' => 30,
                'info' => 'The timeout (in seconds) for waiting opening a page',
            ],
            'wait_for_email' => [
                'defaultValue' => 30,
                'info' => 'This timeout will be used if you checking an email via IMAP',
            ],
            'email_account_strings' => [
                'defaultValue' => '',
                'info' => 'See detailed description in "docs/examples/EMAIL.md"',
            ],
        ] as $scalarNode => $data) {
            $config = $config->scalarNode($scalarNode)
                ->defaultValue($data['defaultValue'])
                ->info($data['info'])
                ->end();
        }

        $config = $config->arrayNode('email_accounts')
            ->requiresAtLeastOneElement()
            ->prototype('array')
            ->children();

        foreach ([
            'imap' => 'IMAP url without parameters. For example: imap.gmail.com:993/imap/ssl',
            'username' => 'Login from an e-mail account',
            'password' => 'Password from an e-mail account',
        ] as $scalarNode => $info) {
            $config = $config->scalarNode($scalarNode)
                ->isRequired()
                ->cannotBeEmpty()
                ->info($info)
                ->end();
        }

        $config->end()->end()->end()->end();
    }
}
