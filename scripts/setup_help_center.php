<?php

/**
 * @file
 * Setup Help Center: documentation CT, encyclopedia, Search API, seed content.
 *
 * Usage: drush php:script scripts/setup_help_center.php
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

$etm = \Drupal::entityTypeManager();
$module_installer = \Drupal::service('module_installer');

if (!\Drupal::moduleHandler()->moduleExists('mybg_help')) {
  $module_installer->install(['mybg_help'], TRUE);
  print "Enabled mybg_help\n";
}

// ---------------------------------------------------------------------------
// Content type: documentation
// ---------------------------------------------------------------------------
if (!NodeType::load('documentation')) {
  NodeType::create([
    'type' => 'documentation',
    'name' => 'Документація',
    'description' => 'Статті довідкової системи та енциклопедії порталу',
  ])->save();
  print "Created CT: documentation\n";
}

if (!Vocabulary::load('help_category')) {
  Vocabulary::create(['vid' => 'help_category', 'name' => 'Розділи довідки'])->save();
  print "Created vocabulary: help_category\n";
}

$ensure_field = function (string $entity_type, string $bundle, string $field_name, array $info) {
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
    if (!empty($info['default_value'])) {
      $field['default_value'] = $info['default_value'];
    }
    FieldConfig::create($field)->save();
    print "  field: $bundle.$field_name\n";
  }
};

$doc_fields = [
  'field_doc_category' => [
    'type' => 'entity_reference',
    'label' => 'Розділ енциклопедії',
    'settings' => [
      'target_type' => 'taxonomy_term',
      'handler' => 'default:taxonomy_term',
      'handler_settings' => ['target_bundles' => ['help_category' => 'help_category']],
    ],
  ],
  'field_doc_module' => [
    'type' => 'list_string',
    'label' => 'Повʼязаний модуль',
    'settings' => [
      'allowed_values' => [
        'getting_started' => 'Початок роботи',
        'mission' => 'Місія',
        'news' => 'Новини',
        'listings' => 'Оголошення',
        'jobs' => 'Робота',
        'business' => 'Бізнес',
        'events' => 'Події',
        'map' => 'Карта',
        'community' => 'Спільнота',
        'city' => 'Місто',
        'profile' => 'Профіль',
        'gallery' => 'Галерея',
        'tour360' => '360-місця',
        'reputation' => 'Репутація',
        'security' => 'Безпека',
        'faq' => 'FAQ',
      ],
    ],
  ],
  'field_doc_parent' => [
    'type' => 'entity_reference',
    'label' => 'Батьківська стаття',
    'settings' => [
      'target_type' => 'node',
      'handler' => 'default:node',
      'handler_settings' => ['target_bundles' => ['documentation' => 'documentation']],
    ],
  ],
  'field_doc_weight' => [
    'type' => 'integer',
    'label' => 'Вага сортування',
    'default_value' => [['value' => 0]],
  ],
  'field_doc_faq' => [
    'type' => 'boolean',
    'label' => 'FAQ',
    'default_value' => [['value' => 0]],
  ],
  'field_doc_is_tip' => [
    'type' => 'boolean',
    'label' => 'Порада дня',
    'default_value' => [['value' => 0]],
  ],
  'field_doc_video' => [
    'type' => 'link',
    'label' => 'Відеоінструкція',
    'settings' => ['link_type' => 16, 'title' => 1],
  ],
];

foreach ($doc_fields as $name => $info) {
  $ensure_field('node', 'documentation', $name, $info);
}
$ensure_field('node', 'documentation', 'body', ['type' => 'text_with_summary', 'label' => 'Зміст']);

// User onboarding flag.
$ensure_field('user', 'user', 'field_onboarding_done', [
  'type' => 'boolean',
  'label' => 'Onboarding пройдено',
  'default_value' => [['value' => 0]],
]);

// Form / view displays for documentation.
foreach (['form', 'view'] as $mode_type) {
  $storage_name = $mode_type === 'form' ? 'entity_form_display' : 'entity_view_display';
  $id = "node.documentation.default";
  if (!$etm->getStorage($storage_name)->load($id)) {
    $etm->getStorage($storage_name)->create([
      'targetEntityType' => 'node',
      'bundle' => 'documentation',
      'mode' => 'default',
      'status' => TRUE,
    ])->save();
  }
}

// Pathauto.
if (!PathautoPattern::load('documentation_pattern')) {
  PathautoPattern::create([
    'id' => 'documentation_pattern',
    'label' => 'Documentation',
    'type' => 'canonical_entities:node',
    'pattern' => 'encyklopediya/[node:title]',
    'weight' => 0,
    'selection_criteria' => [
      [
        'id' => 'entity_bundle:node',
        'bundles' => ['documentation' => 'documentation'],
        'negate' => FALSE,
      ],
    ],
  ])->save();
  print "Created pathauto pattern for documentation\n";
}

// Search API index for help docs.
if (\Drupal::moduleHandler()->moduleExists('search_api')) {
  $server = $etm->getStorage('search_api_server')->load('city_search_server');
  if ($server && !$etm->getStorage('search_api_index')->load('help_docs_index')) {
    $index = $etm->getStorage('search_api_index')->create([
      'id' => 'help_docs_index',
      'name' => 'Help Documentation Index',
      'description' => 'Encyclopedia and help articles',
      'server' => 'city_search_server',
      'status' => TRUE,
      'read_only' => FALSE,
    ]);
    $index->set('datasource_settings', [
      'entity:node' => [
        'bundles' => ['default' => FALSE, 'selected' => ['documentation' => 'documentation']],
      ],
    ]);
    $index->set('field_settings', [
      'title' => [
        'label' => 'Title',
        'datasource_id' => 'entity:node',
        'property_path' => 'title',
        'type' => 'text',
      ],
      'body' => [
        'label' => 'Body',
        'datasource_id' => 'entity:node',
        'property_path' => 'body',
        'type' => 'text',
      ],
    ]);
    $index->set('processor_settings', [
      'add_url' => [],
      'content_access' => ['weights' => ['preprocess_query' => -10]],
      'entity_status' => ['weights' => ['preprocess_query' => -10]],
    ]);
    $index->save();
    print "Created Search API index: help_docs_index\n";
    $index->reindex();
  }

  // Add documentation to global city index too.
  $city_index = $etm->getStorage('search_api_index')->load('city_content_index');
  if ($city_index) {
    $bundles = $city_index->get('datasource_settings')['entity:node']['selected'] ?? [];
    if (!in_array('documentation', $bundles, TRUE)) {
      $bundles[] = 'documentation';
      $city_index->set('datasource_settings', [
        'entity:node' => ['default' => FALSE, 'selected' => array_combine($bundles, $bundles)],
      ]);
      $city_index->save();
      print "Added documentation to city_content_index\n";
    }
  }
}

// Encyclopedia categories.
$categories = [
  'getting_started' => 'Початок роботи',
  'registration' => 'Реєстрація',
  'profile' => 'Профіль',
  'news' => 'Новини',
  'business' => 'Бізнес',
  'jobs' => 'Робота',
  'events' => 'Події',
  'map' => 'Карта',
  'gallery' => 'Галерея',
  'tour360' => '360-місця',
  'community' => 'Спільнота',
  'reputation' => 'Репутація',
  'ratings' => 'Рейтинги',
  'achievements' => 'Досягнення',
  'security' => 'Безпека',
  'faq' => 'FAQ',
  'video' => 'Відеоуроки',
];

$term_ids = [];
$weight = 0;
foreach ($categories as $key => $name) {
  $existing = $etm->getStorage('taxonomy_term')->loadByProperties(['vid' => 'help_category', 'name' => $name]);
  if ($existing) {
    $term_ids[$key] = reset($existing)->id();
  }
  else {
    $term = Term::create(['vid' => 'help_category', 'name' => $name, 'weight' => $weight++]);
    $term->save();
    $term_ids[$key] = $term->id();
  }
}

/**
 * Create doc if not exists by title.
 */
