<?php

/**
 * @file
 * One-time setup for «Мій Богуслав — соціальна матриця міста» MVP.
 *
 * Usage: drush php:script scripts/setup_social_matrix.php
 */

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

$entity_type_manager = \Drupal::entityTypeManager();
$module_handler = \Drupal::moduleHandler();

// ---------------------------------------------------------------------------
// 1. Enable required modules.
// ---------------------------------------------------------------------------
$modules = ['geofield', 'mybg_matrix'];
foreach ($modules as $module) {
  if (!$module_handler->moduleExists($module)) {
    throw new RuntimeException("Module $module is not installed. Run composer install first.");
  }
  if (!$module_handler->moduleExists($module) || !\Drupal::service('module_installer')->install([$module], TRUE)) {
    // module_installer returns void; re-check.
  }
}
\Drupal::service('module_installer')->install($modules, TRUE);
print "Enabled modules: " . implode(', ', $modules) . "\n";

// ---------------------------------------------------------------------------
// 2. Site branding.
// ---------------------------------------------------------------------------
\Drupal::configFactory()->getEditable('system.site')
  ->set('name', 'Мій Богуслав')
  ->set('slogan', 'Найрідніше місто у світі')
  ->set('page.front', '/home')
  ->save(TRUE);
print "Updated site branding.\n";

// ---------------------------------------------------------------------------
// 3. Content type labels (align with TZ terminology).
// ---------------------------------------------------------------------------
$labels = [
  'organization' => ['label' => 'Бізнес', 'description' => 'Бізнес-сторінки: магазини, заклади, послуги'],
  'listing' => ['label' => 'Оголошення', 'description' => 'Продам, куплю, обміняю, віддам, послуги'],
  'real_estate' => ['label' => 'Нерухомість', 'description' => 'Оренда та продаж нерухомості'],
  'event' => ['label' => 'Подія', 'description' => 'Міські події та заходи'],
  'news' => ['label' => 'Новини', 'description' => 'Новини міста та громади'],
  'city_page' => ['label' => 'Місце', 'description' => 'Історичні, туристичні та цікаві місця міста'],
  'article' => ['label' => 'Авторський матеріал', 'description' => 'Блог та авторські публікації мешканців'],
];

foreach ($labels as $id => $info) {
  $type = NodeType::load($id);
  if ($type) {
    $type->set('name', $info['label']);
    $type->set('description', $info['description']);
    $type->save();
    print "Updated content type label: $id → {$info['label']}\n";
  }
}

// ---------------------------------------------------------------------------
// 4. City content type.
// ---------------------------------------------------------------------------
if (!NodeType::load('city')) {
  NodeType::create([
    'type' => 'city',
    'name' => 'Місто',
    'description' => 'Сутність міста для масштабування платформи',
  ])->save();
  print "Created content type: city\n";
}

$city_fields = [
  'field_city_region' => ['type' => 'string', 'label' => 'Область', 'bundle' => 'city'],
  'field_city_description' => ['type' => 'text_long', 'label' => 'Опис', 'bundle' => 'city'],
  'field_city_image' => ['type' => 'image', 'label' => 'Герб / фото', 'bundle' => 'city'],
  'field_city_geo' => ['type' => 'geofield', 'label' => 'Координати центру', 'bundle' => 'city'],
];

// ---------------------------------------------------------------------------
// 5. Job content type.
// ---------------------------------------------------------------------------
if (!NodeType::load('job')) {
  NodeType::create([
    'type' => 'job',
    'name' => 'Робота',
    'description' => 'Вакансії, резюме, підробіток',
  ])->save();
  print "Created content type: job\n";
}

$shared_fields = [
  'field_city' => [
    'type' => 'entity_reference',
    'label' => 'Місто',
    'settings' => ['target_type' => 'node'],
  ],
  'field_geolocation' => [
    'type' => 'geofield',
    'label' => 'Геолокація',
  ],
  'body' => [
    'type' => 'text_with_summary',
    'label' => 'Опис',
  ],
];

