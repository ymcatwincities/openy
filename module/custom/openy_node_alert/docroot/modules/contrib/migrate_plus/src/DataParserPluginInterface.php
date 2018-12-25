<?php

namespace Drupal\migrate_plus;

/**
 * Defines an interface for data parsers.
 *
 * @see \Drupal\migrate_plus\Annotation\DataParser
 * @see \Drupal\migrate_plus\DataParserPluginBase
 * @see \Drupal\migrate_plus\DataParserPluginManager
 * @see plugin_api
 */
interface DataParserPluginInterface extends \Iterator, \Countable {

}
