<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context;

// Contexts.
use Drupal\DrupalExtension\Context\RawDrupalContext;
// Exceptions.
use WebDriver\Exception\NoSuchElement;
// Helpers.
use Behat\Mink\Element\NodeElement;
// Utils.
use Drupal\TqExtension\Utils\XPath;
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class RawPageContext extends RawDrupalContext
{
    /**
     * @var NodeElement
     */
    private static $workingElement;

    /**
     * @return NodeElement
     */
    public function getWorkingElement()
    {
        if (null === self::$workingElement) {
            $this->setWorkingElement($this->getBodyElement());
        }

        return self::$workingElement;
    }

    /**
     * @param NodeElement $element
     */
    public function setWorkingElement(NodeElement $element)
    {
        self::$workingElement = $element;
    }

    public function unsetWorkingElement()
    {
        self::$workingElement = null;
    }

    /**
     * Find all elements matching CSS selector, name of region from config or inaccurate text.
     *
     * @param string $selector
     *   CSS selector, region name or inaccurate text.
     *
     * @return NodeElement[]
     *   List of nodes.
     */
    public function findAll($selector)
    {
        $element = $this->getWorkingElement();

        $elements = $element
            ->findAll($this->computeSelectorType($selector), $selector);

        if (empty($elements)) {
            $elements = $this->inaccurateText('*', $selector, $element)
                ->findAll();
        }

        return (array) $elements;
    }

    /**
     * @param string $selector
     *
     * @return NodeElement
     */
    public function findByCss($selector)
    {
        return $this->getWorkingElement()
            ->find($this->computeSelectorType($selector), $selector);
    }

    /**
     * @param string $selector
     *
     * @return NodeElement|null
     */
    public function findField($selector)
    {
        $selector = ltrim($selector, '#');
        $element = $this->getWorkingElement();

        foreach ($this->findLabels($selector) as $forAttribute => $label) {
            // We trying to find an ID with "-upload" suffix, because some
            // image inputs in Drupal are suffixed by it.
            foreach ([$forAttribute, "$forAttribute-upload"] as $elementID) {
                $field = $element->findById($elementID);

                if (null !== $field) {
                    return $field;
                }
            }
        }

        return $element->findField($selector);
    }

    /**
     * @param string $selector
     *
     * @return NodeElement
     */
    public function findButton($selector)
    {
        $element = $this->getWorkingElement();

        // Search inside of: "id", "name", "title", "alt" and "value" attributes.
        return $element->findButton($selector) ?: $this->inaccurateText('button', $selector, $element)->find();
    }

    /**
     * @param string $text
     *
     * @return NodeElement|null
     */
    public function findByText($text)
    {
        $element = null;

        foreach ($this->inaccurateText('*', $text)->findAll() as $element) {
            if ($element->isVisible()) {
                break;
            }
        }

        return $element;
    }

    /**
     * @param string $locator
     *   Element locator. Can be inaccurate text, inaccurate field label, CSS selector or region name.
     *
     * @throws NoSuchElement
     *
     * @return NodeElement
     */
    public function findElement($locator)
    {
        return $this->findByCss($locator)
            ?: $this->findField($locator)
                ?: $this->findButton($locator)
                    ?: $this->findByText($locator);
    }

    /**
     * Find all field labels by text.
     *
     * @param string $text
     *   Label text.
     *
     * @return NodeElement[]
     */
    public function findLabels($text)
    {
        $labels = [];

        foreach ($this->inaccurateText('label[@for]', $text)->findAll() as $label) {
            $labels[$label->getAttribute('for')] = $label;
        }

        return $labels;
    }

    /**
     * @return NodeElement
     */
    public function getBodyElement()
    {
        return $this->getSession()->getPage()->find('css', 'body');
    }

    /**
     * @param string $selector
     *   Element selector.
     * @param mixed $element
     *   Existing element or null.
     *
     * @throws NoSuchElement
     */
    public function throwNoSuchElementException($selector, $element)
    {
        if (null === $element) {
            throw new NoSuchElement(sprintf('Cannot find an element by "%s" selector.', $selector));
        }
    }

    /**
     * @param string $locator
     * @param string $selector
     *
     * @throws \RuntimeException
     * @throws NoSuchElement
     *
     * @return NodeElement
     */
    public function element($locator, $selector)
    {
        $map = [
            'button' => 'Button',
            'field' => 'Field',
            'text' => 'ByText',
            'css' => 'ByCss',
            '*' => 'Element',
        ];

        if (!isset($map[$locator])) {
            throw new \RuntimeException(sprintf('Locator "%s" is not available.', $locator));
        }

        $selector = DrupalKernelPlaceholder::t($selector);
        $element = $this->{'find' . $map[$locator]}($selector);
        $this->throwNoSuchElementException($selector, $element);

        return $element;
    }

    /**
     * @param string $query
     * @param string $text
     * @param NodeElement $parent
     *
     * @return XPath\InaccurateText
     */
    private function inaccurateText($query, $text, NodeElement $parent = null)
    {
        return (new XPath\InaccurateText("//$query", $parent ?: $this->getWorkingElement()))->text($text);
    }

    /**
     * @param string $selector
     *
     * @return string
     */
    private function computeSelectorType($selector)
    {
        return empty($this->getDrupalParameter('region_map')[$selector]) ? 'css' : 'region';
    }
}
