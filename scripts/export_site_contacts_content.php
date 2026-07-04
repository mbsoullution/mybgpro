<?php

/**
 * @file
 * Export site_contacts config page for deployment.
 *
 * Usage: drush php:script scripts/export_site_contacts_content.php
 */

$config_page = config_pages_config('site_contacts');
if (!$config_page) {
  throw new \RuntimeException('site_contacts config page not found.');
}

$dir = DRUPAL_ROOT . '/../content/site_contacts';
if (!is_dir($dir)) {
  mkdir($dir, 0755, TRUE);
}

$uuid = $config_page->uuid();
$file = $dir . '/site_contacts.yml';

\Drupal::service('drush.cmd')->execute('content:export', [
  'entity_type' => 'config_pages',
  'entity_id' => $uuid,
  'file' => $file,
]);

print "Exported to: $file\n";
