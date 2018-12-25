<?php

namespace Drupal\purge\Plugin\Purge\TagsHeader;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Describes a plugin that adds and formats a cache tags header on responses.
 */
interface TagsHeaderInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Fetch the HTTP response header name.
   *
   * @warning
   *   In RFC #6648 the use of 'X-' as header prefixes has been deprecated
   *   for "application protocols", this naturally includes Drupal. Therefore
   *   if this is possible, consider header names without this prefix.
   *
   * @throws \LogicException
   *   Thrown when the returned value isn't a string.
   *
   * @see \Drupal\purge\Annotation\PurgeTagsHeader::$header_name
   * @see http://tools.ietf.org/html/rfc6648
   *
   * @return string
   *   Name of the HTTP header to send out on responses.
   */
  public function getHeaderName();

  /**
   * Format the given cache tags for the header value representation.
   *
   * @param string[] $tags
   *   A set of cache tags.
   *
   * @throws \LogicException
   *   Thrown when the returned value isn't a string.
   *
   * @see \Drupal\Core\Cache\CacheableDependencyInterface::getCacheTags().
   *
   * @return string
   *   String representing the given headers.
   */
  public function getValue(array $tags);

}
