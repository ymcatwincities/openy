<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\XPath;

// Helpers.
use Behat\DebugExtension\Debugger;
use Behat\Mink\Element\NodeElement;

class InaccurateText
{
    use Debugger;

    /**
     * The text, desired to be found.
     *
     * @var string
     */
    private $text = '';
    /**
     * XPath query.
     *
     * @var string
     */
    private $query = '';
    /**
     * The name of element's attribute (without "@" symbol). If will
     * be specified then inaccurate search will be done in its value.
     *
     * @var string
     */
    private $attribute = '';
    /**
     * An element to search in. Must be specified to performing queries.
     *
     * @var NodeElement
     */
    private $parent;

    /**
     * @code
     * // Any labels with text.
     * // XPath: //label[text()]
     * (string) (new InaccurateText)->query('//label');
     * @endcode
     *
     * @code
     * // Any labels with "for" attribute and text().
     * // XPath: //label[@for][text()]
     * (string) (new InaccurateText)->query('//label[@for]'),
     * @endcode
     *
     * @code
     * // Any labels with text, starts with "The text".
     * // XPath: //label[text()[starts-with(., 'The text')]]
     * (string) (new InaccurateText)->query('//label')->text('The text');
     * @endcode
     *
     * @code
     * // Any labels with text in "data-title" attribute starts with "The text".
     * // XPath: //label[@data-title[starts-with(., 'The text')]]
     * (string) (new InaccurateText)->query('//label')->attribute('data-title')->text('The text');
     * @endcode
     *
     * @code
     * // Any parent elements with text in "method" attribute starts with "POST".
     * // XPath: /ancestor::*[@method[starts-with(., 'POST')]]
     * (new InaccurateText)->query('/ancestor::*')->attribute('method')->text('POST');
     * @endcode
     *
     * @param string $query
     * @param NodeElement $parent
     */
    public function __construct($query, NodeElement $parent = null)
    {
        if (empty($query)) {
            throw new \InvalidArgumentException('You need to initialize XPath query.');
        }

        $this->query = $query;
        $this->parent = $parent;
    }

    /**
     * @return string
     *   XPath selector.
     */
    public function __toString()
    {
        if (!empty($this->text)) {
            $this->text = "[starts-with(normalize-space(.), '$this->text')]";
        }

        $xpath = sprintf("$this->query[%s$this->text]", empty($this->attribute) ? 'text()' : "@$this->attribute");

        self::debug(['XPath: %s'], [$xpath]);

        return $xpath;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function text($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param string $attribute
     *
     * @return $this
     */
    public function attribute($attribute)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return NodeElement[]
     */
    public function findAll()
    {
        if (null === $this->parent) {
            throw new \RuntimeException(sprintf('An object "%s" instantiated incorrectly.', __CLASS__));
        }

        return $this->parent->findAll('xpath', (string) $this);
    }

    /**
     * @return NodeElement
     */
    public function find()
    {
        $items = $this->findAll();

        return empty($items) ? null : current($items);
    }
}
