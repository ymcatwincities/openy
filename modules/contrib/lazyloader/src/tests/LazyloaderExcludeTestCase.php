<?php
namespace Drupal\lazyloader\Tests;

/**
 * Tests Lazyloader's exclusion functionality.
 *
 * @group Lazyloader
 */
class LazyloaderExcludeTestCase extends LazyloaderTestBase {

  /**
   * Tests functioning of the content type exclude settings.
   */
  function testContentTypeExclude() {
    // Test default rendering of a node.
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled();

    // Test rendering with content type exclusion enabled.
    $edit['lazyloader_content_types[article]'] = 'article';
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled(FALSE, 'Lazyloader is disabled for page nodes when limited to article content types.');

    // Test rendering when the page type is supposed to be rendered.
    $edit['lazyloader_content_types[article]'] = FALSE;
    $edit['lazyloader_content_types[page]'] = 'page';
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled(TRUE, 'Lazyloader is enabled for page nodes when limited to page content types.');
  }

  /**
   * Test functioning of the path exclude setting.
   */
  function testPathExclude() {
    $alias = $this->node->path['alias'];
    $this->drupalGet($alias);
    $this->assertLazyloaderEnabled();

    $edit['lazyloader_paths'] = $alias;
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet($alias);
    $this->assertLazyloaderEnabled(FALSE, 'Lazyloader disabled for disabled alias');

    $edit['lazyloader_paths'] = '*' . substr($alias, 2,2) . '*';
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet($alias);
    $this->assertLazyloaderEnabled(FALSE, 'Lazyloader disabled for disabled alias with wildcards');
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled(FALSE, 'Lazyloader is also disabled on internal path if alias with wildcard matches ');
  }

  /**
   * Test functioning of the filename exclude setting.
   */
  function testFilenameExclude() {
    $node = node_view($this->node);

    \Drupal::configFactory()->getEditable('lazyloader.settings')->set('lazyloader_excluded_filenames', $node['field_images'][0]['#item']['filename'])->save();
    $this->drupalGet("node/{$this->node->nid}");

    foreach (\Drupal\Core\Render\Element::children($node['field_images']) as $image) {
      $image = $node['field_images'][$image]['#item'];
      $pattern = '/data-echo=".*' . preg_quote($image['filename']) . '/';

      // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/lazyloader.settings.yml and config/schema/lazyloader.schema.yml.
if ($image['filename'] !== \Drupal::config('lazyloader.settings')->get('lazyloader_excluded_filenames')) {
        $this->assertPattern("{$pattern}");
        $this->assertPattern("{$pattern}", 'Image is lazyloaded when not excluded by filename.');
      }
      else {
        $this->assertNoPattern("{$pattern}", 'Image is NOT lazyloaded when excluded by filename.');
      }
    }
  }

  /**
   * Test functioning of the image style exclude setting.
   */
  function testImageStyleExclude() {
    // Test default rendering of a node.
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled();

    // Enable exclusion option, but don't disable the current image style.
    $edit['lazyloader_image_styles[medium]'] = 'medium';
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled(TRUE, 'Lazyloader is enabled for <em>medium</em> image style.');

    // Enable exclusion option, now excluding the current image style.
    $edit['lazyloader_image_styles[medium]'] = FALSE;
    $edit['lazyloader_image_styles[large]'] = 'large';
    $this->drupalPost("admin/config/media/lazyloader/exclude", $edit, t('Save configuration'));
    $this->drupalGet("node/{$this->node->nid}");
    $this->assertLazyloaderEnabled(FALSE, 'Lazyloader is disabled for <em>medium</em> image style.');

    // @todo test that image style exclusion only affects images using an image
    // style.
  }

}
