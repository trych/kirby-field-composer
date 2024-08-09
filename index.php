<?php
// field-composer/index.php

use trych\FieldComposer\FieldMethods;

F::loadClasses([
  'trych\\FieldComposer\\FieldMethods' => 'classes/FieldMethods.php'
], __DIR__);

Kirby::plugin(
  name: 'trych/field-methods',
  extends: [
    'fieldMethods' => [
      'merge'  => [FieldMethods::class, 'merge'],
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

