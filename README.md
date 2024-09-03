# Kirby Field Composer

Kirby Field Composer is a plugin that simplifies complex field operations in Kirby. It provides methods for merging fields, applying conditional logic, and manipulating strings, handling field values intelligently to avoid unwanted formatting issues. This makes it easier to work with both simple and complex content structures.

## Features

- 🧪 **Field Methods**: A collection of methods to manipulate and compose field values.
- 🌐 **Global Helper Functions**: `field()` and `f()` for easy field composition.
- 🧬 **Flexible Merging**: Combining multiple fields with custom separators and positioning.
- 🏷️ **Smart Handling of Empty Fields:** No separators get inserted when fields are empty.
- 🚦 **Conditional Field Handling**: Apply conditions to field rendering.
- 🔡 **String Manipulation**: Apply Kirby's `Str` class methods directly to fields.

## Overview
Simple use cases include merging multiple fields to a single field’s value …
```php
$page->title()->merge($page->author(), $page->year(), ', ');
// => Secret Diaries, Jane Doe, 2008
```

… conditionally prefixing fields with certain values …
```php
$page->publisher()->prefix('Publisher: ');
// => Publisher: Kirby Press
```

… or many more. But to understand how the plugin can become really useful, it’s helpful to look at a complex example: Let’s say on our website we have to display digital museum labels for a collection of paintings. We might need to compose a string after a schema like this:

**{Artist Name}, {Year of Birth}-{Year of Death}, {Birth Place}; {Title of the Artwork}, {Year of Creation}; {Material} ({Width} × {Height} cm); {Collection}; {Description}**

At first this might seem straight forward, but it can quickly become complex when you get into the specifics: there are sub-groups separated by semi-colons, while the sub-group entries themselves are separated by commas, mostly. When data is missing, it should not leave an abandoned separator in place, if the width is not given, the height should not display either, if the title is empty, it should be replaced by *Untitled*, if the artist is still alive, there should be a `*` before their year of birth, and so on.

Usually this would require a lot of fiddling with conditional statements, implode commands etc.

The plugin offers methods to make this process significantly simpler. Here is how the code could look, making use of some of the plugin’s field methods:

```php
// assuming we have a variable $artwork holding infos on the artwork
// and assuming our default separator (see below) is set to ', '
field(
  [
    $artwork->artist()->or('Unknown'),
    field($artwork->born(), $artwork->died(), '-')
      ->prefix(field('*')->whenNot($artwork->died())),
    $artwork->artistorigin()
  ],
  [
    $artwork->title()->or('Untitled'),
    $artwork->year()
  ],
  [
    $artwork->material(),
    $artwork->width()->merge($artwork->height(), ' × ')
      ->when($artwork->width(), $artwork->height())
      ->suffix(' cm')
      ->wrap('(', ')'),
    ''
  ],
  $artwork->collection(),
  $artwork->description(),
  '; '
);
```

The result might look something like this:

**Edward McDoe, 1856-1936, Scotland; Solitude, 1881; Oil on canvas (56 × 82 cm); Summerfield Collection; An impressionistic depiction of a lone farmer in the fields.**

As this setup will flexibly handle empty fields, for another content file, where the artwork’s title, the dimensions and the collection are missing and the artist is still alive, it might result in something like this instead:

**Jil Nash ,\*1982, Ireland; Untitled, 1994; Acrylic on wood; An abstract color explosion.**

Additionally we could wrap fields into tags for styling, change fields conditionally etc. See below for a detailed list of available field methods.

## Installation

### Download

Download and copy this repository to `/site/plugins/field-composer`.

### Git submodule

```
git submodule add https://github.com/trych/kirby-field-composer.git site/plugins/field-composer
```

### Composer

```
composer require trych/kirby-field-composer
```


## Usage

When looking at the field methods, let's assume we have a page describing a painting with this content:
```
Title: Haze
----
Artist: Jil Nash
----
Year: 2014
----
Width: 48.2
----
Height: 67
----
Depth:
----
Description: Faint shapes lost in mist.
----
Info:
----
Museum: Tate
```

