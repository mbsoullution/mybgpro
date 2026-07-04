<?php

/**
 * @file
 * Allow config_pages export via Single Content Sync.
 *
 * Usage: drush php:script scripts/enable_config_pages_content_sync.php
 */

$config = \Drupal::configFactory()->getEditable('single_content_sync.settings');
$allowed = $config->get('allowed_entity_types') ?? [];
$allowed['config_pages'] = [];
$config->set('allowed_entity_types', $allowed)->save();
print "Enabled config_pages in single_content_sync.\n";
