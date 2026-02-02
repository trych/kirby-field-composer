<?php
// field-composer/classes/FieldMethods.php

namespace trych\FieldComposer;

use Closure;
use Kirby\Content\Field;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\Html;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;
use Kirby\Exception\InvalidArgumentException;

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
   * Note: Empty fields are automatically excluded from the merge.
   *
   * @param Field|string|int|bool ...$args Variable arguments which can include:
   *        - Field objects or scalar values to merge
   *        - A string separator (if provided, must be either the last argument or the
   *          second-to-last argument if a position is provided as the last argument (see below))
   *        - An integer position or boolean flag (if provided, must be the last argument)
   *          - Integer: Specifies the position to insert the current field (0-based index)
   *          - Boolean false: Excludes the current field from the merge
   *
   * @return Field The field with the merged value
   */
  public static function merge(Field $field, Field|string|int|bool|array ...$args): Field
  {
    $separator = option('trych.field-composer.mergeSeparator');
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
    // empty fields are removed
    $fieldValues = array_values(array_filter(
      array_map(
        function($arg) use ($separator) {
          if (is_array($arg)) {
            // Recursive call to merge for arrays
            return FieldComposer::compose(...$arg);
          }
          return $arg instanceof Field ? $arg->value() : $arg;
        },
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
   * @param mixed $when A condition that determines whether to format the field; defaults to true
   *
   * @return Field The field with its value transformed by the callback
   */
  public static function format(Field $field, Closure $callback, mixed $when = true): Field
  {
    if (!self::isValidCondition($when)) return $field;

    $value = $field->value() ?? '';
    return $field->value($callback($value, $field));
  }

  /**
   * Adds a prefix to the field's value. If the field is empty, no prefix is added.
   *
   * @param Field|string $prefix The prefix to add
   * @param string|null $separator An optional separator between the prefix and the field value
   * @param mixed $when A condition that determines whether to add the prefix; defaults to true
   *
   * @return Field The modified field
   */
  public static function prefix(Field $field, Field|string $prefix = '', ?string $separator = null, mixed $when = true): Field
  {
    if ($field->isEmpty() || !self::isValidCondition($when)) return $field;

    $separator = $separator ?? option('trych.field-composer.affixSeparator');
    return self::addAffix($field, $prefix, $separator, true);
  }

  /**
   * Adds a suffix to the field's value. If the field is empty, no suffix is added.
   *
   * @param Field|string $suffix The suffix to add
   * @param string|null $separator An optional separator between the field value and the suffix
   * @param mixed $when A condition that determines whether to add the suffix; defaults to true
   *
   * @return Field The modified field
   */
  public static function suffix(Field $field, Field|string $suffix = '', ?string $separator = null, mixed $when = true): Field
  {
    if ($field->isEmpty() || !self::isValidCondition($when)) return $field;

    $separator = $separator ?? option('trych.field-composer.affixSeparator');
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
   * Matches the field's value against the keys of an array of key/value pairs and returns
   * the corresponding value of the first match.
   * If no values are matched, the original field will be returned.
   * If 'default' is set as the last key in the array, its value serves as a fallback if no
   * matches are found.
   *
   * @param array $conditions Array of key/value pairs where the keys are matched against the original
   *                          field's value and the keys value would be returned on a match.
   *                          Keys can be strings or integers but will be compared as strings.
   *                          Values can be strings or Field objects.
   *                          If 'default' is the last key, it acts as fallback for unmatched values.
   * @param mixed $when An optional condition that determines whether to use the match function the
   *                    field; defaults to `true`. If the condition is not met, the original field value
   *                    is returned unchanged.
   *
   * @return Field Returns a new field with either:
   *               - the matched condition's value
   *               - the default value (if provided as last key and no match found)
   *               - the original field value (if no match and no default)
   */
  public static function match(Field $field, array $conditions, mixed $when = true): Field
  {
    if (!self::isValidCondition($when)) {
      return $field;
    }

    $value = $field->value();
    $keys = array_keys($conditions);
    $lastKey = end($keys);

    foreach ($conditions as $condition => $result) {
      // Only treat 'default' specially if it's the last key
      if ($condition === $lastKey && $condition === 'default') {
        continue;
      }

      if ((string)$condition === $value) {
        return $field->value(
          $result instanceof Field ? $result->value() : $result
        );
      }
    }

    // If no match was found and last key is 'default', use that
    if ($lastKey === 'default') {
      return $field->value(
        $conditions['default'] instanceof Field ? $conditions['default']->value() : $conditions['default']
      );
    }

    return $field;
  }

  /**
   * Wraps the field's value with specified strings or field values. If the field is empty, no wrapping strings will be added.
   *
   * @param Field|string $before The string or field to prepend to the field's value
   * @param Field|string|null $after The string or field to append to the field's value. If null, $before is used
   * @param string|null $separator An optional separator between the field value and $before and $after
   * @param mixed $when A condition that determines whether to wrap the field; defaults to `true`
   *
   * @return Field The modified field with wrapped value, or the original field if it's empty
   */
  public static function wrap(
    Field $field,
    Field|string $before,
    Field|string|null $after = null,
    ?string $separator = null,
    mixed $when = true
  ): Field
  {
    if ($field->isEmpty() || !self::isValidCondition($when)) return $field;

    $separator = $separator ?? option('trych.field-composer.affixSeparator');
    $after = $after ?? $before;

    $prefixedField = self::addAffix($field, $before, $separator, true);
    return self::addAffix($prefixedField, $after, $separator, false);
  }

  /**
   * Wraps the field's value in an HTML tag. If the field is empty, no tags are added.
   *
   * @param string $tag The HTML tag to wrap the field's value in
   * @param array $attr An associative array of HTML attributes for the tag
   * @param string|null $indent The indentation string, or null for no indentation
   * @param int $level The indentation level
   * @param bool $encode If `true` (default), encodes HTML characters in content. Set to `false` for
   *                     outer tags in nested `tag()` calls to preserve inner HTML structure.
   * @param mixed $when A condition that determines whether to wrap the field in a tag; defaults to `true`
   *
   * @return Field The modified field with its value wrapped in the specified HTML tag
   */
  public static function tag(
    Field $field,
    string $tag,
    array $attr = [],
    ?string $indent = null,
    int $level = 0,
    bool $encode = true,
    mixed $when = true
  ): Field
  {

    if ($field->isEmpty() || !self::isValidCondition($when)) return $field;

    $content = $encode ? $field->value() : [$field->value()];

    return $field->value(
      Html::tag($tag, $content, $attr, $indent, $level)
    );
  }

  /**
   * Converts a field's value into a formatted list. Works with any field type that can be
   * interpreted as a list: structure fields, pages fields, files fields, users fields,
   * blocks fields, or strings with a user defined separator.
   *
   * @param string|bool|null $split Pattern to split string value, `null` for auto-detect,
   *                               `false` to force array handling (non-array fields will be treated as single item)
   * @param string|null $join String to join list items; defaults to configured listJoinSeparator
   * @param string|null|callable $conjunction Optional conjunction text or callback before last item;
   *                                         defaults to configured listConjunction
   * @param bool $serial Whether to use serial (Oxford) comma before conjunction; defaults to `false`
   * @param Closure|null $each Optional callback to process each item before joining
   * @param Closure|null $all Optional callback to process the entire list array right before formatting it to the list
   * @param mixed $when A condition that determines whether to process the field; defaults to `true`
   *
   * @return Field The processed field
   */
  public static function list(
      Field $field,
      string|bool|null $split = null,
      ?string $join = null,
      ?string $conjunction = null,
      bool $serial = false,
      ?Closure $each = null,
      ?Closure $all = null,
      mixed $when = true
  ): Field {
      if ($field->isEmpty() || !self::isValidCondition($when)) {
          return $field;
      }

      $join = $join ?? option('trych.field-composer.listJoinSeparator');
      $conjunction = $conjunction ?? option('trych.field-composer.listConjunction');

      $items = self::getFieldItems($field, $split, $each);

      // Apply list callback if provided
      if ($all) {
          $items = $all($items);
      }

      $count = count($items);
      if ($count <= 1) {
          return $field->value($count ? $items[0] : '');
      }

      if (!$conjunction) {
          return $field->value(implode($join, $items));
      }

      if(is_callable($conjunction)) {
          $conjunction = $conjunction();
      }

      $conjunction = ' ' . trim($conjunction) . ' ';

      if ($count === 2) {
          return $field->value($items[0] . $conjunction . $items[1]);
      }

      $last = array_pop($items);
      return $field->value(
          implode($join, $items) .
          ($serial ? $join : '') .
          $conjunction .
          $last
      );
  }

  /**
   * Counts the number of items in a field that represents a list.
   * Works with any field type that can be interpreted as a list: structure fields,
   * pages fields, files fields, users fields, blocks fields, or strings with a user
   * defined separator.
   *
   * @param string|bool|null $split Pattern to split string value, `null` for auto-detect,
   *                               `false` to force array handling (non-array fields will be treated as single item)
   * @param Closure|null $each Optional callback to process each item before counting
   * @param Closure|null $all Optional callback to process the entire list array
   * @param mixed $when A condition that determines whether to process the field; defaults to `true`
   *
   * @return Field The processed field containing the count (`0` for empty fields)
   */
  public static function count(
    Field $field,
    string|bool|null $split = null,
    ?Closure $each = null,
    ?Closure $all = null,
    mixed $when = true
  ): Field {
    if ($field->isEmpty() || !self::isValidCondition($when)) {
      return $field->value(0);
    }

    $items = self::getFieldItems($field, $split, $each);

    if ($all) {
        $items = $all($items);
    }

    return $field->value(count($items));
  }

  /**
   * Applies a Kirby Str class method to the field's value.
   *
   * This method allows you to use any of Kirby's Str:: utility methods directly on a field.
   * It provides a convenient way to perform string operations that are not covered by existing field methods.
   *
   * @param string $method The name of the Str class method to apply
   * @param mixed ...$args Additional arguments to pass to the Str method
   *
   * @return Field The modified field with the Str method applied to its value
   *
   * @throws InvalidArgumentException If the specified method does not exist in the Str class
   *
   * @example
   * // Convert the field value to camel case
   * $field->str('camel');
   *
   * // Adds -1 to the field's value or increment the ending number to allow -2, -3, etc.
   * $field->str('increment');
   *
   * @link https://getkirby.com/docs/reference/objects/toolkit/str Kirby Str method overview
   */
  public static function str(Field $field, string $method, ...$args): Field
  {
    if (!method_exists(Str::class, $method)) {
      throw new InvalidArgumentException("Method '$method' does not exist in Str class.");
    }

    $value = $field->value() ?? '';
    $result = Str::$method($value, ...$args);

    $field->value = $result;
    return $field;
  }

  /**
   * Dumps the field's value for debugging and returns the field.
   *
   * This method is a wrapper around Kirby's dump() method. It dumps the field's
   * current value and returns the field to allow for further method chaining. The
   * dumped value can be controlled via optional parameters.
   *
   * @param string $msg Optional debugging message that will be added to the dump output.
   *                    Use {{ val }} as placeholder for the value, or the string will be used as prefix
   * @param bool $echo Whether to echo the dump (true) or return it as the field's new value (false)
   * @param bool $dumpField If set to true will dump the field itself instead of its value
   * @param mixed $when A condition that determines whether to dump the output; defaults to true
   *
   * @return Field The original field when echoing the dump, otherwise the modified
   *               field with the dump output as its value
   *
   */
  public static function dump(Field $field, ?string $msg = null, bool $echo = true, bool $dumpField = false, mixed $when = true): Field
  {
    if (!self::isValidCondition($when)) return $field;

    $val = $field->value();
    $dumpVal = $dumpField ? $field : $val;

    if ($msg !== null) {
      $fieldOutput = $dumpField ? trim(print_r($field, true)) : $val;
      $formattedMsg = preg_match('/{{[ ]*val[ ]*}}/', $msg) ?
        Str::template($msg, ['val' => $fieldOutput]) :
        $msg . $fieldOutput;

      dump($formattedMsg);
    } else {
      dump($dumpVal);
    }

    if (!$echo) {
      return $field->value(dump($dumpVal, false));
    }

    return $field;
  }

  /**
   * Logs the field's value to a log file and returns the field.
   *
   * Creates a timestamped log entry in the site/logs directory. If the directory
   * doesn't exist, it will be created. Each log entry includes a timestamp and
   * the field's value, optionally wrapped in a custom message.
   *
   * @param string $msg Optional message for the log entry. Use {{ val }} as placeholder
   *                    for the value, or the string will be used as prefix
   * @param string $filename Name of the log file without extension (default: 'field_composer').
   *                         If the file already exists, the log entry will be appended to the file.
   * @param bool $logField If set to true will log the field itself in Kirby's dump() format
   *                       instead of the field's value
   * @param mixed $when A condition that determines whether to log the output; defaults to true
   *
   * @return Field The original field, allowing for method chaining
   */
  public static function log(Field $field, ?string $msg = null, string $filename = 'field_composer', bool $logField = false, mixed $when = true): Field
  {
    if (!self::isValidCondition($when)) return $field;

    $val = $logField ? trim(print_r($field, true)) : $field->value();
    if ($msg !== null) {
      $val = preg_match('/{{[ ]*val[ ]*}}/', $msg) ? Str::template($msg, ['val' => $val]) : $msg . $val;
    }

    $time = date('Y-m-d H:i:s');
    $log = "[$time] $val" . PHP_EOL;

    $dir = kirby()->root('site') . '/logs';
    if (!Dir::exists($dir)) {
      Dir::make($dir);
    }

    $filepath = $dir . '/' . $filename . '.log';
    F::write($filepath, $log, true);

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

  private static function getFieldItems(Field $field, string|bool|null $split = null, ?Closure $callback = null): array
  {
      $items = null;

      if (is_string($split)) {
          // String split parameter - explicit split
          $items = Str::split($field->value(), $split);
          if ($callback) {
              $items = array_map($callback, $items);
          }
      }
      else {
          // Otherwise try array handling first
          $type = self::arrayFieldType($field);
          if ($type) {
              $items = match ($type) {
                  'pages' => $callback ? $field->toPages()->toArray($callback) : $field->toPages()->keys(),
                  'files' => $callback ? $field->toFiles()->toArray($callback) : $field->toFiles()->keys(),
                  'users' => $callback ? $field->toUsers()->toArray($callback) : $field->toUsers()->keys(),
                  'blocks' => $field->toBlocks()->toArray($callback),
                  'structure' => $field->toStructure()->toArray($callback),
                  default => $field->toData('yaml')
              };
          }
          // If no array type found and split is false, treat as single item
          elseif ($split === false) {
              $value = $callback ? $callback($field->value()) : $field->value();
              $items = [$value];
          }
          // Otherwise fall back to default split
          else {
              $items = Str::split($field->value(), ',');
              if ($callback) {
                  $items = array_map($callback, $items);
              }
          }
      }

      // Convert any remaining sub-arrays to JSON
      $items = array_map(function($item) {
          return is_array($item) ? json_encode($item) : $item;
      }, $items);

      return array_values(array_filter($items, function ($item) {
          if($item instanceof Field) {
              return $item->toString() !== '';
          }
          if(is_bool($item)) {
              return $item === true;
          }
          return $item !== '';
      }));
  }

  private static function arrayFieldType(Field $field): string|null {
      $value = trim($field->value());
      $isYaml = Str::startsWith($value, '- ');
      $isJson = preg_match('/^\[\s*{/', $value) === 1;

      if (!$isYaml && !$isJson) {
          return null;
      }

      $fieldDef = $field->parent()?->blueprint()?->field($field->key());

      if ($fieldDef && in_array($fieldDef['type'], [
          'pages',
          'files',
          'users',
          'structure',
          'blocks',
          'layout'
      ])) {
        return $fieldDef['type'];
      }


      try {
        if ($field->toPages()->count() > 0) return 'pages';
        if ($field->toFiles()->count() > 0) return 'files';
        if ($field->toUsers()->count() > 0) return 'users';
        $data = Data::decode($value, 'yaml');
        if (is_array($data) && A::isAssociative($data[0] ?? null)) {
          return 'structure';
        }
      } catch (\Throwable $e) {
        // no structure field, continue
      }

      $blocks = $field->toBlocks();
      if ($blocks->count() > 0 && !empty($blocks->first()->type())) {
        return 'blocks';
      }

      if ($field->toLayouts()->count() > 0) return 'layout';

      return null;
  }

  private static function addAffix(Field $field, Field|string $affix, string $separator, bool $isPrefix): Field
  {
    if ($field->isEmpty()) return $field;

    $affixValue = $affix instanceof Field ? $affix->value() : $affix;

    // return original field if affix is empty to not insert an extra separator
    if (empty($affixValue)) {
      return $field;
    }

    $mergedValue = $isPrefix
      ? $affixValue . $separator . $field->value()
      : $field->value() . $separator . $affixValue;

    return $field->value($mergedValue);
  }

}