Let's also assume we have set the plugin's default separator to be a comma followed by a space:
```php
// /site/config/config.php

return [
  'trych.field-composer' => [
    'defaultSeparator' => ', '
  ]
];
```
# Field Methods

Each of the plugin's field methods returns a field, so the methods can all be used to chain multiple field methods.

### `$field->merge(...$args)`

Merges the field's value with the given arguments. The `merge()` method is the heart of the plugin and allows for complex composing of multiple fields and strings.

- **`$args`:** one or multiple arguments (fields, strings, numbers, arrays) that will be merged to a single field's value.

In its most simple form, it can merge the value of the original field with one or more given arguments.
```php
$page->title()->merge($page->year());
// => Haze, 2014
```

Further field methods can still be chained to the merge method.
```php
$page->title()->merge($page->artist())->upper();
// => HAZE, JIL NASH
```

Strings can be merged as well.
```php
$page->title()->merge($page->artist(), 'Oil on canvas', $page->year());
// => Haze, Jil Nash, Oil on canvas, 2014
```

Empty fields will simply be omitted, without introducing duplicate separators.
```php
$page->title()->merge($page->artist(), $page->info(), $page->year())
// => Haze, Jil Nash, 2014
```

If a string is used as the last argument, it will be interpreted as the separator to place between the separate parts. Otherwise the default separator (`''` or the one set via the `defaultSeparator` option) will be used.
```php
$page->title()->merge($page->artist(), $page->year(), ' / ');
// => Haze / Jil Nash / 2014
```

If you want to merge a string as the last argument, remember to explicitly set the separator even if it matches the default separator, otherwise the last string to merge would be interpreted as separator.
```php
// 🚫 this will use the string `Sold` as a separator
$page->title()->merge($page->artist(), $page->year(), 'Sold');
// => HazeSoldJil NashSold2014

// ✅ provide the separator explicitly as the last argument instead
$page->title()->merge($page->artist(), $page->year(), 'Sold', ', ');
// => Haze, Jil Nash, 2014, Sold
```

If the original field's value should not be merged at the first position, an integer can be used as the last argument to specify the position at which the value should merge.
```php
$page->title()->upper()->merge($page->year(), $page->artist(), $page->museum(), 2);
// => 2014, Jil Nash, HAZE, Tate
```

Negative integers can be used as well, to count from the back of the list.
```php
$page->title()->upper()->merge($page->year(), $page->artist(), $page->museum(), -1);
// => 2014, Jil Nash, Tate, HAZE
```

If the last argument is set to `false`, the original value will not be merged at all, instead only the given arguments will be merged. This can be useful in more complex scenarios where the original value is part of a „sub-group“ within the string (see the `merge()` method’s use with arrays below).
```php
$page->title()->merge($page->year(), $page->artist(), $page->museum(), false);
// => 2014, Jil Nash, Tate
```

If the last argument is used to specify the position, the separator string can be provided as the *second to last* argument.
```php
$page->title()->upper()->merge($page->artist(), $page->year(), $page->museum(), ' / ', 2);
// => 2014 / Jil Nash / HAZE / Tate
```

If an array is provided as one of the arguments, its entries will be merged by the same rules, except that there is no original field value that is passed and therefore there is also no position option. This allows for complex merging when there are several "sub-groups" in the resulting string that might have different separators.
```php
$page->title()->upper()->merge(
  [$page->artist(), $page->year()], // arguments will be merged separated by the default by `, `
  [$page->description(), $page->info(), $page->museum(), '|'], // arguments will be merged separated by `|`
  'Sold',
  '; ' // separator, top level arguments will be merged separated by `; `
);
// => HAZE; Jil Nash, 2014; Faint shapes lost in mist | Tate; Sold
```

### `$field->prefix($prefix, $separator)`

Adds a prefix to the field's value. If the field is empty, no prefix is added, so the field stays empty. If an empty field is passed as the prefix, there will be no prefix and no separator added, so the field keeps its original value.