$job_fields = [
  'field_job_type' => ['type' => 'list_string', 'label' => 'Тип', 'settings' => [
    'allowed_values' => ['vacancy' => 'Вакансія', 'resume' => 'Резюме'],
  ]],
  'field_job_category' => ['type' => 'entity_reference', 'label' => 'Категорія', 'settings' => ['target_type' => 'taxonomy_term']],
  'field_job_salary' => ['type' => 'string', 'label' => 'Оплата'],
  'field_job_schedule' => ['type' => 'string', 'label' => 'Графік'],
  'field_job_contact' => ['type' => 'telephone', 'label' => 'Контакт'],
  'field_job_experience' => ['type' => 'string', 'label' => 'Досвід'],
];

// Taxonomies.
$vocabularies = [
  'job_category' => 'Категорії роботи',
  'event_category' => 'Категорії подій',
];

foreach ($vocabularies as $vid => $label) {
  if (!Vocabulary::load($vid)) {
    Vocabulary::create(['vid' => $vid, 'name' => $label])->save();
    print "Created vocabulary: $vid\n";
  }
}

$org_vocab = Vocabulary::load('organization_type');
if ($org_vocab) {
  $org_vocab->set('name', 'Категорії бізнесу');
  $org_vocab->save();
}

$listing_vocab = Vocabulary::load('listing_category');
if ($listing_vocab) {
  $listing_vocab->set('name', 'Категорії оголошень');
  $listing_vocab->save();
}

/**
 * Helper: ensure field storage + instance.
 */
$ensure_field = function (string $entity_type, string $bundle, string $field_name, array $info) use ($entity_type_manager): void {
  if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
    $storage = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $info['type'],
      'cardinality' => $info['cardinality'] ?? 1,
    ];
    if (!empty($info['settings'])) {
      $storage['settings'] = $info['settings'];
    }
    FieldStorageConfig::create($storage)->save();
    print "  storage: $field_name\n";
  }

  if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    $field = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $info['label'],
      'required' => $info['required'] ?? FALSE,
    ];
    if (!empty($info['settings'])) {
      $field['settings'] = $info['settings'];
    }
    FieldConfig::create($field)->save();
    print "  field: $bundle.$field_name\n";
  }
};

// City fields.
foreach ($city_fields as $name => $info) {
  $ensure_field('node', 'city', $name, $info);
}

// Job fields.
foreach ($job_fields as $name => $info) {
  $info += ['settings' => $info['settings'] ?? []];
  if ($name === 'field_job_category') {
    $info['settings']['handler_settings'] = ['target_bundles' => ['job_category' => 'job_category']];
  }
  $ensure_field('node', 'job', $name, $info);
}
$ensure_field('node', 'job', 'field_city', $shared_fields['field_city'] + ['settings' => [
  'handler' => 'default:node',
  'handler_settings' => ['target_bundles' => ['city' => 'city']],
]]);
$ensure_field('node', 'job', 'body', $shared_fields['body']);

// field_city on all city bundles.
$city_bundles = ['listing', 'real_estate', 'news', 'event', 'organization', 'city_page', 'job', 'article'];
foreach ($city_bundles as $bundle) {
  if (!NodeType::load($bundle)) {
    continue;
  }
  $ensure_field('node', $bundle, 'field_city', $shared_fields['field_city'] + ['settings' => [
    'handler' => 'default:node',
    'handler_settings' => ['target_bundles' => ['city' => 'city']],
  ]]);
}

// field_geolocation on map bundles.
foreach (['organization', 'event', 'city_page'] as $bundle) {
  $ensure_field('node', $bundle, 'field_geolocation', $shared_fields['field_geolocation']);
}

// body on editorial bundles if missing.
foreach (['organization', 'event', 'news', 'city_page'] as $bundle) {
  if (!NodeType::load($bundle)) {
    continue;
  }
  if (!FieldStorageConfig::loadByName('node', 'body')) {
    continue;
  }
  $ensure_field('node', $bundle, 'body', $shared_fields['body']);
}

// Event category field.
$ensure_field('node', 'event', 'field_event_category', [
  'type' => 'entity_reference',
  'label' => 'Категорія події',
  'settings' => ['target_type' => 'taxonomy_term', 'handler_settings' => ['target_bundles' => ['event_category' => 'event_category']]],
]);

