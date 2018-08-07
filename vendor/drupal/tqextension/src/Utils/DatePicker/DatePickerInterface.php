<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\DatePicker;

interface DatePickerInterface
{
    /**
     * @throws \Exception
     *   When date is not available for selection.
     *
     * @return static
     */
    public function isDateAvailable();

    /**
     * @return static
     */
    public function setDate();

    /**
     * @throws \Exception
     *   When date is not selected.
     *
     * @return static
     */
    public function isDateSelected();
}
