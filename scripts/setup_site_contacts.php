<?php

/**
 * @file
 * One-time setup for site_contacts Config Page type, fields, and defaults.
 *
 * Usage: drush php:script scripts/setup_site_contacts.php
 */

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

if (!\Drupal::moduleHandler()->moduleExists('config_pages')) {
  throw new \RuntimeException('Enable config_pages module first.');
}

// Config page type.
if (!ConfigPagesType::load('site_contacts')) {
  ConfigPagesType::create([
    'id' => 'site_contacts',
    'label' => 'Контакти сайту',
    'menu' => [
      'path' => '/admin/config/mybg/site-contacts',
      'weight' => 0,
      'description' => 'Телефон, email та інші контакти для шапки і футера.',
    ],
    'context' => [
      'language' => FALSE,
      'theme' => FALSE,
    ],
    'token' => TRUE,
  ])->save();
  print "Created config page type: site_contacts\n";
}

$fields = [
  'field_site_phone' => [
    'type' => 'telephone',
    'label' => 'Телефон',
    'module' => 'telephone',
    'placeholder' => '+380 (XX) XXX-XX-XX',
  ],
  'field_site_email' => [
    'type' => 'email',
    'label' => 'Email',
    'module' => 'core',
    'placeholder' => 'info@example.com',
  ],
  'field_site_address' => [
    'type' => 'string',
    'label' => 'Адреса',
    'module' => 'core',
    'placeholder' => 'м. Богуслав, ...',
  ],
  'field_site_hours' => [
    'type' => 'string_long',
    'label' => 'Графік роботи',
    'module' => 'core',
    'placeholder' => 'Пн–Пт: 9:00–18:00',
  ],
  'field_site_telegram' => [
    'type' => 'string',
    'label' => 'Telegram',
    'module' => 'core',
    'placeholder' => '@username',
  ],
  'field_site_facebook' => [
    'type' => 'link',
    'label' => 'Facebook',
    'module' => 'link',
  ],
];

foreach ($fields as $field_name => $info) {
  if (!FieldStorageConfig::loadByName('config_pages', $field_name)) {
    $storage = [
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'type' => $info['type'],
      'cardinality' => 1,
    ];
    if ($info['type'] === 'link') {
      $storage['settings'] = ['link_type' => 16, 'title' => 0];
    }
    FieldStorageConfig::create($storage)->save();
    print "Created storage: $field_name\n";
  }

  if (!FieldConfig::loadByName('config_pages', 'site_contacts', $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'bundle' => 'site_contacts',
      'label' => $info['label'],
      'required' => $field_name === 'field_site_phone',
    ])->save();
    print "Created field: $field_name\n";
  }
}

// Form / view displays.
$entity_type_manager = \Drupal::entityTypeManager();
$form_display = $entity_type_manager->getStorage('entity_form_display')->load('config_pages.site_contacts.default');
if (!$form_display) {
  $entity_type_manager->getStorage('entity_form_display')->create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'site_contacts',
    'mode' => 'default',
    'status' => TRUE,
  ])->save();
}

$view_display = $entity_type_manager->getStorage('entity_view_display')->load('config_pages.site_contacts.default');
if (!$view_display) {
  $entity_type_manager->getStorage('entity_view_display')->create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'site_contacts',
    'mode' => 'default',
    'status' => TRUE,
  ])->save();
}

$form_display = $entity_type_manager->getStorage('entity_form_display')->load('config_pages.site_contacts.default');
$view_display = $entity_type_manager->getStorage('entity_view_display')->load('config_pages.site_contacts.default');

$weight = 0;
foreach (array_keys($fields) as $field_name) {
  $widget_type = match ($fields[$field_name]['type']) {
    'telephone' => 'telephone_default',
    'email' => 'email_default',
    'string_long' => 'string_textarea',
    'link' => 'link_default',
    default => 'string_textfield',
  };
  $view_type = match ($fields[$field_name]['type']) {
    'link' => 'link',
    'string_long' => 'basic_string',
    default => 'string',
  };

  $form_display->setComponent($field_name, [
    'type' => $widget_type,
    'weight' => $weight,
    'region' => 'content',
    'settings' => isset($fields[$field_name]['placeholder']) ? [
      'placeholder' => $fields[$field_name]['placeholder'],
    ] : [],
  ]);
  $view_display->setComponent($field_name, [
    'type' => $view_type,
    'weight' => $weight,
    'label' => 'inline',
    'region' => 'content',
  ]);
  $weight++;
}
$form_display->save();
$view_display->save();

// Default config page values.
$config_page = config_pages_config('site_contacts');
if (!$config_page) {
  $config_page = ConfigPages::create([
    'type' => 'site_contacts',
    'label' => 'Контакти сайту',
    'context' => serialize([]),
  ]);
}

$config_page->set('field_site_phone', '+380 (XX) XXX-XX-XX');
$config_page->set('field_site_email', 'info@mybg.local');
$config_page->set('field_site_address', 'м. Богуслав, Київська область');
$config_page->set('field_site_hours', 'Пн–Пт: 9:00–18:00');
$config_page->save();
print "Saved default contact values.\n";

print "Done. Run: drush cex\n";
