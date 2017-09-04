<?php

namespace Drupal\Tests\lazyloader\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\AssertContentTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\User;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group lazyloader
 */
class LazyloaderThemeTest extends KernelTestBase {

  use UserCreationTrait;

  protected $user;
  protected $node;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image', 'lazyloader', 'path', 'user', 'node', 'field', 'system', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('system', 'sequences');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', 'node_access');
    $this->installConfig('system');
    $this->installConfig('lazyloader');

    NodeType::create([
      'type' => 'page',
    ])->save();

    $user = $this->createUser([
      'create page content',
      'edit own page content',
      'administer lazyloader',
      'administer url aliases',
      'create url aliases'
    ]);

    // Add unlimited image field.
    $field_storage = FieldStorageConfig::create([
      'type' => 'image',
      'field_name' => 'field_images',
      'cardinality' => -1,
      'entity_type' => 'node',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_images',
      'entity_type' => 'node',
      'bundle' => 'page',
    ]);
    $field->save();

    ImageStyle::create([
      'name' => 'medium',
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = entity_get_display('node', 'page', 'default');
    $display->setComponent('field_images', [
      'type' => 'image',
      'settings' => [
        'image_style' => 'medium',
      ],
    ]);
    $display->save();

    $images = $this->drupalGetTestFiles('image');
    foreach ($images as $key => $image) {
      $file = File::create((array) $image);
      $file->save();
      $images[$key] = $file->id();
    }

    $user = User::create([
      'name' => 'muh',
    ]);
    $user->save();

    $settings = [
      'type' => 'page',
      'field_images' => $images,
      'path' => [
        'alias' => '/' . $this->randomMachineName(),
      ],
      'title' => 'test title',
      'uid' => $user->id(),
    ];

    $this->node = Node::create($settings);
    $this->node->save();
  }

  /**
   * Gets a list of files that can be used in tests.
   *
   * The first time this method is called, it will call
   * simpletest_generate_file() to generate binary and ASCII text files in the
   * public:// directory. It will also copy all files in
   * core/modules/simpletest/files to public://. These contain image, SQL, PHP,
   * JavaScript, and HTML files.
   *
   * All filenames are prefixed with their type and have appropriate extensions:
   * - text-*.txt
   * - binary-*.txt
   * - html-*.html and html-*.txt
   * - image-*.png, image-*.jpg, and image-*.gif
   * - javascript-*.txt and javascript-*.script
   * - php-*.txt and php-*.php
   * - sql-*.txt and sql-*.sql
   *
   * Any subsequent calls will not generate any new files, or copy the files
   * over again. However, if a test class adds a new file to public:// that
   * is prefixed with one of the above types, it will get returned as well, even
   * on subsequent calls.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return
   *   List of files in public:// that match the filter(s).
   */
  protected function drupalGetTestFiles($type, $size = NULL) {
    if (empty($this->generatedTestFiles)) {
      // Generate binary test files.
      $lines = array(64, 1024);
      $count = 0;
      foreach ($lines as $line) {
        $this->generateFile('binary-' . $count++, 64, $line, 'binary');
      }

      // Generate ASCII text test files.
      $lines = array(16, 256, 1024, 2048, 20480);
      $count = 0;
      foreach ($lines as $line) {
        $this->generateFile('text-' . $count++, 64, $line, 'text');
      }

      // Copy other test files from simpletest.
      $original = drupal_get_path('module', 'simpletest') . '/files';
      $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, PublicStream::basePath());
      }

      $this->generatedTestFiles = TRUE;
    }

    $files = array();
    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
      $files = file_scan_directory('public://', '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->uri);
          if ($stats['size'] != $size) {
            unset($files[$file->uri]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  protected function drupalCompareFiles($file1, $file2) {
    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

  protected function generateFile($filename, $width, $lines, $type = 'binary-text') {
    $text = '';
    for ($i = 0; $i < $lines; $i++) {
      // Generate $width - 1 characters to leave space for the "\n" character.
      for ($j = 0; $j < $width - 1; $j++) {
        switch ($type) {
          case 'text':
            $text .= chr(rand(32, 126));
            break;
          case 'binary':
            $text .= chr(rand(0, 31));
            break;
          case 'binary-text':
          default:
            $text .= rand(0, 1);
            break;
        }
      }
      $text .= "\n";
    }

    // Create filename.
    file_put_contents('public://' . $filename . '.txt', $text);
    return $filename;
  }

  /**
   * Tests theme_lazyloader_image() and lazyloader's override of theme_image().
   */
  public function testThemeLazyloaderImage() {
    $request = Request::create('/');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    \Drupal::requestStack()->push($request);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $image = $this->node->field_images;
    $path = file_url_transform_relative(file_create_url($image->entity->uri->value));

    \Drupal::configFactory()->getEditable('lazyloader.configuration')
      ->set('enabled', TRUE)
      ->save();

    $render_array = [
      '#uri' => $image->entity->uri->value,
      '#theme' => 'image',
    ];

    $result = $renderer->renderPlain($render_array);
    $this->setRawContent($result);

    $images = $this->cssSelect('img');
    $main_image = $images[0];
    $this->assertEquals('data:image/gif;base64,R0lGODlhAQABAIAAAP7//wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==', (string) $main_image['src']);

    $fallback_image = $images[1];
    $this->assertEquals($path, (string) $fallback_image['src']);
  }
}
