<?php

/**
 * @file
 * Debug site contacts block visibility and render.
 */

$block = \Drupal::entityTypeManager()->getStorage('block')->load('mybg_radix_site_contacts');
if (!$block) {
  echo "block missing\n";
  return;
}

echo 'status=' . ($block->status() ? '1' : '0') . ' region=' . $block->getRegion() . PHP_EOL;
echo 'visibility=' . json_encode($block->getVisibilityConditions(), JSON_UNESCAPED_UNICODE) . PHP_EOL;

$plugin = $block->getPlugin();
echo 'access=' . ($plugin->access(\Drupal::currentUser()) ? '1' : '0') . PHP_EOL;

$build = $plugin->build();
echo 'build keys: ' . implode(', ', array_keys($build)) . PHP_EOL;

$config_page = config_pages_config('site_contacts');
echo $config_page ? 'config_page id=' . $config_page->id() . PHP_EOL : "no config_page\n";
