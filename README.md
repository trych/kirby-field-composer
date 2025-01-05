# Kirby Field Composer

Kirby Field Composer is a plugin that simplifies complex field operations in Kirby. It provides methods for merging fields, applying conditional logic, and manipulating strings, handling field values intelligently to avoid unwanted formatting issues. This makes it easier to work with both simple and complex content structures.

## Features

- 🧪 **Field Methods**: A collection of methods to manipulate and compose field values.
- 🌐 **Global Helper Functions**: `field()` and `f()` for easy field composition.
- 🧬 **Flexible Merging**: Combining multiple fields with custom separators and positioning.
- 🏷️ **Smart Handling of Empty Fields:** No separators get inserted when fields are empty.
- 🚦 **Conditional Field Handling**: Apply conditions to field rendering.
- 📋 **List Methods**: Format fields to lists with powerful processing options
- 🔡 **String Manipulation**: Apply Kirby's `Str` class methods directly to fields.
- 🔍 **Debugging Tools**: Methods for logging and debugging complex field method chains.

## Overview
Simple use cases include merging multiple fields to a single field’s value …
```php
$page->title()->merge($page->author(), $page->year());
// => Chasing Starlight, Jane Doe, 2008
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
// assuming we have two variables $artwork and $artist holding content on both
field(
  [
    $artist->name()->or('Unknown'),
    field($artist->born(), $artist->died(), '-')
      ->prefix('*', when: $artist->died()->isEmpty()),
    $artist->birthplace()
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

# Field Methods

Each of the plugin's field methods returns a field, so the methods can all be used to chain multiple field methods.

### `$field->merge(...$args)`

Merges the field's value with the given arguments. The `merge()` method is the heart of the plugin and allows for complex composing of multiple fields and strings.

- **`$args`:** one or multiple arguments (fields, strings, numbers, arrays) that will be merged to a single field's value.

In its most simple form, it can merge the value of the original field with one or more given arguments. The default separator is `, `.
```php
$page->title()->merge($page->year());
// => Haze, 2014
```

Further field methods can still be chained to the `merge()` method.
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
$page->title()->merge($page->artist(), $page->info(), $page->year());
// => Haze, Jil Nash, 2014
```

If a string is used as the last argument, it will be interpreted as the separator to place between the separate parts. Otherwise the default separator (`', '` or the one set via the `mergeSeparator` option) will be used.
```php
$page->title()->merge($page->artist(), $page->year(), ' / ');
// => Haze / Jil Nash / 2014
```

If you want to merge a string as the last argument, remember to explicitly set the separator even if it matches the default separator, otherwise the last string to merge would be interpreted as separator.
```php
// 🚫 this will use the string 'Sold' as a separator
$page->title()->merge($page->artist(), $page->year(), 'Sold');
// => HazeSoldJil NashSold2014

// ✅ pass the separator explicitly as the last argument instead
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
// => Jil Nash / 2014 / HAZE / Tate
```

If an array is provided as one of the arguments, its entries will be merged by the same rules, except that there is no original field value that is passed and therefore there is also no position option. This allows for complex merging when there are several "sub-groups" in the resulting string that might have different separators.
```php
$page->title()->upper()->merge(
  [$page->artist(), $page->year()], // arguments will be merged separated by the default by ', '
  [$page->description(), $page->info(), $page->museum(), ' | '], // arguments will be merged separated by ' | '
  'Sold',
  '; ' // separator, top level arguments will be merged separated by `; `
);
// => HAZE; Jil Nash, 2014; Faint shapes lost in mist | Tate; Sold
```

### `$field->prefix($prefix, $separator, $when)`

Adds a prefix to the field's value. If the field is empty or the condition is not met, no prefix is added. If an empty field is passed as the prefix, there will be no prefix and no separator added, so the field keeps its original value.

- **`$prefix`:** The prefix to add (can be a Field or a string).
- **`$separator`:** Optional separator between the prefix and the field value.
- **`$when`:** Optional condition that determines whether to add the prefix. Default is `true`.

```php
$page->title()->prefix('Title: ');
// => Title: Haze

$page->info()->prefix('Additional info: ');
// => [returns an empty field, as the info field is also empty]

$page->title()->prefix($page->artist(), ': ');
// => Jil Nash: Haze

$artist->born()->prefix('*', '', $artist->died()->isEmpty());
// => *1982

// if you do not like to pass redundant arguments or like to be explicit
// you can also pass named arguments
$artist->born()->prefix('*', when: $artist->died()->isEmpty());
// => *1982
```

### `$field->suffix($suffix, $separator, $when)`