$create_doc = function (array $data) use ($etm, $term_ids) {
  $existing = $etm->getStorage('node')->loadByProperties([
    'type' => 'documentation',
    'title' => $data['title'],
  ]);
  if ($existing) {
    return reset($existing);
  }

  $node = Node::create([
    'type' => 'documentation',
    'title' => $data['title'],
    'status' => 1,
    'promote' => 0,
    'sticky' => 0,
    'body' => [
      'value' => $data['body'],
      'format' => 'basic_html',
      'summary' => $data['summary'] ?? '',
    ],
    'field_doc_module' => $data['module'] ?? NULL,
    'field_doc_category' => isset($data['category']) ? $term_ids[$data['category']] : NULL,
    'field_doc_weight' => $data['weight'] ?? 0,
    'field_doc_faq' => $data['faq'] ?? 0,
    'field_doc_is_tip' => $data['tip'] ?? 0,
  ]);
  $node->save();
  print "  doc: {$data['title']}\n";
  return $node;
};

// Seed documentation articles.
print "Seeding documentation...\n";

$create_doc([
  'title' => 'Місія та цінності «Мій Богуслав»',
  'module' => 'mission',
  'category' => 'getting_started',
  'weight' => 0,
  'summary' => 'Навіщо існує портал і яку роль відіграє громада.',
  'body' => '<p><strong>Місія:</strong> створити цифровий простір взаємодії громади Богуслава — місце, де мешканці спілкуються, допомагають одне одному, знаходять послуги, роботу, події та людей, які формують життя міста.</p>
<p><strong>Гасло:</strong> «Найрідніше місто у світі».</p>
<p><strong>Цінності:</strong> рідність, гордість, участь, відповідальність за місто, довіра, відкритість.</p>
<p><strong>Бачення:</strong> соціальна матриця міста — цифровий шар над реальним життям громади.</p>',
]);

