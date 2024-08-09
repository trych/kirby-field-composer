<?php
// field-composer/classes/FieldMethods.php

namespace trych\FieldComposer;

use Kirby\Content\Field;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\Html;
use Kirby\Exception\InvalidArgumentException;
use Closure;

class FieldMethods
{

  public static function merge(Field $field, Field|string|int|bool ...$args): Field
  {
    $separator = '';
    $position = 0; // Default position (beginning of the array)
    $includeCurrentField = true;
    $fields = $args;

    // Check for position or flag at the end
    // A true flag leaves the position at 0 and includes the field
    $lastArg = end($args);
    if (is_int($lastArg) || is_bool($lastArg)) {
      $positionOrFlag = array_pop($fields);
      if (is_int($positionOrFlag)) {
        $position = $positionOrFlag;
      } elseif ($lastArg === false) {
        $includeCurrentField = false;
      }
    }

    // Check if the last remaining argument is a string (separator)
    if (is_string(end($fields))) {
      $separator = array_pop($fields);
    }

    // Filter and map Field arguments to their values
    // empty fields ('' as value) are removed
    $fieldValues = array_values(array_filter(
      array_map(
        fn($arg) => $arg instanceof Field ? $arg->value() : null,
        $fields
      ),
      fn($value) => $value !== null && $value !== '' && $value !== []
    ));

    if ($includeCurrentField && $field->isNotEmpty()) {
      // Calculate the insertion index
      $insertIndex = $position >= 0 ? min($position, count($fieldValues)) : max(0, count($fieldValues) + $position + 1);

      // Insert the current field's value at the calculated position
      array_splice($fieldValues, $insertIndex, 0, [$field->value()]);
    }

    return $field->value(implode($separator, $fieldValues));
  }

  public static function prefix(Field $field, Field|string $prefix = '', ?string $separator = ''): Field
  {
    return self::addAffix($field, $prefix, $separator, true);
  }

  public static function suffix(Field $field, Field|string $suffix = '', ?string $separator = ''): Field
  {
    return self::addAffix($field, $suffix, $separator, false);
  }

  public static function when(Field $field, mixed ...$conditions): Field
  {
      foreach ($conditions as $condition) {
          if (!self::isValidCondition($condition)) {
              return $field->value('');
          }
      }
      return $field;
  }

  public static function whenAny(Field $field, mixed ...$conditions): Field
  {
      foreach ($conditions as $condition) {
          if (self::isValidCondition($condition)) {
              return $field;
          }
      }
      return $field->value('');
  }

  public static function notWhen(Field $field, mixed ...$conditions): Field
  {
      foreach ($conditions as $condition) {
          if (!self::isValidCondition($condition)) {
              return $field;
          }
      }
      return $field->value('');
  }

  public static function notWhenAny(Field $field, mixed ...$conditions): Field
  {
    foreach ($conditions as $condition) {
      if (self::isValidCondition($condition)) {
        return $field->value('');
      }
    }
    return $field;
  }

  public static function whenNone(Field $field, mixed ...$conditions): Field
  {
      return self::notWhenAny($field, ...$conditions);
  }

  public static function whenAll(Field $field, mixed ...$conditions): Field
  {
      return self::when($field, ...$conditions);
  }

  public static function wrap(Field $field, string $before, ?string $after = null): Field
  {
      return $field->value(Str::wrap($field, $before, $after));
  }

  public static function tag(Field $field, string $tag, array $attr = [], ?string $indent = null, int $level = 0): Field
  {
      return $field->value(
          Html::tag($tag, $field->value(), $attr, $indent, $level)
      );
  }

  public static function str(Field $field, string $method, ...$args): Field
  {
      if (!method_exists(Str::class, $method)) {
        throw new InvalidArgumentException("Method '$method' does not exist in Str class.");
      }

      $result = Str::$method($field->value(), ...$args);

      $field->value = $result;
      return $field;
  }

  private static function isValidCondition($condition): bool
  {
      return !($condition instanceof Field && $condition->isEmpty()) &&
             $condition !== false &&
             $condition !== null &&
             $condition !== '' &&
             $condition !== [];
  }

  private static function addAffix(Field $field, Field|string $affix, string $separator, bool $isPrefix): Field
  {
    if ($field->isEmpty()) return $field;

    $affixValue = $affix instanceof Field ? $affix->value() : $affix;
    $mergedValue = $isPrefix
      ? $affixValue . $separator . $field->value()
      : $field->value() . $separator . $affixValue;

    return $field->value($mergedValue);
  }

  // TEMP classes for debugging

  public static function empty(Field $field): Field
  {
    return $field->value('');
  }

}
