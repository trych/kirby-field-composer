<?php
// field-composer/classes/FieldComposer.php

namespace trych\FieldComposer;

use Kirby\Content\Field;

class FieldComposer
{

  /**
   * Composes multiple fields or values into a single field.
   *
   * This method can handle various types of input, including Fields, strings,
   * integers, and arrays. It filters out empty values and joins the remaining
   * values with a separator.
   *
   * @param mixed ...$args Variable number of arguments. These can be:
   *                       - Field objects
   *                       - Strings
   *                       - Integers
   *                       - Arrays (which will be recursively composed)
   *                       - A separator string (if it's the last of multiple arguments)
   *
   * @return Field The composed field
   */
  public static function compose(mixed ...$args): Field {
    $separator = option('trych.field-composer.mergeSeparator');
    $fields = $args;

    // consider the last argument a separator if it is a string
    if (count($fields) > 1) {
      if (is_string(end($fields))) {
        $separator = array_pop($fields);
      }
    }

    $fieldValues = array_values(array_filter(
      array_map(
        function($arg) {
          if (is_array($arg)) {
            // Recursive call for arrays
            return self::compose(...$arg);
          }
          return $arg instanceof Field ? $arg->value() : $arg;
        },
        $fields
      ),
      function($value) {
        // Handle Field objects returned from recursive compose() calls
        if ($value instanceof Field) {
          return $value->isNotEmpty();
        }
        return $value !== null && $value !== '' && $value !== [];
      }
    ));

    return new Field(null, '', implode($separator, $fieldValues));
  }
}