$module_docs = [
  [
    'title' => 'Як користуватися порталом',
    'module' => 'getting_started',
    'category' => 'getting_started',
    'weight' => 1,
    'tip' => 1,
    'body' => '<p>Портал «Мій Богуслав» обʼєднує новини, оголошення, бізнес, роботу, події та карту міста.</p><ul><li>Головна — дашборд з усіма розділами</li><li>Пошук — знайдіть будь-що одним запитом</li><li>Кнопка «?» — контекстна довідка на кожній сторінці</li><li><a href="/dopomoga">Центр допомоги</a> — енциклопедія та FAQ</li></ul>',
  ],
  [
    'title' => 'Реєстрація та перший вхід',
    'module' => 'getting_started',
    'category' => 'registration',
    'weight' => 1,
    'faq' => 1,
    'body' => '<p>Зареєструйтесь через email або Facebook. Нові акаунти проходять перевірку адміністратором.</p><p>Після першого входу вас зустріне короткий тур по основних можливостях.</p>',
  ],
  [
    'title' => 'Довідка: Новини',
    'module' => 'news',
    'category' => 'news',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> міські новини, офіційні та авторські матеріали.</p><p><strong>Як знайти:</strong> розділ <a href="/novyny">Новини</a> або пошук.</p><p><strong>Створити:</strong> доступно редакторам. Коментуйте та діліться корисними матеріалами.</p>',
  ],
  [
    'title' => 'Довідка: Оголошення',
    'module' => 'listings',
    'category' => 'getting_started',
    'weight' => 2,
    'tip' => 1,
    'body' => '<p><strong>Призначення:</strong> продам, куплю, обміняю, віддам, послуги, нерухомість.</p><p><strong>Створити:</strong> кнопка «Додати» → Оголошення або Нерухомість.</p><p><strong>Поради:</strong> додайте якісні фото, вкажіть район, перевірте телефон. Нові оголошення проходять модерацію.</p>',
  ],
  [
    'title' => 'Довідка: Робота',
    'module' => 'jobs',
    'category' => 'jobs',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> вакансії, резюме, підробіток, сезонна робота.</p><p><strong>Створити:</strong> <a href="/node/add/job">Додати вакансію/резюме</a>.</p><p><strong>Поради:</strong> вкажіть тип (вакансія/резюме), категорію, оплату та контакт.</p>',
  ],
  [
    'title' => 'Довідка: Бізнес',
    'module' => 'business',
    'category' => 'business',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> каталог бізнесів міста з контактами, графіком та картою.</p><p><strong>Як знайти:</strong> <a href="/biznes">Бізнес</a> або карта.</p><p><strong>Поради:</strong> додайте геолокацію — бізнес зʼявиться на карті. Оновлюйте графік роботи.</p>',
  ],
  [
    'title' => 'Довідка: Події',
    'module' => 'events',
    'category' => 'events',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> календар міських подій.</p><p><strong>Як знайти:</strong> <a href="/podii">Події</a>.</p><p><strong>Створити:</strong> вкажіть дату, місце та опис. RSVP — у наступних версіях.</p>',
  ],
  [
    'title' => 'Довідка: Карта міста',
    'module' => 'map',
    'category' => 'map',
    'weight' => 1,
    'tip' => 1,
    'body' => '<p><strong>Призначення:</strong> інтерактивна карта бізнесів, подій та місць на OpenStreetMap.</p><p><strong>Відкрити:</strong> <a href="/karta">Карта</a>.</p><p>Додайте геоточку до бізнесу або події — обʼєкт зʼявиться на карті автоматично.</p>',
  ],
  [
    'title' => 'Довідка: Спільнота',
    'module' => 'community',
    'category' => 'community',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> питання, допомога, ідеї, обговорення.</p><p><strong>Розділ:</strong> <a href="/spilnota">Спільнота</a>.</p><p>Модуль розширюється у V1.5 — ініціативи, голосування, проблеми міста.</p>',
  ],
  [
    'title' => 'Довідка: Місто',
    'module' => 'city',
    'category' => 'getting_started',
    'weight' => 3,
    'body' => '<p><strong>Призначення:</strong> історичні, туристичні та цікаві місця Богуслава.</p><p><strong>Розділ:</strong> <a href="/misto">Місто</a>.</p>',
  ],
  [
    'title' => 'Довідка: Профіль',
    'module' => 'profile',
    'category' => 'profile',
    'weight' => 1,
    'body' => '<p><strong>Призначення:</strong> ваш публічний профіль мешканця.</p><p>Заповніть імʼя, район, короткий опис. Телефон прихований на публічній сторінці.</p>',
  ],
  [
    'title' => 'Довідка: Галерея',
    'module' => 'gallery',
    'category' => 'gallery',
    'weight' => 1,
    'body' => '<p>Фотографії міста, історичні знімки — модуль у розробці (V2.0).</p>',
  ],
  [
    'title' => 'Довідка: 360-місця',
    'module' => 'tour360',
    'category' => 'tour360',
    'weight' => 1,
    'body' => '<p>Панорами красивих місць та туристичних точок — заплановано у V2.0.</p>',
  ],
  [
    'title' => 'Репутація та довіра',
    'module' => 'reputation',
    'category' => 'reputation',
    'weight' => 1,
    'body' => '<p>Репутація формується через корисні публікації, участь в ініціативах та відсутність порушень. Бейджі — у V0.4.</p>',
  ],
  [
    'title' => 'Безпека та модерація',
    'module' => 'security',
    'category' => 'security',
    'weight' => 1,
    'faq' => 1,
    'body' => '<p>Контент проходить модерацію. Скарги та блокування порушників — у наступних версіях. Не публікуйте особисті дані інших людей без згоди.</p>',
  ],
  [
    'title' => 'Як додати оголошення?',
    'module' => 'faq',
    'category' => 'faq',
    'weight' => 1,
    'faq' => 1,
    'body' => '<p>Увійдіть в акаунт → кнопка «Додати» → Оголошення. Заповніть назву, категорію, фото та контакт. Натисніть «Надіслати на перевірку».</p>',
  ],
  [
    'title' => 'Чому мій акаунт на перевірці?',
    'module' => 'faq',
    'category' => 'faq',
    'weight' => 2,
    'faq' => 1,
    'body' => '<p>Нові користувачі (особливо через Facebook) проходять ручне схвалення адміністратором для захисту від спаму.</p>',
  ],
  [
    'title' => 'Де знайти довідку по розділу?',
    'module' => 'faq',
    'category' => 'faq',
    'weight' => 3,
    'faq' => 1,
    'tip' => 1,
    'body' => '<p>Натисніть зелену кнопку «?» у правому нижньому куті — відкриється довідка саме для поточного розділу.</p>',
  ],
];

