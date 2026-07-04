<?php

/**
 * @file
 * Footer setup: Simplenews, menu, stats bar blocks.
 *
 * Usage: drush php:script scripts/setup_footer.php
 */

use Drupal\block\Entity\Block;
use Drupal\simplenews\Entity\Newsletter;
use Drupal\user\Entity\Role;

$entity_type_manager = \Drupal::entityTypeManager();
$module_installer = \Drupal::service('module_installer');
$module_handler = \Drupal::moduleHandler();

// ---------------------------------------------------------------------------
// 1. Enable Simplenews.
// ---------------------------------------------------------------------------
$extension_list = \Drupal::service('extension.list.module');
if (!$extension_list->exists('simplenews')) {
  throw new RuntimeException('Simplenews is not installed. Run: ddev composer require drupal/simplenews');
}
if (!$module_handler->moduleExists('simplenews')) {
  $module_installer->install(['simplenews'], TRUE);
}
print "Simplenews ready.\n";

// Allow anonymous newsletter subscription.
foreach (['anonymous', 'authenticated'] as $role_id) {
  $role = Role::load($role_id);
  if ($role && !$role->hasPermission('subscribe to newsletters')) {
    $role->grantPermission('subscribe to newsletters');
    $role->save();
    print "Granted subscribe permission to: $role_id\n";
  }
}

// ---------------------------------------------------------------------------
// 2. Newsletter for portal updates.
// ---------------------------------------------------------------------------
$newsletter_id = 'portal_updates';
$newsletter = Newsletter::load($newsletter_id);
if (!$newsletter) {
  $site_mail = \Drupal::config('system.site')->get('mail') ?: 'noreply@example.com';
  $site_name = \Drupal::config('system.site')->get('name') ?: 'Мій Богуслав';
  Newsletter::create([
    'id' => $newsletter_id,
    'name' => 'Оновлення порталу',
    'description' => 'Новини та оновлення ресурсу «Мій Богуслав».',
    'format' => 'html',
    'priority' => 0,
    'from_name' => $site_name,
    'from_address' => $site_mail,
    'subject' => '[[simplenews-newsletter:name]] — [node:title]',
    'hyperlinks' => TRUE,
    'new_account' => 'none',
    'access' => 'default',
    'weight' => 0,
  ])->save();
  print "Created newsletter: $newsletter_id\n";
}
else {
  print "Newsletter already exists: $newsletter_id\n";
}

// ---------------------------------------------------------------------------
// 3. Footer menu links (vertical navigation).
// ---------------------------------------------------------------------------
$menu_name = 'footer';

// Hide default Drupal "Contact" link from footer menu.
\Drupal::configFactory()->getEditable('core.menu.static_menu_link_overrides')
  ->set('definitions.contact__site_page.enabled', FALSE)
  ->save(TRUE);

$links = [
  '/home' => 'Головна',
  '/novyny' => 'Новини',
  '/ogoloshennya' => 'Оголошення',
  '/robota' => 'Робота',
  '/biznes' => 'Бізнес',
  '/spilnota' => 'Спільнота',
  '/karta' => 'Карта',
  '/podii' => 'Події',
  '/misto' => 'Місто',
];

$weight = 0;
$menu_link_storage = $entity_type_manager->getStorage('menu_link_content');
foreach ($links as $path => $title) {
  $existing = $menu_link_storage->loadByProperties(['menu_name' => $menu_name, 'title' => $title]);
  if ($existing) {
    continue;
  }
  $menu_link_storage->create([
    'title' => $title,
    'menu_name' => $menu_name,
    'link' => ['uri' => 'internal:' . $path],
    'expanded' => FALSE,
    'weight' => $weight++,
  ])->save();
  print "Footer menu link: $title → $path\n";
}

// ---------------------------------------------------------------------------
// 4. Place footer blocks.
// ---------------------------------------------------------------------------
$theme = 'mybg_radix';
$blocks = [
  'mybg_radix_newsletter' => [
    'region' => 'footer',
    'weight' => -30,
    'plugin' => 'simplenews_subscription_block',
    'settings' => [
      'id' => 'simplenews_subscription_block',
      'label' => 'Підписка на оновлення порталу',
      'label_display' => '0',
      'show_manage' => FALSE,
      'newsletters' => [$newsletter_id => $newsletter_id],
      'default_newsletters' => [$newsletter_id => $newsletter_id],
      'message' => 'Електронна пошта',
      'unique_id' => 'footer_newsletter',
    ],
  ],
  'mybg_radix_footer_menu' => [
    'region' => 'footer',
    'weight' => -20,
    'plugin' => 'system_menu_block:footer',
    'settings' => [
      'id' => 'system_menu_block:footer',
      'label' => 'Навігація',
      'label_display' => '0',
      'level' => 1,
      'depth' => 1,
      'expand_all_items' => FALSE,
    ],
  ],
  'mybg_radix_site_contacts' => [
    'region' => 'footer',
    'weight' => -10,
    'plugin' => 'config_pages_block',
    'settings' => [
      'id' => 'config_pages_block',
      'label' => 'Контакти',
      'label_display' => '0',
      'config_page_type' => 'site_contacts',
      'config_page_view_mode' => 'default',
    ],
  ],
  'mybg_radix_site_stats' => [
    'region' => 'page_bottom',
    'weight' => 0,
    'plugin' => 'mybg_site_stats_bar',
    'settings' => [
      'id' => 'mybg_site_stats_bar',
      'label' => 'Статистика порталу',
      'label_display' => '0',
    ],
  ],
];

foreach ($blocks as $block_id => $info) {
  $block = Block::load($block_id);
  if (!$block) {
    Block::create([
      'id' => $block_id,
      'theme' => $theme,
      'region' => $info['region'],
      'weight' => $info['weight'],
      'plugin' => $info['plugin'],
      'settings' => $info['settings'],
      'visibility' => [],
      'status' => TRUE,
    ])->save();
    print "Placed block: $block_id → {$info['region']}\n";
    continue;
  }

  $block->setRegion($info['region']);
  $block->setWeight($info['weight']);
  $block->set('settings', $info['settings']);
  $block->setStatus(TRUE);
  $block->save();
  print "Updated block: $block_id → {$info['region']}\n";
}

print "Footer setup complete.\n";
