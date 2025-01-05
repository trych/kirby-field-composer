<?php

use Kirby\Content\Field;
use trych\FieldComposer\FieldMethods;
use trych\FieldComposer\FieldComposer;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin(
  name: 'trych/field-composer',
  extends: [
    'fieldMethods' => [
      'merge'  => [FieldMethods::class, 'merge'],
      'format'  => [FieldMethods::class, 'format'],
      'prefix' => [FieldMethods::class, 'prefix'],
      'suffix' => [FieldMethods::class, 'suffix'],
      'when' => [FieldMethods::class, 'when'],
      'whenAny' => [FieldMethods::class, 'whenAny'],
      'notWhen'  => [FieldMethods::class, 'notWhen'],
      'notWhenAny' => [FieldMethods::class, 'notWhenAny'],
      'whenAll' => [FieldMethods::class, 'whenAll'],
      'whenNone' => [FieldMethods::class, 'whenNone'],
      'match' => [FieldMethods::class, 'match'],
      'wrap' => [FieldMethods::class, 'wrap'],
      'tag' => [FieldMethods::class, 'tag'],
      'list' => [FieldMethods::class, 'list'],
      'count' => [FieldMethods::class, 'count'],
      'str' => [FieldMethods::class, 'str'],
      'dump' => [FieldMethods::class, 'dump'],
      'log'  => [FieldMethods::class, 'log'],
    ],

    'options' => [
      'mergeSeparator' => ', ',
      'affixSeparator' => '',
      'listJoinSeparator' => ', ',
      'listConjunction' => null
    ],

    'hooks' => [
      'system.loadPlugins:after' => function () {
        // register field helper functions

        if (Helpers::hasOverride('field') === false && !function_exists('field')) {
          function field(...$args): Field {
            return FieldComposer::compose(...$args);
          }
        }

        if (Helpers::hasOverride('f') === false && !function_exists('f')) {
          function f(...$args): Field {
            return FieldComposer::compose(...$args);
          }
        }
      }
    ]
  ]
);
