<?php

/**
 * @file
 * Grant config pages edit permission to editor role.
 *
 * Usage: drush php:script scripts/grant_config_pages_permissions.php
 */

$role = \Drupal\user\Entity\Role::load('editor');
if ($role) {
  $role->grantPermission('edit config_pages entity');
  $role->grantPermission('view config_pages entity');
  $role->save();
  print "Granted config_pages permissions to editor role.\n";
}

$admin_role = \Drupal\user\Entity\Role::load('moderator');
if ($admin_role) {
  $admin_role->grantPermission('edit config_pages entity');
  $admin_role->grantPermission('view config_pages entity');
  $admin_role->save();
  print "Granted config_pages permissions to moderator role.\n";
}