// ---------------------------------------------------------------------------
// 6. Default Boguslav city node.
// ---------------------------------------------------------------------------
$city_storage = $entity_type_manager->getStorage('node');
$city_ids = $city_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'city')
  ->execute();

if (!$city_ids) {
  $city = Node::create([
    'type' => 'city',
    'title' => 'Богуслав',
    'status' => 1,
    'field_city_region' => 'Київська область',
    'field_city_description' => [
      'value' => 'Мій Богуслав — соціальна матриця нашого рідного міста на річці Рось.',
      'format' => 'basic_html',
    ],
    'field_city_geo' => ['lat' => 49.5467, 'lon' => 30.8744, 'value' => 'POINT (30.8744 49.5467)'],
  ]);
  $city->save();
  print "Created default city node: Богуслав (nid {$city->id()})\n";
}
else {
  print "City node already exists.\n";
}

// Seed job categories.
$term_storage = $entity_type_manager->getStorage('taxonomy_term');
$job_terms = ['IT', 'Торгівля', 'Будівництво', 'Освіта', 'Послуги', 'Сезонна робота'];
foreach ($job_terms as $term_name) {
  $existing = $term_storage->loadByProperties(['vid' => 'job_category', 'name' => $term_name]);
  if (!$existing) {
    $term_storage->create(['vid' => 'job_category', 'name' => $term_name])->save();
  }
}

// ---------------------------------------------------------------------------
// 7. Roles per TZ.
// ---------------------------------------------------------------------------
$roles = [
  'resident' => ['label' => 'Мешканець', 'weight' => 4, 'perms' => ['access content', 'post comments']],
  'business_owner' => ['label' => 'Бізнес', 'weight' => 6, 'perms' => ['access content', 'post comments']],
  'education_owner' => ['label' => 'Навчальний заклад', 'weight' => 7, 'perms' => ['access content', 'post comments']],
];

foreach ($roles as $id => $info) {
  $role = Role::load($id);
  if (!$role) {
    $role = Role::create(['id' => $id, 'label' => $info['label']]);
  }
  $role->set('weight', $info['weight']);
  foreach ($info['perms'] as $perm) {
    $role->grantPermission($perm);
  }
  $role->save();
  print "Role: {$info['label']}\n";
}

// Job permissions for trusted users.
$trusted = Role::load('trusted_user');
if ($trusted) {
  $trusted->set('label', 'Довірений мешканець');
  foreach (['create job content', 'edit own job content', 'delete own job content'] as $perm) {
    $trusted->grantPermission($perm);
  }
  $trusted->save();
}

$verified = Role::load('verified_user');
if ($verified) {
  $verified->set('label', 'Мешканець');
}

// ---------------------------------------------------------------------------
// 8. Main navigation menu.
// ---------------------------------------------------------------------------
$menu_name = 'main';
$links = [
  '/novyny' => 'Новини',
  '/ogoloshennya' => 'Оголошення',
  '/robota' => 'Робота',
  '/biznes' => 'Бізнес',
  '/spilnota' => 'Спільнота',
  '/karta' => 'Карта',
  '/podii' => 'Події',
  '/misto' => 'Місто',
];

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
    'weight' => 0,
  ])->save();
  print "Menu link: $title → $path\n";
}

// ---------------------------------------------------------------------------
// 9. Section Views (minimal page displays).
// ---------------------------------------------------------------------------
$view_definitions = [
  'city_news' => ['label' => 'Новини міста', 'path' => 'novyny', 'types' => ['news']],
  'city_listings' => ['label' => 'Оголошення', 'path' => 'ogoloshennya', 'types' => ['listing', 'real_estate']],
  'city_jobs' => ['label' => 'Робота', 'path' => 'robota', 'types' => ['job']],
  'city_businesses' => ['label' => 'Бізнес', 'path' => 'biznes', 'types' => ['organization']],
  'city_events' => ['label' => 'Події', 'path' => 'podii', 'types' => ['event']],
  'city_places' => ['label' => 'Місто', 'path' => 'misto', 'types' => ['city_page']],
  'city_community' => ['label' => 'Спільнота', 'path' => 'spilnota', 'types' => ['article']],
];