foreach ($module_docs as $doc) {
  $create_doc($doc);
}

// Blocks.
$block_storage = $etm->getStorage('block');
$blocks = [
  'mybg_radix_contextualhelp' => [
    'plugin' => 'mybg_contextual_help',
    'region' => 'page_bottom',
    'weight' => 0,
    'settings' => ['id' => 'mybg_contextual_help', 'label' => 'Довідка', 'label_display' => '0', 'provider' => 'mybg_help'],
  ],
  'mybg_radix_tipofday' => [
    'plugin' => 'mybg_tip_of_day',
    'region' => 'content',
    'weight' => -5,
    'settings' => ['id' => 'mybg_tip_of_day', 'label' => 'Порада дня', 'label_display' => '0', 'provider' => 'mybg_help'],
    'visibility' => ['request_path' => ['id' => 'request_path', 'negate' => FALSE, 'pages' => '<front>']],
  ],
];

foreach ($blocks as $id => $info) {
  if ($block_storage->load($id)) {
    continue;
  }
  $data = [
    'id' => $id,
    'theme' => 'mybg_radix',
    'region' => $info['region'],
    'weight' => $info['weight'],
    'plugin' => $info['plugin'],
    'settings' => $info['settings'],
  ];
  if (!empty($info['visibility'])) {
    $data['visibility'] = $info['visibility'];
  }
  $block_storage->create($data)->save();
  print "Placed block: $id\n";
}

// Menu links.
$menu_links = [
  '/dopomoga' => 'Допомога',
  '/encyklopediya' => 'Енциклопедія',
];
$mls = $etm->getStorage('menu_link_content');
foreach ($menu_links as $path => $title) {
  if ($mls->loadByProperties(['menu_name' => 'main', 'title' => $title])) {
    continue;
  }
  $mls->create([
    'title' => $title,
    'menu_name' => 'main',
    'link' => ['uri' => 'internal:' . $path],
    'weight' => 50,
  ])->save();
  print "Menu: $title\n";
}

// Editor permissions for documentation.
foreach (['editor', 'moderator'] as $role_id) {
  $role = Role::load($role_id);
  if ($role) {
    foreach (['create documentation content', 'edit any documentation content', 'delete any documentation content'] as $perm) {
      $role->grantPermission($perm);
    }
    $role->save();
  }
}

drupal_flush_all_caches();
print "\n✓ Help Center setup complete.\n";
print "  /dopomoga — центр допомоги\n";
print "  /encyklopediya — енциклопедія\n";
print "  /dopomoga/poshuk — пошук по довідці\n";
print "  Run: drush cex -y\n";
