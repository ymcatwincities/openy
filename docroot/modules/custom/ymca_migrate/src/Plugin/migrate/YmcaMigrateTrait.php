<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Helper functions for Ymca Migrate plugins.
 */
trait YmcaMigrateTrait {

  /**
   * Check if environment is dev.
   *
   * @return bool
   *   TRUE if environment is dev.
   */
  public function isDev() {
    // For now (in the sql flow) we should run all migrations not in dev mode.
    return FALSE;
  }

  /**
   * Get destination ID.
   *
   * @param array $source
   *   Example: ['site_page_id' => 10].
   * @param array $migrations
   *   List of migrations.
   *
   * @return mixed
   *   Destination ID.
   */
  public static function getDestinationId(array $source, array $migrations) {
    foreach ($migrations as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource($source);
      if (!empty($dest)) {
        return $dest['destid1'];
      }
    }

    return FALSE;
  }

  /**
   * Create and get Promo Block.
   *
   * @param array $data
   *   Required list of items:
   *    - info: Description,
   *    - header: Block header,
   *    - image_id: Image ID,
   *    - image_alt: Image alt,
   *    - link_uri: Link URI,
   *    - link_title: Link title,
   *    - content: Content.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createPromoBlock(array $data) {
    $block = BlockContent::create([
      'type' => 'promo_block',
      'langcode' => 'en',
      'info' => $data['info'],
      'field_block_header' => $data['header'],
      'field_image' => [
        'target_id' => $data['image_id'],
        'alt' => $data['image_alt'],
      ],
      'field_link' => [
        'uri' => $data['link_uri'],
        'title' => $data['link_title']
      ],
      'field_block_content' => [
        'value' => $data['content'],
        'format' => 'full_html',
      ],
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Create slide show item.
   *
   * @param array $data
   *   Block data.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createSlideShowItem($data) {
    $block = BlockContent::create([
      'type' => 'slide_show_item',
      'langcode' => 'en',
      'info' => $data['info'],
      'field_button' => $data['button'],
      'field_image' => [
        'target_id' => $data['image_id'],
        'alt' => $data['image_alt'],
      ],
      'field_block_content' => [
        'value' => $data['content'],
        'format' => 'full_html',
      ],
      'field_title' => $data['title'],
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Create slide show item.
   *
   * @param array $data
   *   Block data.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createSlideShow($data) {
    $items = [];
    foreach ($data['items'] as $item) {
      $items[]['target_id'] = $item;
    }

    $block = BlockContent::create([
      'type' => 'slide_show',
      'langcode' => 'en',
      'info' => $data['info'],
      'field_slide_show_item' => $items,
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Create and get Expander Block.
   *
   * @param array $data
   *   Required list of items:
   *    - info: Description,
   *    - header: Block header,
   *    - content: Content.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createExpanderBlock(array $data) {
    $block = BlockContent::create([
      'type' => 'expander_block',
      'langcode' => 'en',
      'info' => $data['info'],
      'field_block_header' => $data['header'],
      'field_block_content' => [
        'value' => $data['content'],
        'format' => 'full_html',
      ],
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Create and get Date Block.
   *
   * @param array $data
   *   Required list of items:
   *    - info: Description,
   *    - date_start: Start date,
   *    - date_end: End date,
   *    - content_before: Content before,
   *    - content_during: Content between,
   *    - content_after: Content end.
   * @param bool $filter
   *   Filter or not outdated blocks.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createDateBlock($data, $filter = TRUE) {
    if ($filter) {
      // Check if block is outdated.
      /** @var \DateTime $date */
      $date = \DateTime::createFromFormat(
        DATETIME_DATETIME_STORAGE_FORMAT,
        $data['date_end'],
        new \DateTimeZone(
          \Drupal::config('system.date')->get('timezone')['default']
        )
      );