Adds a suffix to the field's value. If the field is empty or the condition is not met, no suffix is added. If an empty field is passed as the suffix, there will be no suffix and no separator added, so the field keeps its original value.

- **`$suffix`:** The suffix to add (can be a Field or a string).
- **`$separator`:** Optional separator between the field value and the suffix.
- **`$when`:** Optional condition that determines whether to add the suffix. Default is `true`.

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

### `$field->wrap($before, $after, $separator, $when)`

Wraps the field's value with specified strings or field values. If the field is empty or the condition is not met, no wrapping strings will be added.

- **`$before`:** The string or field to prepend to the field's value.
- **`$after`:** The string or field to append to the field's value. If null, `$before` is used.
- **`$separator`:** An optional separator between the field value and the wrapping strings.
- **`$when`:** Optional condition that determines whether to wrap the field. Default is `true`.

```php
$page->title()->wrap('»', '«');
// => »Haze«
```

If an empty field is passed to `before` or `after`, there is no string prepended / appended and no separator inserted.
```php
$page->artist()->wrap($page->title(), $page->info(), ' | ');
// => Haze | Jil Nash
```

### `$field->tag($tag, $attr, $indent, $level, $encode, $when)`

Wraps the field's value in an HTML tag. If the field is empty or the condition is not met, no tags are added.

- **`$tag`:** The HTML tag to wrap the field's value in.
- **`$attr`:** An associative array of HTML attributes for the tag.
- **`$indent`:** The indentation string, or null for no indentation.
- **`$level`:** The indentation level. Defaults to `0`.
- **`$encode`:** If `true` (default), encodes HTML in content for security. Set to `false` for outer tags in nested tag calls to preserve inner HTML structure.
- **`$when`:** Optional condition that determines whether to wrap the field in a tag. Default is `true`.

```php
$page->title()->tag('h1');
// => <h1>Haze</h1>

$page->description()->tag('p', ['class' => 'description']);
// => <p class="description">Faint shapes lost in mist.</p>
```

When nesting multiple `tag()` calls like `$field->tag('em')->tag('p')`, the inner HTML tags will be encoded and shown as text rather than rendered as HTML. This happens because each `tag()` call encodes its content for security. To properly nest tags while maintaining security, you need to: encode user content with a regular `tag()` call (`encode: true`), and then for subsequent `tag()` calls set `encode: false` to preserve the HTML structure.

```php
// 🚫 Incorrect output: inner <em> tags will be encoded and shown as text
$page->artist()->tag('em')->tag('p');

// 🚫 Insecure: encoding is disabled on both `tag()` calls.
//              The artist field's content is not sanitized.
$page->artist()->tag('em', encode: false)
               ->tag('p', encode: false);

// ✅ Secure: First tag encodes content, outer tag preserves HTML
$page->artist()->tag('em')  // inner tag encodes user content
               ->tag('p', encode: false);  // outer tag preserves HTML

// 🚫 Insecure: Even though inner tag encodes initial content,
//              merging additional content between tag calls breaks the security chain
$page->artist()->tag('em')  // this encodes artist content
               ->merge($page->description())  // description content is raw
               ->tag('p', encode: false);  // preserves HTML, but now includes unencoded content

// ✅ Secure: As long as the tags are only wrapped around already secured content,
//            they can be chained infinitely without compromising security since the
//            initial encoding protects all user content
$page->artist()->tag('em')  // sanitizes content
               ->tag('strong', encode: false)  // preserves HTML
               ->tag('p', encode: false);  // preserves HTML
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

### `$field->match($conditions, $when)`

Similar to [PHP's match expression](https://www.php.net/manual/en/control-structures.match.php), this matches the field's value against the keys of an array of key/value pairs and returns their corresponding values if a match is found. In case no match is found, the original field is returned. Alternatively, setting `'default'` as the last key in the array provides a fallback value for unmatched cases.

- **`$conditions`:** Array of key/value pairs where the keys are matched against the field's value
- **`$when`:** Optional condition that determines whether to run the matching operation. If the condition is not met, the original field is returned unchanged. Default is `true`.

```php
// Basic matching with fallback
$page->museum()->match([
  'Tate' => 'Tate Gallery',
  'MoMA' => 'Museum of Modern Art',
  'Louvre' => 'Musée du Louvre',
  'default' => 'Unknown gallery'
]);
// => 'Tate Gallery'
```

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

### `$field->list($split, $join, $conjunction, $serial, $each, $all, $when)`

Converts a field's value into a formatted list with advanced processing options. This method can handle any field type that represents a list: strings (with custom separators), structure fields, pages fields, files fields, users fields, or blocks fields. The method provides options to format the output with custom separators and conjunctions, process individual items, and transform the entire list.

- **`$split`:** Pattern to split string value, `null` for auto-detect, `false` to force array handling (non-array fields will be treated as single item)
- **`$join`:** String to join list items. Defaults to `, ` or the user-defined `listJoinSeparator` option
- **`$conjunction`:** Optional conjunction text or callback before last item. Defaults to the no conjunction or the user-configured `listConjunction` option
- **`$serial`:** Whether to use serial (Oxford) comma before conjunction. Defaults to `false`
- **`$each`:** Optional callback to process each item
- **`$all`:** Optional callback to process the entire list array right before formatting it to a list
- **`$when`:** Optional condition that determines whether to process the field. Default is `true`

In its most basic form, it converts a comma-separated string into a formatted list:
```php
// Simple list from comma-separated string
$page->keywords()->list(',');
// => red, blue, green
```

The output format can be customized using the parameters `$join`, `$conjunction` and `$serial`. The `$join` parameter sets the separator between items in the resulting list, while `$conjunction` adds text before the last item. Setting `$serial` to `true` adds an Oxford comma before the conjunction:
```php
// Custom join separator
$page->keywords()->list(',', '|');
// => red|blue|green

