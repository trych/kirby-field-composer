<?php
// field-composer/index.php

use trych\FieldComposer\FieldMethods;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin(
  name: 'trych/field-methods',
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
      'wrap' => [FieldMethods::class, 'wrap'],
      'tag' => [FieldMethods::class, 'tag'],
      'str' => [FieldMethods::class, 'str'],
      'empty'  => [FieldMethods::class, 'empty'],
    ],

    'options' => [
      'defaultSeparator' => ''
    ]
  ]
);

