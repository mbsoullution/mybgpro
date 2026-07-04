<?php

/**
 * @file
 * Dashboard branding, menu, roles, and homepage blocks setup.
 *
 * Usage: drush php:script scripts/setup_dashboard_branding.php
 */

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;

$etm = \Drupal::entityTypeManager();

// ---------------------------------------------------------------------------
// 1. Config Page: dashboard_settings (editable hero)
// ---------------------------------------------------------------------------
if (!ConfigPagesType::load('dashboard_settings')) {
  ConfigPagesType::create([
    'id' => 'dashboard_settings',
    'label' => 'Міський дашборд',
    'menu' => [
      'path' => '/admin/config/mybg/dashboard',
      'weight' => 1,
      'description' => 'Заголовок, слоган, відео-фон головної сторінки.',
    ],
    'context' => ['show_warning' => FALSE, 'language' => FALSE, 'theme' => FALSE],
    'token' => TRUE,
  ])->save();
  print "Created config page type: dashboard_settings\n";
}

$dash_fields = [
  'field_dash_title' => ['type' => 'string', 'label' => 'Заголовок', 'default' => 'Мій Богуслав'],
  'field_dash_tagline' => ['type' => 'string', 'label' => 'Підзаголовок', 'default' => 'Соціальна матриця міста'],
  'field_dash_slogan' => ['type' => 'string', 'label' => 'Слоган', 'default' => 'Найрідніше місто у світі'],
  'field_dash_video' => ['type' => 'file', 'label' => 'Відео-фон (MP4)', 'settings' => ['file_extensions' => 'mp4 webm', 'file_directory' => 'dashboard']],
  'field_dash_poster' => ['type' => 'image', 'label' => 'Постер / фото-фон', 'settings' => ['file_directory' => 'dashboard']],
];

foreach ($dash_fields as $field_name => $info) {
  if (!FieldStorageConfig::loadByName('config_pages', $field_name)) {
    $storage = [
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'type' => $info['type'],
      'cardinality' => 1,
    ];
    if (!empty($info['settings'])) {
      $storage['settings'] = $info['settings'];
    }
    FieldStorageConfig::create($storage)->save();
  }
  if (!FieldConfig::loadByName('config_pages', 'dashboard_settings', $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'config_pages',
      'bundle' => 'dashboard_settings',
      'label' => $info['label'],
    ])->save();
  }
}

// Default dashboard entity.
$existing = $etm->getStorage('config_pages')->loadByProperties(['type' => 'dashboard_settings']);
if (!$existing) {
  ConfigPages::create([
    'type' => 'dashboard_settings',
    'context' => 'a:0:{}',
    'field_dash_title' => 'Мій Богуслав',
    'field_dash_tagline' => 'Соціальна матриця міста',
    'field_dash_slogan' => 'Найрідніше місто у світі',
  ])->save();
  print "Created default dashboard_settings entity\n";
}

// Form/view displays.
$form_display = $etm->getStorage('entity_form_display')->load('config_pages.dashboard_settings.default');
if (!$form_display) {
  $form_display = $etm->getStorage('entity_form_display')->create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'dashboard_settings',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}

