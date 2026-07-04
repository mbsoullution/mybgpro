<?php

// phpcs:ignoreFile

/**
 * @file
 * Example local settings — copy to settings.local.php and fill in values.
 *
 * cp web/sites/default/settings.local.example.php web/sites/default/settings.local.php
 */

/**
 * Facebook Login (Social Auth).
 *
 * Create an app: https://developers.facebook.com/apps
 * Add "Facebook Login" product.
 * Valid OAuth redirect URI (shown in Drupal admin):
 *   https://YOUR-DOMAIN/user/login/facebook/callback
 * For DDEV: https://mybgpro.ddev.site/user/login/facebook/callback
 */
$config['social_auth_facebook.settings']['app_id'] = 'YOUR_FACEBOOK_APP_ID';
$config['social_auth_facebook.settings']['app_secret'] = 'YOUR_FACEBOOK_APP_SECRET';
$config['social_auth_facebook.settings']['graph_version'] = '21.0';