// List with conjunction
$page->keywords()->list(',', null, 'or')->upper();
// => RED, BLUE OR GREEN

// List with conjunction and Oxford comma
$page->keywords()->list(',', null, 'and', true);
// => red, blue, and green
```

The method automatically handles Kirby's list-type fields like pages, files, users, blocks, and structure fields. Using the `$each` callback, you can process each item before it gets added to the list. The items are the individual collection items of the given field type. That means a pages field will be converted to page objects, so all page methods can be used in the item callback and accordingly for other collection types.
```php
// Splitting a files field and listing the file names by using a callback
$page->slideshow()->list(
  each: fn($img) => $img->filename() . ' (' . $img->dimensions() . ' px)'
);
// => photo1.jpg (720 × 640 px), photo2.webp (600 × 400 px), photo3.jpg (1280 × 720 px)

// If `false` or an empty string `''` is returned for an item, this item does not get listed
$page->slideshow()->list(
  each: fn($img) => $img->extension() === 'jpg' ? $img->filename() : false;
);
// => photo1.jpg, photo3.jpg

// List structure field values
$page->team()->list(each: fn($member) => $member->name());
// => John Doe, Jane Smith, Alex Johnson

// List block types
$page->blocks()->list(each: fn($block) => $block->type());
// => text, gallery, text, quote, image, text
```

The `$all` callback allows you to transform the entire list before it gets formatted. This is useful for sorting, filtering or removing duplicates:
```php
// Sort items alphabetically before joining
$page->tags()->list(
    each: fn($tag) => $tag->name(),
    all: fn($items) => sort($items)
);
// => art, culture, design, photography

// Outputting all types of a bocks field with unique, sorted values
$page->article()->list(
  each: fn($item) => $item->type(),
  all: fn($items) => sort(array_unique($items))
);
// => gallery, image, quote, text
```

### `$field->count($split, $each, $when)`

Counts the number of items in a field that represents a list. Works with any field type that can be interpreted as a list: structure fields, pages fields, files fields, users fields, blocks fields, or strings with a user defined separator. If an `$each` callback is provided, strings or booleans can be returned. Empty strings or `false` values are not counted in this case.

- **`$split`:** Pattern to split string value, `null` for auto-detect, `false` to force array handling (non-array fields will be treated as single item)
- **`$each`:** Optional callback to process each item before counting. Can return transformed values or booleans
- **`$when`:** Optional condition that determines whether to process the field. Default is `true`

```php
// Count items in a simple comma-separated list
$page->keywords()->count();
// => 3

// Count items in a structure field and count only the items
// that have an entry in the `street` column
$page->addresses()->count(null, fn($address) => $address->zip() );
// => 12 (number of addresses with a given zip code)

// Count images wider than 1000px in a slideshow field
$page->slideshow()->count(null, fn($file) => $file->width() > 1000 );
// => 5 (number of images wider than 1000px)
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

### `$field->dump($msg, $echo, $dumpField)`

Dumps the field's value for debugging and returns the field. This is a wrapper around Kirby's `dump()` method that maintains chainability.

- **`$msg`:** Optional debugging message that will be added to the dump output. If the message includes the placeholder `{{ val }}`, the field's value will replace it, otherwise the message will be used as prefix.
- **`$echo`:** Whether to echo the dump (`true`) or return it as the field's new value (`false`). Default is `true`.
- **`$dumpField`:** If set to `true` will dump the field itself instead of its value. Default is `false`.