$view_display = $etm->getStorage('entity_view_display')->load('config_pages.dashboard_settings.default');
if (!$view_display) {
  $view_display = $etm->getStorage('entity_view_display')->create([
    'targetEntityType' => 'config_pages',
    'bundle' => 'dashboard_settings',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}

$form_components = [
  'field_dash_title' => ['type' => 'string_textfield', 'weight' => 0, 'placeholder' => 'Мій Богуслав'],
  'field_dash_tagline' => ['type' => 'string_textfield', 'weight' => 1, 'placeholder' => 'Соціальна матриця міста'],
  'field_dash_slogan' => ['type' => 'string_textfield', 'weight' => 2, 'placeholder' => 'Найрідніше місто у світі'],
  'field_dash_video' => ['type' => 'file_generic', 'weight' => 3],
  'field_dash_poster' => ['type' => 'image_image', 'weight' => 4],
];

foreach ($form_components as $field_name => $info) {
  $settings = [];
  if (!empty($info['placeholder'])) {
    $settings['placeholder'] = $info['placeholder'];
    $settings['size'] = 60;
  }
  if ($info['type'] === 'file_generic') {
    $settings['progress_indicator'] = 'throbber';
  }
  if ($info['type'] === 'image_image') {
    $settings['progress_indicator'] = 'throbber';
    $settings['preview_image_style'] = 'thumbnail';
  }
  $form_display->setComponent($field_name, [
    'type' => $info['type'],
    'weight' => $info['weight'],
    'region' => 'content',
    'settings' => $settings,
  ]);
  $view_display->setComponent($field_name, [
    'type' => str_starts_with($info['type'], 'image') ? 'image' : (str_starts_with($info['type'], 'file') ? 'file_default' : 'string'),
    'weight' => $info['weight'],
    'label' => 'above',
    'region' => 'content',
  ]);
}
$form_display->save();
$view_display->save();
print "Configured dashboard form/view displays\n";

// ---------------------------------------------------------------------------
// 2. Site admin display name: Мій Богуслав
// ---------------------------------------------------------------------------
$admin = User::load(1);
if ($admin) {
  $admin->set('field_profile_full_name', 'Мій Богуслав');
  $admin->save();
  print "Updated user 1 display name via profile field\n";
}

// ---------------------------------------------------------------------------
// 3. Platform developer role
// ---------------------------------------------------------------------------
if (!Role::load('platform_developer')) {
  Role::create([
    'id' => 'platform_developer',
    'label' => 'Головний розробник',
    'weight' => 2,
  ])->save();
}
$dev = Role::load('platform_developer');
$dev->set('is_admin', TRUE);
$dev->save();
print "Role platform_developer (is_admin) ready\n";

// ---------------------------------------------------------------------------
// 4. Views: 10 items per section
// ---------------------------------------------------------------------------
$view_ids = ['city_news', 'city_listings', 'city_jobs', 'city_businesses', 'city_events', 'city_places', 'city_community'];
foreach ($view_ids as $view_id) {
  $view = View::load($view_id);
  if (!$view) {
    continue;
  }
  $display = &$view->getDisplay('default');
  $display['display_options']['pager']['options']['items_per_page'] = 10;
  $view->save();
  print "View $view_id: 10 items per page\n";
}

// ---------------------------------------------------------------------------
// 5. Clean up main menu
// ---------------------------------------------------------------------------
$desired = [
  ['title' => 'Головна', 'uri' => 'internal:/', 'weight' => -50],
  ['title' => 'Новини', 'uri' => 'internal:/novyny', 'weight' => 0],
  ['title' => 'Оголошення', 'uri' => 'internal:/ogoloshennya', 'weight' => 1],
  ['title' => 'Робота', 'uri' => 'internal:/robota', 'weight' => 2],
  ['title' => 'Бізнес', 'uri' => 'internal:/biznes', 'weight' => 3],
  ['title' => 'Спільнота', 'uri' => 'internal:/spilnota', 'weight' => 4],
  ['title' => 'Карта', 'uri' => 'internal:/karta', 'weight' => 5],
  ['title' => 'Події', 'uri' => 'internal:/podii', 'weight' => 6],
  ['title' => 'Місто', 'uri' => 'internal:/misto', 'weight' => 7],
  ['title' => 'Допомога', 'uri' => 'internal:/dopomoga', 'weight' => 50],
];

$keep_titles = array_column($desired, 'title');
$mls = $etm->getStorage('menu_link_content');
foreach ($mls->loadByProperties(['menu_name' => 'main']) as $link) {
  if (!in_array($link->getTitle(), $keep_titles, TRUE)) {
    $link->delete();
    print 'Removed menu: ' . $link->getTitle() . "\n";
  }
}
foreach ($desired as $item) {
  $found = FALSE;
  foreach ($mls->loadByProperties(['menu_name' => 'main', 'title' => $item['title']]) as $link) {
    $link->link = ['uri' => $item['uri']];
    $link->weight = $item['weight'];
    $link->save();
    $found = TRUE;
  }
  if (!$found) {
    $mls->create([
      'title' => $item['title'],
      'menu_name' => 'main',
      'link' => ['uri' => $item['uri']],
      'weight' => $item['weight'],
    ])->save();
    print 'Added menu: ' . $item['title'] . "\n";
  }
}

// Remove Енциклопедія from main menu (available via Допомога).
foreach ($mls->loadByProperties(['menu_name' => 'main', 'title' => 'Енциклопедія']) as $link) {
  $link->delete();
}

// ---------------------------------------------------------------------------
// 6. Blocks: latest feed, branding, hide help on front
// ---------------------------------------------------------------------------
$block_storage = $etm->getStorage('block');

if (!$block_storage->load('mybg_radix_latestfeed')) {
  $block_storage->create([
    'id' => 'mybg_radix_latestfeed',
    'theme' => 'mybg_radix',
    'region' => 'content',
    'weight' => -8,
    'plugin' => 'mybg_latest_feed',
    'settings' => [
      'id' => 'mybg_latest_feed',
      'label' => 'Останні публікації',
      'label_display' => '0',
      'provider' => 'mybg_matrix',
    ],
    'visibility' => [
      'request_path' => ['id' => 'request_path', 'negate' => FALSE, 'pages' => '<front>'],
    ],
  ])->save();
  print "Placed latest feed block\n";
}

if (!$block_storage->load('mybg_radix_branding')) {
  $block_storage->create([
    'id' => 'mybg_radix_branding',
    'theme' => 'mybg_radix',
    'region' => 'navbar_branding',
    'weight' => -1,
    'plugin' => 'system_branding_block',
    'settings' => [
      'id' => 'system_branding_block',
      'label' => 'Site branding',
      'label_display' => '0',
      'provider' => 'system',
      'use_site_logo' => TRUE,
      'use_site_name' => FALSE,
      'use_site_slogan' => FALSE,
    ],
  ])->save();
  print "Placed branding block\n";
}

// Hide tip of day on front.
$tip = $block_storage->load('mybg_radix_tipofday');
if ($tip) {
  $tip->setStatus(FALSE);
  $tip->save();
  print "Disabled tip-of-day on front\n";
}

// Hide contextual help on front.
$help = $block_storage->load('mybg_radix_contextualhelp');
if ($help) {
  $help->setVisibilityConfig('request_path', [
    'id' => 'request_path',
    'negate' => TRUE,
    'pages' => '<front>',
  ]);
  $help->save();
  print "Hidden contextual help on front\n";
}

// Dashboard block weight above feed.
$dash_block = $block_storage->load('mybg_radix_citydashboard');
if ($dash_block) {
  $dash_block->setWeight(-10);
  $dash_block->save();
}

// ---------------------------------------------------------------------------
// 7. Theme logo via public files
// ---------------------------------------------------------------------------
$logo_src = DRUPAL_ROOT . '/themes/custom/mybg_radix/assets/branding/logo.png';
if (file_exists($logo_src)) {
  $data = file_get_contents($logo_src);
  $directory = 'public://branding';
  \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
  $uri = $directory . '/logo.png';
  \Drupal::service('file.repository')->writeData($data, $uri, \Drupal\Core\File\FileExists::Replace);
  $theme = \Drupal::theme()->getActiveTheme()->getName();
  \Drupal::configFactory()->getEditable($theme . '.settings')
    ->set('logo.path', $uri)
    ->set('logo.use_default', FALSE)
    ->save();
  print "Set theme logo: $uri\n";
}

// Grant config_pages permissions.
foreach (['editor', 'moderator', 'platform_developer'] as $role_id) {
  $role = Role::load($role_id);
  if ($role) {
    foreach (['edit config_pages entity', 'view config_pages entity'] as $perm) {
      $role->grantPermission($perm);
    }
    $role->save();
  }
}

drupal_flush_all_caches();
print "\n✓ Dashboard branding setup complete.\n";
print "  Edit dashboard: /admin/config/mybg/dashboard\n";