      if (!$date) {
        \Drupal::logger('YmcaMigrateTrait')->info(
          '[CLIENT] Date for Date Block is invalid: @info.',
          ['@info' => $data['info']]
        );
        return FALSE;
      }

      if ($date->getTimestamp() > REQUEST_TIME) {
        \Drupal::logger('YmcaMigrateTrait')->info(
          '[CLIENT] Outdated Date Block was filtered out: @info.',
          ['@info' => $data['info']]
        );
        return FALSE;
      }
    }

    $block = BlockContent::create([
      'type' => 'ygtc_date_block',
      'langcode' => 'en',
      'info' => $data['info'],
      'field_ygtc_start_date' => $data['date_start'],
      'field_ygtc_end_date' => $data['date_end'],
      'field_ygtc_content_date_before' => [
        'value' => $data['content_before'],
        'format' => 'full_html',
      ],
      'field_ygtc_content_date_between' => [
        'value' => $data['content_during'],
        'format' => 'full_html',
      ],
      'field_ygtc_content_date_end' => [
        'value' => $data['content_after'],
        'format' => 'full_html',
      ],
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Convert date to Drupal format.
   *
   * @param string $input
   *   Date in format: 'd/m/Y h:i:s a'.
   *
   * @return string
   *   Date in format: 'Y-m-d\TH:i:s'.
   */
  public function convertDate($input) {
    $date = \DateTime::createFromFormat(
      'm/d/Y h:i:s a',
      $input,
      new \DateTimeZone(
        \Drupal::config('system.date')->get('timezone')['default']
      )
    );

    if (!$date) {
      \Drupal::logger('YmcaMigrateTrait')->error(
        t('[LEAD]: Date is incorrect.')
      );
      return FALSE;
    }

    return $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
  }

  /**
   * Parse Promo Block.
   *
   * @param string $text
   *   Input text.
   *
   * @return array
   *   Block data.
   *
   * @throws \Exception
   *   The problem fo the parser.
   */
  public function parsePromoBlock($text = '') {
    $block_data = [];

    if ($text == '') {
      throw new \Exception('Input text is empty.');
    }

    $regex = '/<p.*><img.*{{internal_asset_link_(.*)}}.*alt=\\"(.*)\\".*<\/p>.*[\n]<h2.*>(.*)<\/h2>.*[\n]<p.*>(.*)<\/p>.*[\n]<p.*><a[^{}]*href=\\"({{internal_page_link_.*}}|http.*)\\".*>(.*)<.*<\/p>/imU';
    preg_match_all($regex, $text, $match);

    if (empty($match[0])) {
      throw new \Exception('No promo blocks were detected.');
    }

    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaAssetsTokensMap $ymca_asset_tokens_map */
    $ymca_asset_tokens_map = \Drupal::service('ymcaassetstokensmap.service');

    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaTokensMap $ymca_tokens_map */
    $ymca_tokens_map = \Drupal::service('ymcatokensmap.service');

    // We hope that there comes only single promoBlock.
    // Check if regex found more.
    if (count($match[0]) > 1) {
      throw new \Exception('Parser has found more than one Promo Block');
    }

    $file_id = $ymca_asset_tokens_map->getAssetId($match[1][0]);
    if ($file_id == FALSE) {
      if ($this->isDev()) {
        $file_id = 4;
      }
      else {
        throw new \Exception('Failed to get File ID via Asset ID.');
      }
    }

    // Get link. It could be internal or external.
    $link_text = $match[5][0];
    if (strpos($link_text, '{{internal') !== FALSE) {
      // It's a token.
      preg_match('/{{internal_page_link_(\d*)}}/imU', $link_text, $test);
      if (!isset($test[1]) || !is_numeric($test[1])) {
        throw new \Exception('Failed to parse internal link token.');
      }
      $menu_id = $ymca_tokens_map->getMenuId($test[1]);
      if ($menu_id === FALSE) {
        throw new \Exception(sprintf('Failed to get MenuID: [id: %s]', $test[1]));
      }

      /* @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link_entity */
      $menu_link_entity = \Drupal::entityManager()->getStorage('menu_link_content')->load($menu_id);

      /** @var Url $url */
      $url = $menu_link_entity->getUrlObject();
      $path = $url->toString();
      $uri = sprintf('internal:%s', $path);
    }
    else {
      $uri = $link_text;
    }

    $block_data = [
      'info' => sprintf(
        'Promo Block - %s [asset: %d]',
        $match[2][0],
        $file_id
      ),
      'header' => $match[3][0],
      'image_id' => $file_id,
      'image_alt' => $match[2][0],
      'link_uri' => $uri,
      'link_title' => $match[6][0],
      'content' => $match[4][0],
    ];

    return $block_data;
  }

  /**
   * Create string for embedding block.
   *
   * @param \Drupal\block_content\Entity\BlockContent $block
   *   Block object.
   *
   * @return string
   *   String to put to WYSIWYG.
   */
  public function getEmbedBlockString(BlockContent $block) {
    $string = '<drupal-entity data-align="none" data-embed-button="block" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-settings="{&quot;view_mode&quot;:&quot;full&quot;}" data-entity-id="%u" data-entity-label="Block" data-entity-type="block_content" data-entity-uuid="%s"></drupal-entity>';
    return sprintf($string, $block->id(), $block->uuid());
  }

  /**
   * List of skipped pages confirmed by the Client.
   *
   * @return array
   *   List of Page IDs.
   */
  public function getSkippedPages() {
    // Pages from GA.
    $part1 = [
      4791,
      4817,
      4821,
      4822,
      4823,
      4824,
      4825,
      4826,
      4827,
      4828,
      4829,
      4830,
      4831,
      4832,
      4833,
      4837,
      4838,
      4839,
      4840,
      4841,
      4842,
      4843,
      4844,
      4845,
      4846,
      4847,
      4848,
      4849,
      4850,
      4853,
      4857,
      4863,
      4864,
      4865,
      4866,
      4867,
      4868,
      4870,
      4872,
      4873,
      4874,
      4875,
      4881,
      4882,
      4883,
      4884,
      4885,
      4886,
      4887,
      4888,
      4889,
      4890,
      4891,
      4892,
      4893,
      4895,
      4900,
      4901,
      4902,
      4903,
      4904,
      4905,
      4906,
      4938,
      5079,
      5084,
      5090,
      5093,
      5122,
      5123,
      5224,
      5252,
      5301,
      6039,
      6041,
      6043,
      6045,
      6047,
      6048,
      6049,
      6050,
      6051,
      6052,
      6053,
      6055,
      6056,
      6057,
      6059,
      6061,
      6062,
      6063,
      6065,
      6066,
      6067,
      6068,
      6069,
      6071,
      6072,
      6073,
      6075,
      6076,
      6077,
      6078,
      6079,
      6080,
      6081,
      6082,
      6083,
      6131,
      6133,
      6134,
      6137,
      6138,
      6139,
      6140,
      6142,
      6143,
      6146,
      6147,
      6148,
      6149,
      6150,
      6151,
      6152,
      6153,
      6154,
      6155,
      6156,
      6157,
      6158,
      6159,
      6160,
      6161,
      6162,
      6163,
      6164,
      6165,
      6166,
      6167,
      6168,
      6169,
      6170,
      6712,
      6716,
      6719,
      6720,
      6738,
      6740,
      6742,
      6743,
      6744,
      6745,
      6760,
      6764,
      6765,
      6767,
      6769,
      6770,
      6776,
      6778,
      6779,
      6783,
      6801,
      6802,
      6807,
      6811,
      6812,
      6817,
      6818,
      6823,
      6825,
      6827,
      6828,
      6829,
      6837,
      6838,
      6840,
      6852,
      6853,
      6856,
      6857,
      6861,
      7247,
      7248,
      7249,
      7250,
      7251,
      7252,
      7255,
      7256,
      7257,
      7258,
      7427,
      7428,
      7429,
      7430,
      7431,
      7432,
      7435,
      7436,
      7437,
      7438,
      7439,
      7440,
      7441,
      7442,
      7497,
      7498,
      7839,
      7840,
      7888,
      7889,
      7929,
      7955,
      7956,
      7976,
      7977,
      7997,
      7998,
      8019,
      8060,
      8061,
      8081,
      8082,
      8102,
      8103,
      8123,
      8124,
      8145,
      8165,
      8166,
      8207,
      8208,
      8228,
      8229,
      8249,
      8250,
      8270,
      8271,
      8291,
      8292,
      8313,
      8621,
      8636,
      8644,
      8938,
      8942,
      8948,
      8949,
      8968,
      8969,
      8975,
      8976,
      8982,
      8983,
      8989,
      8990,
      8996,
      9004,
      9005,
      9358,
      9911,
      10520,
      10521,
      11189,
      11272,
      11273,
      11744,
      11745,
      11748,
      12101,
      12124,
      12130,
      12136,
      12143,
      12147,
      12149,
      12151,
      12153,
      12155,
      12157,
      12158,
      12163,
      12165,
      12168,
      12170,
      12171,
      12539,
      12541,
      12543,
      12582,
      12794,
      12795,
      12796,
      12812,
      12813,
      12814,
      12835,
      12836,
      12837,
      12842,
      12967,
      13004,
      13005,
      13064,
      13067,
      13069,
      13190,
      13317,
      13617,
      13618,
      13619,
      13620,
      13621,
      13676,
      14089,
      14232,
      14233,
      14234,
      14378,
      14519,
      14520,
      14606,
      14667,
      14668,
      14669,
      14670,
      14876,
      14917,
      14918,
      15062,
      15063,
      15064,
      15070,
      15071,
      15072,
      15073,
      15074,
      15095,
      15122,
      15166,
      15292,
      15294,
      15301,
      15302,
      15303,
      15413,
      15414,
      15415,
      15442,
      15443,
      15444,
      15445,
      15446,
      15462,
      15482,
      15752,
      15831,
      15832,
      15992,
      15996,
      16017,
      16018,
      16019,
      16020,
      16021,
      16022,
      16023,
      16024,
      16025,
      16026,
      16027,
      16028,
      16029,
      16030,
      16031,
      16032,
      16033,
      16034,
      16035,
      16036,
      16049,
      16056,
      16059,
      16060,
      16064,
      16067,
      16068,
      16071,
      16072,
      16076,
      16080,
      16084,
      16088,
      16147,
      16148,
      16151,
      16245,
      16254,
      16278,
      16280,
      16283,
      16323,
      16324,
      16325,
      16351,
      16360,
      16361,
      16362,
      16411,
      16504,
      16686,
      16843,
      16844,
      16845,
      16846,
      16870,
      16871,
      17067,
      17068,
      17069,
      17203,
      17205,
      17206,
      17207,
      17208,
      17215,
      17244,
      17281,
      17347,
      17348,
      17349,
      17350,
      17351,
      17352,
      17367,
      17472,
      17499,
      17501,
      17503,
      17504,
      17505,
      17506,
      17565,
      17566,
      17567,
      17568,
      17569,
      17570,
      17572,
      17573,
      17574,
      17575,
      17576,
      17577,
      17578,
      17579,
      17580,
      17581,
      17582,
      17583,
      17584,
      17585,
      17586,
      17587,
      17588,
      17589,
      17590,
      17591,
      17592,
      17593,
      17594,
      17595,
      17596,
      17597,
      17598,
      17599,
      17600,
      17601,
      17602,
      17603,
      17604,
      17605,
      17606,
      17607,
      17608,
      17609,
      17610,
      17611,
      17612,
      17613,
      17614,
      17615,
      17616,
      17617,
      17618,
      17619,
      17962,
      18074,
      18081,
      18088,
      18100,
      18104,
      18106,
      18137,
      18138,
      18139,
      18171,
      18213,
      18214,
      18519,
      18635,
      18636,
      18637,
      19147,
      19148,
      19193,
      19311,
      19312,
      19431,
      19770,
      20203,
      20347,
      20348,
      20349,
      20350,
      20447,
      20572,
      20573,
      20574,
      20575,
      20576,
      20577,
      20578,
      20579,
      20580,
      20700,
      20701,
      20849,
      21252,
      21302,
      21303,
      21304,
      21305,
      21306,
      21313,
      21401,
      21789,
      21827,
      21828,
      21830,
      21943,
      21950,
      22438,
      22463,
      22566,
      22572,
      22590,
      22623,
      22664,
      22943,
      22957,
      22958,
      23133,
      23178,
      23179,
      23184,
      23185,
      23217,
      23405,
      23406,
      23408,
      23439,
      23584,
      23619,
      23622,
      23691,
      23746,
      23760,
      23785,
      24048,
      24055,
      24056,
      24065,
      24098,
      24255,
      24299,
      24300,
      24301,
      24304,
      24305,
      24340,
      24348,
      24459,
      24460,
      24461,
      24462,
      24475,
      24476,
      24477,
      24478,
      24479,
      24480,
      24481,
      24563,
      24633,
      24638,
      24759,
      24760,
      24781,
      24920,
      24923,
      24924,
      25192,
      25212,
      25599,
      25649,
      25650,
      25651,
      25653,
      25654,
      25655,
      25870,
      25914,
      25915,
      25925,
      25926,
      25933,
      25936,
      26055,
      26070,
      26091,
      26093,
      26094,
      26125,
      26126,
      26130,
      26132,
      26133,
      26134,
      26135,
      26140,
      26150,
      26155,
      26156,
      26183,
      26201,
      26203,
      26207,
      26212,
      26217,
      26218,
      26232,
      26233,
      26235,
      26236,
      26237,
      26243,
      26245,
      26250,
      26253,
      26275,
      26279,
      26280,
      26282,
      26283,
      26284,
      26285,
      26286,
      26287,
      26288,
      26289,
      26290,
      26291,
      26292,
      26296,
    ];

    // Pages skipped by the client.
    $part2 = [
      4801,
      4837,
      4838,
      4839,
      4840,
      4841,
      4842,
      4843,
      4844,
      4845,
      4846,
      4847,
      4848,
      4849,
      4850,
      4853,
      4872,
      4873,
      4874,
      4875,
      4881,
      4882,
      4883,
      4884,
      4885,
      4886,
      4887,
      4888,
      4889,
      4890,
      4891,
      4892,
      4893,
      5066,
      5084,
      5092,
      5093,
      6131,
      6132,
      6137,
      6138,
      6139,
      6140,
      7247,
      7248,
      7428,
      8228,
      8229,
      8989,
      8990,
      16278,
      16871,
      17067,
      17068,
      17069,
      17281,
      17472,
      17603,
      17604,
      17605,
      17606,
      18104,
      18635,
      18636,
      18637,
      19431,
      20347,
      20348,
      20349,
      20350,
      21302,
      21303,
      21304,
      21305,
      21439,
      21789,
      21950,
      22590,
      22641,
      22643,
      22644,
      22645,
      22646,
      22647,
      22648,
      22650,
      22652,
      22653,
      22654,
      22655,
      22656,
      22657,
      22658,
      22659,
      22660,
      22664,
      22957,
      23760,
      24148,
      24340,
      24426,
      24428,
      24431,
      24436,
      24439,
      24706,
      25654,
      25655,
      26093,
      26095,
      26125,
      26140,
      26150,
      26201,
      26245,
      26280,
      26296
    ];

    return array_merge($part1, $part2);
  }

}
