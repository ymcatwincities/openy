<?php

namespace Drupal\jquery_colorpicker\Service;

interface JQueryColorpickerServiceInterface
{
	/**
	 * Formats colors into a consistent format for database storage. Turns
	 * non-scalar values into an empty string. Removes the leading # (hash)
	 * from the given value if one exists at the start of the string.
	 *
	 * @param mixed $color
	 *   The value to be formatted
	 *
	 * @return string
	 *   The formatted string.
	 */
	public function formatColor($color);

	/**
	 * Validates a given string according to the following rules:
	 * - Length is six characters
	 * - Value is hexidecimal
	 *
	 * @param string $color
	 *   The color string to be validated.
	 *
	 * @return bool|\Drupal\Core\StringTranslation\TranslatableMarkup
	 *   FALSE if there are no errors, or a TranslateabeMarkup object
	 *   containing the error message if there are any errors.
	 */
	public function validateColor($color);
}