- **`$prefix`:** The prefix to add (can be a Field or a string).
- **`$separator`:** Optional separator between the prefix and the field value.

```php
$page->title()->prefix('Title: ');
// => Title: Haze

$page->info()->prefix('Additional info: ');
// => [returns an empty field, as the info field is also empty]

$page->title()->prefix($page->artist(), ': ');
// => Jil Nash: Haze
```

### `$field->suffix($suffix, $separator)`

Adds a suffix to the field's value. If the field is empty, no suffix is added, so the field stays empty.  If an empty field is passed as the suffix, there will be no suffix and no separator added, so the field keeps its original value.

- **`$suffix`:** The suffix to add (can be a Field or a string).
- **`$separator`:** Optional separator between the field value and the suffix.

```php
$page->width()->suffix(' cm');
// => 48.2 cm
```

```php
$page->width()->merge($page->height(), $page->depth(), ' × ')
  ->prefix('Dimensions: ')
  ->suffix(' cm');
// => Dimensions: 48.2 × 67 cm
```
In the above example, if all of the fields `width`, `height`, `depth` were empty, the `merge` would result in an empty field and neither the `prefix` nor the `suffix` values would be applied.

### `$field->wrap($before, $after, $separator)`

Wraps the field's value with specified strings or field values. If the field is empty, no wrapping strings will be added, so the field stays empty.

- **`$before`:** The string or field to prepend to the field's value.
- **`$after`:** The string or field to append to the field's value. If null, `$before` is used.
- **`$separator`:** An optional separator between the field value and the wrapping strings.

```php
$page->title()->wrap('<<', '>>', '');
// => <<Haze>>
```

If an empty field is passed to `before` or `after`, there is no string prepended / appended and no separator inserted.
```php
$page->artist()->wrap($page->title(), $page->info(), ' | ');
// => Haze | Jil Nash
```

### `$field->tag($tag, $attr, $indent, $level)`

Wraps the field's value in an HTML tag. If the field is empty, no tags are added, so the field stays empty.

- **`$tag`:** The HTML tag to wrap the field's value in.
- **`$attr`:** An associative array of HTML attributes for the tag.
- **`$indent`:** The indentation string, or null for no indentation.
- **`$level`:** The indentation level. Defaults to `0`.

```php
$page->title()->tag('h1');
// => <h1>Haze</h1>

$page->description()->tag('p', ['class' => 'description']);
// => <p class="description">Faint shapes lost in mist.</p>
```

### `$field->when(...$conditions)`

Returns the original field if all conditions are valid, otherwise returns an empty field. If a field is passed as one of the conditions, it evaluates to `false` in case it is empty.

- **`$conditions`:** Variable number of conditions to check.

```php
// just pass the dimensions, if both the `width` and the `height` are given
$page->width()->merge($page->height(), ' × ')->suffix(' cm')
  ->when($page->width(), $page->height());
// => 48.2 × 67 cm
```

### `$field->whenAny(...$conditions)`

Returns the original field if any of the conditions is valid, otherwise returns an empty field. If a field is passed as one of the conditions, it evaluates to `false` in case it is empty.

- **`$conditions`:** Variable number of conditions to check.

```php
// just pass the museum, if either `artist` or `info` are given
$page->museum()->prefix('Gallery: ', '')->whenAny($page->artist(), $page->info());
// => Gallery: Tate
```

### `$field->notWhen(...$conditions)`

Returns an empty field if all conditions are valid, otherwise returns the original field. If a field is passed as one of the conditions, it evaluates to `false` in case it is empty.

- **`$conditions`:** Variable number of conditions to check.

```php
// shows the `description` only if `info` is empty
$page->description()->notWhen($page->info());
// => Faint shapes lost in mist.
```

### `$field->notWhenAny(...$conditions)`

Returns an empty field if any of the conditions are valid, otherwise returns the original field. If a field is passed as one of the conditions, it evaluates to `false` in case it is empty.

- **`$conditions`:** Variable number of conditions to check.

```php
// do not pass museum if either `artist` or `info` are given
$page->museum()->notWhenAny($page->artist(), $page->info());
// => [empty, as `artist` is given]
```

