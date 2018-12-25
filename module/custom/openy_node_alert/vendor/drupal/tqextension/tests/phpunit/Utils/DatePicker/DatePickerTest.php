<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils\DatePicker;

use Behat\Mink\Session;
use Behat\Mink\Element\NodeElement;
use Drupal\TqExtension\Utils\DatePicker\DatePicker;
use Drupal\TqExtension\Utils\DatePicker\Native;
use Drupal\TqExtension\Utils\DatePicker\JQuery;
use Drupal\TqExtension\Context\TqContext;

/**
 * Class DatePickerTest.
 *
 * @package Drupal\Tests\TqExtension\Utils\Database
 */
class DatePickerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TqContext
     */
    protected $tqContext;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this
            ->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tqContext = $this
            ->getMockBuilder(TqContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tqContext
            ->method('getSession')
            ->willReturn($this->session);
    }

    /**
     * @see DatePicker::__construct()
     *
     * @param string $selector
     *   CSS selector.
     * @param string $xpath
     *   XPath representation of CSS selector.
     * @param string $date
     *   Parsable date for "strtotime()".
     * @param string $dateFormat
     *   Date format for "date()".
     * @param string $datePickerClass
     *   Implementation which expects to be used.
     * @param bool $isAvailable
     *   Whether datepicker available.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatePicker
     */
    protected function getDatePickerMock($selector, $xpath, $date, $dateFormat, $datePickerClass, $isAvailable = true)
    {
        $useNative = Native::class === $datePickerClass;

        $this->session
            ->expects(static::atLeastOnce())
            ->method('evaluateScript')
            ->withConsecutive(['jQuery.fn.datepicker'], ['Modernizr && Modernizr.inputtypes.date'])
            ->willReturnOnConsecutiveCalls($isAvailable, $useNative);

        $element = $this
            ->getMockBuilder(NodeElement::class)
            ->setConstructorArgs([$xpath, $this->session])
            ->getMock();

        $this->tqContext
            ->method('element')
            ->with('*', $selector)
            ->willReturn($element);

        if ($useNative && $isAvailable) {
            $this->tqContext
                ->expects(static::once())
                ->method('executeJsOnElement')
                ->with($element, "return jQuery({{ELEMENT}}).data('drupalDateFormat')")
                ->willReturn($dateFormat);
        }

        return $this
            ->getMockBuilder(DatePicker::class)
            ->setConstructorArgs([$this->tqContext, $selector, $date])
            ->getMock();
    }

    /**
     * {@inheritdoc}
     *
     * @dataProvider providerDatePickerConstructor
     */
    public function testDatePickerConstructor($format, $class, $isAvailable = true, \Exception $exception = null)
    {
        if (null !== $exception) {
            // Use deprecated method to be compatible with PHPUnit 4 running on PHP 5.5.
            $this->setExpectedException(get_class($exception), $exception->getMessage());
        }

        $this->assertAttributeInstanceOf($class, 'datePicker', $this->getDatePickerMock(
            '.test',
            '//*[@class = ".test"]',
            '13 October, 2022',
            $format,
            $class,
            $isAvailable
        ));
    }

    public function providerDatePickerConstructor()
    {
        return [
            ['', Native::class, null, new \RuntimeException('jQuery DatePicker is not available on the page.')],
            // Drupal 8 native datepicker. Cannot determine date format.
            ['', Native::class, true, new \RuntimeException('Unknown date format.')],
            // Drupal 8 native datepicker. Date format stored in "data-drupalDateFormat" property.
            ['Y-m-d', Native::class],
            // Drupal 7 & 8 jQuery datepicker.
            [null, JQuery::class],
        ];
    }
}
