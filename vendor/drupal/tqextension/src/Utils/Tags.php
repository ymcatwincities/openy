<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

trait Tags
{
    /**
     * @var string[]
     */
    private static $tags = [];

    /**
     * @param string $tag
     *   The name of tag.
     *
     * @return bool
     *   Indicates the state of tag existence in a feature and/or scenario.
     */
    public static function hasTag($tag)
    {
        return isset(self::$tags[$tag]);
    }

    /**
     * @param string $tag
     *   The name of tag.
     * @param string $default
     *   Default value, if tag does not exist or empty.
     *
     * @return string
     *   Tag value or an empty string.
     */
    public static function getTag($tag, $default = '')
    {
        return empty(self::$tags[$tag]) ? $default : self::$tags[$tag];
    }

    /**
     * @param string[] $tags
     */
    public static function collectTags(array $tags)
    {
        foreach ($tags as $tag) {
            $values = explode(':', $tag);
            $value = '';

            if (count($values) > 1) {
                list($tag, $value) = $values;
            }

            self::$tags[strtolower($tag)] = $value;
        }
    }

    public static function clearTags()
    {
        self::$tags = [];
    }
}
