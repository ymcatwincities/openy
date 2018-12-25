<?php

namespace Drupal\Tests\ludwig\Unit;

use Drupal\ludwig\PackageManager;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\ludwig\PackageManager
 * @group ludwig
 */
class PackageManagerTest extends UnitTestCase {

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManager
   */
  protected $manager;

  /**
   * Package fixtures.
   *
   * @var array
   */
  protected $packages = [
    'extension' => [
      'lightning' => [
        'require' => [
          'symfony/css-selector' => [
            'version' => 'v3.2.8',
            'url' => 'https://github.com/symfony/css-selector/archive/v3.2.8.zip',
          ],
        ],
      ],
      'test1' => [
        'require' => [
          'symfony/intl' => [
            'version' => 'v3.2.8',
            'url' => 'https://github.com/symfony/intl/archive/v3.2.8.zip',
          ],
        ],
      ],
      'test2' => [
        'require' => [
          'symfony/config' => [
            'version' => 'v3.2.8',
            'url' => 'https://github.com/symfony/config/archive/v3.2.8.zip',
          ],
        ],
      ],
    ],
    'installed' => [
      'symfony/config' => [
        'name' => 'symfony/config',
        'description' => 'Symfony Config Component',
        'homepage' => 'http://symfony.com',
        'autoload' => [
          'psr-4' => ['Symfony\\Component\\Config\\' => ''],
        ],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $structure = [
      'profiles' => [
        'lightning' => [
          'lightning.info.yml' => 'type: profile',
          'ludwig.json' => json_encode($this->packages['extension']['lightning']),
        ],
      ],
      'modules' => [
        'test1' => [
          'ludwig.json' => json_encode($this->packages['extension']['test1']),
          'test1.info.yml' => 'type: module',
        ],
      ],
      'sites' => [
        'all' => [
          'modules' => [
            'test2' => [
              'ludwig.json' => json_encode($this->packages['extension']['test2']),
              'test2.info.yml' => 'type: module',
              'lib' => [
                'symfony-config' => [
                  'v3.2.8' => [
                    'composer.json' => json_encode($this->packages['installed']['symfony/config']),
                  ],
                ]
              ]
            ],
          ],
        ],
      ],
    ];
    vfsStream::setup('drupal', null, $structure);

    $this->manager = new PackageManager('vfs://drupal');

  }

  /**
   * @covers ::getPackages
   */
  public function testGetPackages() {
    $expected_packages = [
      'symfony/css-selector' => [
        'name' => 'symfony/css-selector',
        'version' => 'v3.2.8',
        'description' => '',
        'homepage' => '',
        'provider' => 'lightning',
        'download_url' => 'https://github.com/symfony/css-selector/archive/v3.2.8.zip',
        'path' => 'profiles/lightning/lib/symfony-css-selector/v3.2.8',
        'namespace' => '',
        'src_dir' => '',
        'installed' => FALSE,
      ],
      'symfony/config' => [
        'name' => 'symfony/config',
        'version' => 'v3.2.8',
        'description' => 'Symfony Config Component',
        'homepage' => 'http://symfony.com',
        'provider' => 'test2',
        'download_url' => 'https://github.com/symfony/config/archive/v3.2.8.zip',
        'path' => 'sites/all/modules/test2/lib/symfony-config/v3.2.8',
        'namespace' => 'Symfony\\Component\\Config',
        'src_dir' => '',
        'installed' => TRUE,
      ],
      'symfony/intl' => [
        'name' => 'symfony/intl',
        'version' => 'v3.2.8',
        'description' => '',
        'homepage' => '',
        'provider' => 'test1',
        'download_url' => 'https://github.com/symfony/intl/archive/v3.2.8.zip',
        'path' => 'modules/test1/lib/symfony-intl/v3.2.8',
        'namespace' => '',
        'src_dir' => '',
        'installed' => FALSE,
      ],
    ];

    $required_packages = $this->manager->getPackages();
    $this->assertEquals($expected_packages, $required_packages);
  }

}
