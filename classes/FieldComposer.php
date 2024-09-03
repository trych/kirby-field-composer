<?php
// field-composer/classes/FieldComposer.php

namespace trych\FieldComposer;

use Kirby\Content\Field;

class FieldComposer
{

  public static function compose(...$args): Field {
    $separator = option('trych.field-composer.defaultSeparator', '');
    $fields = $args;

    if (is_string(end($fields))) {
      $separator = array_pop($fields);
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
      fn($value) => $value !== null && $value !== '' && $value !== []
    ));

    return new Field(null, '', implode($separator, $fieldValues));
  }

}
