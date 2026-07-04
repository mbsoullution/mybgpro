<?php

/**
 * @file
 * Debug config page entity access.
 */

$cp = config_pages_config('site_contacts');
if (!$cp) {
  echo "no config page\n";
  return;
}

$anon = \Drupal\user\Entity\User::getAnonymousUser();
$auth = \Drupal\user\Entity\User::load(1);

foreach (['anon', 'auth'] as $label) {
  $account = $label === 'anon' ? $anon : $auth;
  echo "$label view=" . ($cp->access('view', $account) ? '1' : '0') . PHP_EOL;
}

echo 'permissions anon: ' . implode(', ', array_filter([
  $anon->hasPermission('view config_pages entity') ? 'view config_pages entity' : '',
  $anon->hasPermission('access content') ? 'access content' : '',
])) . PHP_EOL;
