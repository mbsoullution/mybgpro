<?php

/**
 * @file
 * Place site contacts block and export content for sync.
 *
 * Usage: drush php:script scripts/place_site_contacts_block.php
 */

use Drupal\block\Entity\Block;

$theme = 'mybg_radix';
$block_id = 'mybg_radix_site_contacts';

if (!Block::load($block_id)) {
  Block::create([
    'id' => $block_id,
    'theme' => $theme,
    'region' => 'footer',
    'weight' => -10,
    'plugin' => 'config_pages_block',
    'settings' => [
      'id' => 'config_pages_block',
      'label' => 'Контакти',
      'label_display' => '0',
      'config_page' => 'site_contacts',
      'config_page_view_mode' => 'default',
    ],
    'visibility' => [],
    'status' => TRUE,
  ])->save();
  print "Block placed: $block_id\n";
}
else {
  print "Block already exists: $block_id\n";
}

$config_page = config_pages_config('site_contacts');
if ($config_page) {
  print 'Config page entity ID: ' . $config_page->id() . "\n";
  print 'UUID: ' . $config_page->uuid() . "\n";
}

print "Done.\n";
