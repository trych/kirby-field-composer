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

  /**
   * Merges the current field with additional fields or values, with options for positioning and separation.
   *
   * This method allows for complex merging operations, including:
   * - Merging multiple fields and/or scalar values
   * - Specifying a separator between merged values
   * - Controlling the position of the current field in the merged result
   * - Optionally excluding the current field from the merge
   *
   * @param Field|string|int|bool ...$args Variable arguments which can include:
   *        - Field objects or scalar values to merge
   *        - A string separator (if provided, must be the second-to-last argument)
   *        - An integer position or boolean flag (if provided, must be the last argument)
   *          - Integer: Specifies the position to insert the current field (0-based index)
   *          - Boolean false: Excludes the current field from the merge
   *
   * @return Field The field with the merged value
   *
   * @example
   * // Merge fields with a separator
   * merge($field, $field2, $field3, ', ')
   *
   * // Merge fields and insert current field at position 1
   * merge($field, $field2, $field3, 1)
   *
   * // Merge fields with a separator, excluding the current field
   * merge($field, $field2, $field3, ', ', false)
   *
   * // Merge fields with a separator, inserting current field at position -1 (second to last)
   * merge($field, $field2, $field3, ', ', -1)
   *
   * Note: Empty fields (with value '' or []) are automatically excluded from the merge.
   */
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

  /**
   * Applies a custom formatting function to the field's value.
   *
   * This method allows for flexible, user-defined transformations of the field's value
   * using a closure. The closure receives the field's current value and the field object itself,
   * allowing for complex transformations based on both the value and other field properties.
   *
   * @param Closure $callback A closure that takes the field's value and the field object as arguments,
   *                          and returns the new formatted value
   *
   * @return Field The field with its value transformed by the callback
   */
  public static function format(Field $field, Closure $callback): Field
  {
    return $field->value($callback($field->value(), $field));
  }

  /**
   * Adds a prefix to the field's value. If the field is empty, no prefix is added.
   *
   * @param Field|string $prefix The prefix to add
   * @param string|null $separator An optional separator between the prefix and the field value
   *
   * @return Field The modified field
   */
  public static function prefix(Field $field, Field|string $prefix = '', ?string $separator = ''): Field
  {
    return self::addAffix($field, $prefix, $separator, true);
  }

  /**
   * Adds a suffix to the field's value. If the field is empty, no suffix is added.
   *
   * @param Field|string $suffix The suffix to add
   * @param string|null $separator An optional separator between the field value and the suffix
   *
   * @return Field The modified field
   */
  public static function suffix(Field $field, Field|string $suffix = '', ?string $separator = ''): Field
  {
    return self::addAffix($field, $suffix, $separator, false);
  }

  /**
   * Returns the field if all conditions are valid, otherwise returns an empty field.
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field The original field if all conditions are valid, otherwise an empty field
   */
  public static function when(Field $field, mixed ...$conditions): Field
  {
    foreach ($conditions as $condition) {
      if (!self::isValidCondition($condition)) {
        return $field->value('');
      }
    }
    return $field;
  }

  /**
   * Returns the field if any condition is valid, otherwise returns an empty field.
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field The original field if any condition is valid, otherwise an empty field
   */
  public static function whenAny(Field $field, mixed ...$conditions): Field
  {
    foreach ($conditions as $condition) {
      if (self::isValidCondition($condition)) {
        return $field;
      }
    }
    return $field->value('');
  }

  /**
   * Returns an empty field if all conditions are valid, otherwise returns the original field.
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field An empty field if all conditions are valid, otherwise the original field
   */
  public static function notWhen(Field $field, mixed ...$conditions): Field
  {
    foreach ($conditions as $condition) {
      if (!self::isValidCondition($condition)) {
        return $field;
      }
    }
    return $field->value('');
  }

  /**
   * Returns an empty field if any of the conditions are valid, otherwise returns the original field.
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field An empty field if any of the conditions is valid, otherwise the original field
   */
  public static function notWhenAny(Field $field, mixed ...$conditions): Field
  {
    foreach ($conditions as $condition) {
      if (self::isValidCondition($condition)) {
        return $field->value('');
      }
    }
    return $field;
  }

  /**
   * Alias for when(). Returns the field if all conditions are valid.
   *
   * @see self::when()
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field The original field if all conditions are valid, otherwise an empty field
   */
  public static function whenAll(Field $field, mixed ...$conditions): Field
  {
    return self::when($field, ...$conditions);
  }

  /**
   * Alias for notWhenAny(). Returns the field if all conditions are invalid.
   *
   * @see self::notWhenAny()
   *
   * @param mixed ...$conditions Variable number of conditions to check
   *
   * @return Field The original field if all conditions are invalid, otherwise an empty field
   */
  public static function whenNone(Field $field, mixed ...$conditions): Field
  {
    return self::notWhenAny($field, ...$conditions);
  }

  /**
   * Wraps the field's value with specified strings.
   *
   * @param string $before The string to prepend to the field's value
   * @param string|null $after The string to append to the field's value. If null, $before is used
   *
   * @return Field The modified field with wrapped value
   */
  public static function wrap(Field $field, string $before, ?string $after = null): Field
  {
    return $field->value(Str::wrap($field, $before, $after));
  }

  /**
   * Wraps the field's value in an HTML tag.
   *
   * @param string $tag The HTML tag to wrap the field's value in
   * @param array $attr An associative array of HTML attributes for the tag
   * @param string|null $indent The indentation string, or null for no indentation
   * @param int $level The indentation level
   *
   * @return Field The modified field with its value wrapped in the specified HTML tag
   */
  public static function tag(Field $field, string $tag, array $attr = [], ?string $indent = null, int $level = 0): Field
  {
    return $field->value(
      Html::tag($tag, $field->value(), $attr, $indent, $level)
    );
  }

  /**
   * Applies a switch-like logic to the field based on given conditions.
   *
   * @param array $cases An associative array of condition => action pairs
   * @param mixed $fallback The fallback value if no conditions are met. If no fallback value is given and no conditions are met, the original field will be returned
   *
   * @return Field The modified field based on the first true condition, or the fallback value
   */
  public static function switch(Field $field, array $cases, $fallback = null): Field
  {
    foreach ($cases as $condition => $action) {
      if ($condition) {
        if (is_callable($action)) {
          return $field->value($action($field));
        }
        return $field->value($action);
      }
    }
    return $fallback !== null ? $field->value($fallback) : $field;
  }

  /**
   * Applies a Str class method to the field's value.
   *
   * @param string $method The name of the Str class method to apply
   * @param mixed ...$args Additional arguments to pass to the Str method
   *
   * @return Field The modified field with the Str method applied to its value
   *
   * @throws InvalidArgumentException If the specified method does not exist in the Str class
   */
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