foreach ($view_definitions as $view_id => $def) {
  if (View::load($view_id)) {
    continue;
  }

  $view = View::create([
    'label' => $def['label'],
    'id' => $view_id,
    'base_table' => 'node_field_data',
    'module' => 'node',
    'description' => $def['label'] . ' — «Мій Богуслав»',
  ]);

  $view->addDisplay('default', 'default', 'default');
  $view->set('display', [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Master',
      'position' => 0,
      'display_options' => [
        'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
        'cache' => ['type' => 'tag'],
        'query' => ['type' => 'views_query', 'options' => []],
        'exposed_form' => ['type' => 'basic'],
        'pager' => ['type' => 'full', 'options' => ['items_per_page' => 12]],
        'style' => ['type' => 'default', 'options' => ['row_class' => 'mybg-list-item']],
        'row' => ['type' => 'entity:node', 'options' => ['view_mode' => 'teaser']],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'value' => '1',
            'plugin_id' => 'boolean',
          ],
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'value' => $def['types'],
            'plugin_id' => 'in_operator',
          ],
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'order' => 'DESC',
            'plugin_id' => 'date',
          ],
        ],
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'path' => $def['path'],
        'display_extenders' => [],
      ],
    ],
  ]);

  $view->save();
  print "Created view: {$def['label']} → /{$def['path']}\n";
}

// ---------------------------------------------------------------------------
// 10. Place dashboard block on front page.
// ---------------------------------------------------------------------------
$block_storage = $entity_type_manager->getStorage('block');
$block_id = 'mybg_radix_citydashboard';
if (!$block_storage->load($block_id)) {
  $block_storage->create([
    'id' => $block_id,
    'theme' => 'mybg_radix',
    'region' => 'content',
    'weight' => -10,
    'plugin' => 'mybg_city_dashboard',
    'settings' => [
      'id' => 'mybg_city_dashboard',
      'label' => 'Міський дашборд',
      'label_display' => '0',
      'provider' => 'mybg_matrix',
    ],
    'visibility' => [
      'request_path' => [
        'id' => 'request_path',
        'negate' => FALSE,
        'pages' => '<front>',
      ],
    ],
  ])->save();
  print "Placed city dashboard block on front page.\n";
}

// Hide default content block on front (dashboard replaces it).
$content_block = $block_storage->load('mybg_radix_content');
if ($content_block) {
  $content_block->setVisibilityConfig('request_path', [
    'id' => 'request_path',
    'negate' => TRUE,
    'pages' => '<front>',
  ]);
  $content_block->save();
}

// Ensure main menu block is placed.
$main_menu_block = $block_storage->load('mybg_radix_main_menu');
if (!$main_menu_block) {
  $block_storage->create([
    'id' => 'mybg_radix_main_menu',
    'theme' => 'mybg_radix',
    'region' => 'navbar_left',
    'weight' => 0,
    'plugin' => 'system_menu_block:main',
    'settings' => [
      'id' => 'system_menu_block:main',
      'label' => 'Main navigation',
      'label_display' => '0',
      'provider' => 'system',
      'level' => 1,
      'depth' => 2,
    ],
  ])->save();
  print "Placed main menu block.\n";
}

// ---------------------------------------------------------------------------
// 11. Update Search API index bundles.
// ---------------------------------------------------------------------------
$index = $entity_type_manager->getStorage('search_api_index')->load('city_content_index');
if ($index) {
  $index->set('datasource_settings', [
    'entity:node' => [
      'bundles' => [
        'default' => FALSE,
        'selected' => ['listing', 'real_estate', 'news', 'event', 'organization', 'job', 'city_page', 'article'],
      ],
    ],
  ]);
  $index->save();
  print "Updated Search API index bundles.\n";
}

\Drupal::service('router.builder')->rebuild();
drupal_flush_all_caches();

print "\n✓ Social matrix MVP setup complete.\n";
print "  Front: " . Url::fromRoute('<front>')->toString() . "\n";
print "  Map: /karta | Search: /poshuk\n";
print "  Run: drush cex -y  to export config\n";