```php
// Simple dump
$page->artist()->dump();

// With prefix
$page->artist()->dump('artist value: ');

// With template
$page->artist()->dump('The artist known as {{ val }}!!');

// Return dump result instead of echoing
$page->artist()->dump('Artist: ', false)->upper();
// => "ARTIST: JIL NASH"

// Dump entire field object
$page->artist()->dump('artist field: ', true, true);
```

### `$field->log($msg, $filename, $logField)`

Logs the field's value to a log file and returns the field. Creates a timestamped log entry in the `site/logs` directory. Each log entry includes a timestamp and the field's value, optionally wrapped in a custom debugging message. This method is particularly useful when debugging field operations in contexts where output cannot be displayed, such as in Kirby Panel query strings or on production servers.

- **`$msg`:** Optional debugging message for the log entry. If the message includes the placeholder `{{ val }}`, the field's value will replace it, otherwise the message will be used as prefix.
- **`$filename`:** Name of the log file without extension (default: `'field_composer'`). If the file already exists, the log entry will be appended to the file.
- **`$logField`:** If set to true will log the field itself in Kirby's `dump()` format instead of the field's value. Default is `false`.

```php
// Simple log
$page->artist()->log();
// => [2024-11-10 14:30:22] Jil Nash

// With prefix
$page->artist()->log('Artist: ');
// => [2024-11-10 14:30:22] Artist: Jil Nash

// With template
$page->artist()->log('Found artist {{ val }} in page');
// => [2024-11-10 14:30:22] Found artist Jil Nash in page

// Custom log file
$page->artist()->log('Artist: ', 'artist_logs');
// => creates/appends to site/logs/artist_logs.log

// Log entire field object
$page->artist()->log('Artist field: ', 'field_logs', true);
// => logs the full field object in dump format to site/logs/field_logs.log
```

# Helpers
The plugin provides a global helper function `field()` along with a shortcut alias `f()`.

### `field(...$args)`
The field helper allows you to compose a field from given values. This field can then be used to chain it with other field methods. The arguments work the same way as they do in the `$field->merge()` field method described above: You can pass fields, strings or numbers and they will be merged to the new field’s value.

```php
field($page->title(), $page->artist(), 'sold', ', ')->upper()
// => HAZE, JIL NASH, SOLD
```

If an array is passed, it will merge its values to a field by the same rules. If there is more than one argument and the last given argument is a string, it will be interpreted as a separator. Unlike the `$field->merge()` method, the last argument cannot be used as a position parameter as there is no initial field value that gets passed into the `field()` helper.

The field helper is especially useful if you need to compose a field where the first value is part of a „sub-group“ or if you need to chain further field methods to such a sub-group, as shown in the example below.

```php
field(
  [$page->title()->tag('em'), $page->year()],
  $page->artist(),
  field($page->width(), $page->height(), ' × ')->suffix(' cm')
    ->when($page->width(), $page->height()),
  $page->description()->prefix('Subject: '),
  $page->info()->prefix('Info: '),
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
The plugin has four options, `mergeSeparator`, `affixSeparator`, `listJoinSeparator` and `listConjunction`.

The `mergeSeparator` sets the default separator for the `$field->merge()` as well as the `field()` helper. Its default value is a comma followed by a space: `', '`.

The `affixSeparator` sets the default separator for the field methods `$field->prefix()`, `$field->suffix()` and `$field->wrap()` ("affix" being the umbrella term for "prefix" and "suffix"). Its default value is an empty string: `''`.

The `listJoinSeparator` sets the default separator between list items for the `$field->list()` method. Its default value is a comma followed by a space: `', '`.

The `listConjunction` sets the default conjunction word for the `$field->list()` method. Its default value is `null` (no conjunction). It could be set to a simple string like `'and'` or for multilingual sites it could set to a callback that returns a translated conjunction: `fn() => t('and')`.

You can change the defaults in your `config.php` file.

```php
// /site/config/config.php

return [
  'trych.field-composer' => [
    'mergeSeparator' => ' | ',
    'affixSeparator' => ' ',
    'listJoinSeparator' => '/',
    'listConjunction' => fn() => t('and')  // returns "and", "und", "et" etc. based on current language
  ]
];
```

These user-defined options can still be overridden by providing explicit parameters in the method calls:
```php
$page->title()->merge($page->artist(), $page->year(), ' / ');

$page->title()->prefix($page->artist(), ': ');

$page->keywords()->list(join: ' | ');

$page->members()->list(conjunction: '&');
```

---
## Contributing
If you encounter any issues or have suggestions, please [open an new issue](https://github.com/trych/kirby-field-composer/issues).
## License

[MIT](./LICENSE) License © 2024 [Timo Rychert](https://github.com/trych)
