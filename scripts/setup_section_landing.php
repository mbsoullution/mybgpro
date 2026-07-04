<?php

/**
 * @file
 * Setup section_landing content type with Layout Builder for portal sections.
 *
 * Run: ddev drush php:script scripts/setup_section_landing.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

$etm = \Drupal::entityTypeManager();
$registry = \Drupal::service('mybg_matrix.section_registry');

// Enable Layout Builder modules.
\Drupal::service('module_installer')->install(['layout_builder', 'layout_discovery'], TRUE);
print "Enabled layout_builder\n";

// Content type: section_landing — landing page for a portal section (Layout Builder).
if (!NodeType::load('section_landing')) {
  NodeType::create([
    'type' => 'section_landing',
    'name' => 'Сторінка розділу',
    'description' => 'Landing-сторінка розділу порталу з Layout Builder. Привʼязується до одного з розділів (Новини, Оголошення тощо).',
  ])->save();
  print "Created content type: section_landing\n";
}

// Field: portal section selector.
if (!FieldStorageConfig::loadByName('node', 'field_portal_section')) {
  $allowed = [];
  foreach ($registry->getAllSectionsById() as $id => $section) {
    $allowed[$id] = $section['label'] ?? $id;
  }
  FieldStorageConfig::create([
    'field_name' => 'field_portal_section',
    'entity_type' => 'node',
    'type' => 'list_string',
    'settings' => [
      'allowed_values' => $allowed,
    ],
    'cardinality' => 1,
  ])->save();
  print "Created field storage: field_portal_section\n";
}

if (!FieldConfig::loadByName('node', 'section_landing', 'field_portal_section')) {
  FieldConfig::create([
    'field_name' => 'field_portal_section',
    'entity_type' => 'node',
    'bundle' => 'section_landing',
    'label' => 'Розділ порталу',
    'required' => TRUE,
  ])->save();
  print "Created field: field_portal_section on section_landing\n";
}

// Body field for optional intro (Layout Builder handles main layout).
if (!FieldConfig::loadByName('node', 'section_landing', 'body')) {
  FieldConfig::create([
    'field_name' => 'body',
    'entity_type' => 'node',
    'bundle' => 'section_landing',
    'label' => 'Короткий опис',
    'settings' => ['display_summary' => FALSE],
  ])->save();
}

// Form display.
$form = EntityFormDisplay::load('node.section_landing.default')
  ?? EntityFormDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'section_landing',
    'mode' => 'default',
    'status' => TRUE,
  ]);
$form->setComponent('title', ['type' => 'string_textfield', 'weight' => 0]);
$form->setComponent('field_portal_section', ['type' => 'options_select', 'weight' => 1]);
$form->setComponent('body', ['type' => 'text_textarea', 'weight' => 2]);
$form->save();

// View display with Layout Builder enabled.
$view = EntityViewDisplay::load('node.section_landing.default')
  ?? EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'section_landing',
    'mode' => 'default',
    'status' => TRUE,
  ]);
$view->removeComponent('body');
$view->setThirdPartySetting('layout_builder', 'enabled', TRUE);
$view->setThirdPartySetting('layout_builder', 'allow_custom', TRUE);
$view->save();
print "Enabled Layout Builder on section_landing default view mode\n";

print "\n=== Portal sections → content types mapping ===\n";
foreach ($registry->getAllSectionsById() as $id => $section) {
  $bundles = implode(', ', $section['content_bundles'] ?? []);
  printf("  %-12s %-14s bundles: %s\n", $id, $section['label'] ?? '', $bundles);
}

print "\nDone. Create section landing nodes at /node/add/section_landing\n";