### `$field->whenAll(...$conditions)`

Alias for `when()`. Returns the field if all conditions are valid.

### `$field->whenNone(...$conditions)`

Alias for `notWhenAny()`. Returns the field if none of the conditions are valid.

### `$field->format($callback)`

Applies a custom formatting function to the field's value.

This is very similar to [Kirby’s native $field->callback\(\) method](https://getkirby.com/docs/reference/templates/field-methods/callback), except that for convenience the field’s value is used as the first parameter of the callback function (with the field itself being the second one) and only a string needs to be returned, the re-wrapping into a field happens automatically. Returning the field with the new value directly will also work, though.

- **`$callback`:** A closure that takes the field's value and the field object as arguments, and returns the new formatted value. The value will be automatically wrapped in a field again.

```php
// remove all vowels from a string
$page->description()->format(function($value) {
  return preg_replace('/[aeiou]/i', '', $value);
});
// => Fnt shps lst n mst.
```

### `$field->str($method, ...$args)`

Applies a [Kirby Str class method](https://getkirby.com/docs/reference/objects/toolkit/str) to the field's value.

- **`$method`:** The name of the `Str` class method to apply.
- **`$args`:** Additional arguments to pass to the Str method.

```php
// Change the field's value to camel case
$page->artist()->str('camel');
// => jilNash

// Adds -1 to the field's value or increments the ending number to allow -2, -3, etc.
$page->title()->lower()->str('increment');
// => haze-1
```

# Helpers
The plugin provides a global helper function `field()` along with a shortcut alias `f()`.

### `field(...$args)`
The field helper allows you to compose a field from given values. This field can then be used to chain it with other field methods. The arguments work the same way as they do in the `$field->merge()` field method described above: You can pass fields, strings, numbers and they will be merged to the new field’s value.

```php
field($page->title(), $page->artist(), 'sold', ', ')->upper()
// => HAZE, JIL NASH, SOLD
```

If an array is passed, it will merge its values to a field by the same rules. If the last given argument is a string, it will be interpreted as a separator. Unlike the `$field->merge()` method, the last argument cannot be used as a position parameter as there is no initial field value that gets passed into the `field()` helper.

The field helper is especially useful if you need to compose a field where the first value is part of a „sub-group“ or if you need to chain further field methods to such a sub-group, as shown in the example below.

```php
field(
  [$page->title()->tag('em'), $page->year()],
  $page->artist(),
  field($page->width(), $page->height(), ' × ')->suffix(' cm')
    ->when($page->width(), $page->height()),
  $page->description()->prefix('Subject: ', '')),
  $page->info()->prefix('Info: ', ''),
  '; ' // separator for the top level
);
// => <em>Haze</em>, 2014; Jil Nash; 48.2 × 67 cm; Subject: Faint shapes lost in mist.
```

### `f(...$args)`
Alias for `field()`.

You can disable one or both helpers by setting their respective constant in `index.php` to `false` as [described in the Kirby helper docs](https://getkirby.com/docs/reference/templates/helpers#deactivate-a-helper-globally).

```php
// /index.php

<?php

define('KIRBY_HELPER_FIELD', false);
define('KIRBY_HELPER_F', false);

require __DIR__ . '/kirby/bootstrap.php';

echo (new Kirby)->render();
```
# Options
The plugin has a single option, `defaultSeparator`, that sets the default separator for the field methods `$field->merge()`, `$field->prefix()`, `$field->suffix()` and `$field->wrap()`. Its default value is an empty string `''` .

```php
// /site/config/config.php

return [
  'trych.field-composer' => [
    'defaultSeparator' => ', '
  ]
];
```

If a separator is explicitly provided in a method call of the mentioned field methods, it will override the `defaultSeparator` for that specific operation.

---
## Contributing
If you encounter any issues or have suggestions, please [open an new issue](https://github.com/trych/kirby-field-composer/issues).
## License

[MIT](./LICENSE) License © 2024 [Timo Rychert](https://github.com/trych)
