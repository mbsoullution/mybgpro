<?php

/**
 * @file
 * Config Page: editable background images per portal section.
 *
 * Run: ddev drush php:script scripts/setup_section_backgrounds.php
 */

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

$etm = \Drupal::entityTypeManager();
$registry = \Drupal::service('mybg_matrix.section_registry');

if (!ConfigPagesType::load('section_backgrounds')) {
  ConfigPagesType::create([
    'id' => 'section_backgrounds',
    'label' => 'Фони розділів',
    'context' => [
      'show_warning' => FALSE,
      'language' => FALSE,
      'theme' => FALSE,
    ],
    'menu' => [
      'path' => '/admin/config/mybg/section-backgrounds',
      'weight' => 2,
      'description' => 'Фонові зображення для сторінок розділів порталу.',
    ],
  ])->save();
  print "Created config page type: section_backgrounds\n";
}

$labels = [
  'news' => 'Новини',
  'listings' => 'Оголошення',
  'jobs' => 'Робота',
  'business' => 'Бізнес',
  'events' => 'Події',
  'community' => 'Спільнота',
  'city' => 'Місто',
  'map' => 'Карта',
];

$weight = 0;
foreach ($registry->getAllSectionsById() as $section_id => $section) {
  $field_name = 'field_bg_' . str_replace('-', '_', $section_id);
  $label = $labels[$section_id] ?? ($section['label'] ?? $section_id);

  if (!FieldStorageConfig::loadByName('config_pages', $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'type' => 'image',
      'settings' => [
        'target_type' => 'file',
        'uri_scheme' => 'public',
      ],
      'cardinality' => 1,
    ])->save();
  }

  if (!FieldConfig::loadByName('config_pages', 'section_backgrounds', $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'bundle' => 'section_backgrounds',
      'label' => 'Фон: ' . $label,
      'settings' => [
        'file_directory' => 'sections/backgrounds',
        'file_extensions' => 'png gif jpg jpeg webp',
      ],
    ])->save();
    print "Created field: $field_name\n";
  }

  $weight++;
}

$existing = $etm->getStorage('config_pages')->loadByProperties(['type' => 'section_backgrounds']);
if (!$existing) {
  ConfigPages::create([
    'type' => 'section_backgrounds',
    'context' => 'a:0:{}',
  ])->save();
  print "Created section_backgrounds config entity\n";
}

$form_display = EntityFormDisplay::load('config_pages.section_backgrounds.default')
  ?? EntityFormDisplay::create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'section_backgrounds',
    'mode' => 'default',
    'status' => TRUE,
  ]);

$weight = 0;
foreach ($registry->getAllSectionsById() as $section_id => $section) {
  $field_name = 'field_bg_' . str_replace('-', '_', $section_id);
  $form_display->setComponent($field_name, [
    'type' => 'image_image',
    'weight' => $weight++,
    'region' => 'content',
    'settings' => [
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'thumbnail',
    ],
  ]);
}
$form_display->save();

$view_display = EntityViewDisplay::load('config_pages.section_backgrounds.default')
  ?? EntityViewDisplay::create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'section_backgrounds',
    'mode' => 'default',
    'status' => TRUE,
  ]);
foreach ($registry->getAllSectionsById() as $section_id => $section) {
  $field_name = 'field_bg_' . str_replace('-', '_', $section_id);
  $view_display->setComponent($field_name, [
    'type' => 'image',
    'weight' => $weight++,
    'label' => 'above',
    'region' => 'content',
  ]);
}
$view_display->save();

// Seed default images from theme assets if present.
$config_page = config_pages_config('section_backgrounds');
$theme_path = \Drupal::service('extension.list.theme')->getPath('mybg_radix');
$file_repository = \Drupal::service('file.repository');
$file_system = \Drupal::service('file_system');
$upload_dir = 'public://sections/backgrounds';
$file_system->prepareDirectory($upload_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

foreach ($registry->getAllSectionsById() as $section_id => $section) {
  $field_name = 'field_bg_' . str_replace('-', '_', $section_id);
  if (!$config_page->get($field_name)->isEmpty()) {
    continue;
  }
  $relative = $section['poster'] ?? 'hero-poster.jpg';
  $source = DRUPAL_ROOT . '/' . $theme_path . '/assets/branding/' . $relative;
  if (!is_readable($source)) {
    $source = DRUPAL_ROOT . '/' . $theme_path . '/assets/branding/hero-poster.jpg';
  }
  if (!is_readable($source)) {
    continue;
  }
  $data = file_get_contents($source);
  $filename = basename($relative);
  $file = $file_repository->writeData($data, 'public://sections/backgrounds/' . $section_id . '-' . $filename);
  $config_page->set($field_name, ['target_id' => $file->id()]);
  print "Seeded default image for $section_id\n";
}
$config_page->save();

foreach (['editor', 'moderator'] as $role_id) {
  $role = \Drupal\user\Entity\Role::load($role_id);
  if ($role) {
    $role->grantPermission('edit config_pages entity');
    $role->grantPermission('view config_pages entity');
    $role->save();
  }
}

print "Done. Edit at /admin/config/mybg/section-backgrounds\n";
