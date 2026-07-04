<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Section metadata: descriptions and role-gated "add content" actions.
 */
final class SectionRegistry {

  use StringTranslationTrait;

  /**
   * View machine name => section definition.
   */
  private const VIEW_SECTIONS = [
    'city_news' => [
      'id' => 'news',
      'label' => 'Новини',
      'description' => 'Міські новини, офіційні повідомлення та авторські матеріали про життя Богуслава.',
      'content_bundles' => ['news'],
      'add_label' => 'Додати новину',
      'add_url' => '/node/add/news',
      'permissions' => ['create news content'],
      'search_path' => '/novyny',
      'search_placeholder' => 'Пошук новин…',
      'poster' => 'sections/news.jpg',
    ],
    'city_listings' => [
      'id' => 'listings',
      'label' => 'Оголошення',
      'description' => 'Оголошення мешканців: продам, куплю, обміняю, віддам, послуги та нерухомість.',
      'content_bundles' => ['listing', 'real_estate'],
      'add_label' => 'Додати оголошення',
      'add_url' => '/node/add/listing',
      'permissions' => ['create listing content'],
      'search_path' => '/ogoloshennya',
      'search_placeholder' => 'Пошук оголошень…',
      'poster' => 'sections/listings.jpg',
    ],
    'city_jobs' => [
      'id' => 'jobs',
      'label' => 'Робота',
      'description' => 'Вакансії від роботодавців, резюме мешканців, підробіток та сезонна робота.',
      'content_bundles' => ['job'],
      'add_label' => 'Додати вакансію / резюме',
      'add_url' => '/node/add/job',
      'permissions' => ['create job content'],
      'search_path' => '/robota',
      'search_placeholder' => 'Пошук вакансій…',
      'poster' => 'sections/jobs.jpg',
    ],
    'city_businesses' => [
      'id' => 'business',
      'label' => 'Бізнес',
      'description' => 'Каталог бізнесів міста: контакти, графік роботи, опис послуг та точка на карті.',
      'content_bundles' => ['organization'],
      'add_label' => 'Додати бізнес',
      'add_url' => '/node/add/organization',
      'permissions' => ['create organization content'],
      'search_path' => '/biznes',
      'search_placeholder' => 'Пошук бізнесу…',
      'poster' => 'sections/business.jpg',
    ],
    'city_events' => [
      'id' => 'events',
      'label' => 'Події',
      'description' => 'Календар міських подій: концерти, ярмарки, зустрічі та заходи громади.',
      'content_bundles' => ['event'],
      'add_label' => 'Додати подію',
      'add_url' => '/node/add/event',
      'permissions' => ['create event content'],
      'search_path' => '/podii',
      'search_placeholder' => 'Пошук подій…',
      'poster' => 'sections/events.jpg',
    ],
    'city_community' => [
      'id' => 'community',
      'label' => 'Спільнота',
      'description' => 'Питання, допомога, ідеї та обговорення — голос мешканців і спільні ініціативи.',
      'content_bundles' => ['article'],
      'add_label' => 'Додати матеріал',
      'add_url' => '/node/add/article',
      'permissions' => ['create article content'],
      'search_path' => '/spilnota',
      'search_placeholder' => 'Пошук у спільноті…',
      'poster' => 'sections/community.jpg',
    ],
    'city_places' => [
      'id' => 'city',
      'label' => 'Місто',
      'description' => 'Історичні, туристичні та цікаві місця Богуслава — довідник міста.',
      'content_bundles' => ['city_page'],
      'add_label' => 'Додати місце',
      'add_url' => '/node/add/city_page',
      'permissions' => ['create city_page content'],
      'search_path' => '/misto',
      'search_placeholder' => 'Пошук місць…',
      'poster' => 'sections/city.jpg',
    ],
  ];

  /**
   * Route name => section definition (non-Views pages).
   */
  private const ROUTE_SECTIONS = [
    'mybg_matrix.map' => [
      'id' => 'map',
      'label' => 'Карта',
      'description' => 'Інтерактивна карта бізнесів, подій та місць на OpenStreetMap. Додайте геоточку до бізнесу — і він зʼявиться тут автоматично.',
      'content_bundles' => ['organization', 'event', 'city_page'],
      'add_label' => 'Додати бізнес на карту',
      'add_url' => '/node/add/organization',
      'roles' => ['business_owner'],
      'poster' => 'sections/map.jpg',
    ],
  ];

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Resolve section for the current route, if any.
   *
   * @return array{key: string, section: array}|null
   */
  public function resolveCurrentSection(): ?array {
    $route_name = $this->routeMatch->getRouteName() ?? '';
    if (str_starts_with($route_name, 'view.')) {
      $parts = explode('.', $route_name);
      $view_id = $parts[1] ?? '';
      $section = $this->getSectionByViewId($view_id);
      if ($section) {
        return ['key' => $view_id, 'section' => $section];
      }
    }

    $section = $this->getSectionByRoute($route_name);
    if ($section) {
      return ['key' => $route_name, 'section' => $section];
    }

    return NULL;
  }

  /**
   * Navigation for section page header (includes Головна).
   *
   * @return array<string, array{label: string, url: \Drupal\Core\Url}>
   */
  public function getSectionNavItems(): array {
    return [
      'home' => ['label' => 'Головна', 'url' => Url::fromRoute('<front>')],
      'news' => ['label' => 'Новини', 'url' => Url::fromUserInput('/novyny')],
      'listings' => ['label' => 'Оголошення', 'url' => Url::fromUserInput('/ogoloshennya')],
      'jobs' => ['label' => 'Робота', 'url' => Url::fromUserInput('/robota')],
      'businesses' => ['label' => 'Бізнес', 'url' => Url::fromUserInput('/biznes')],
      'events' => ['label' => 'Події', 'url' => Url::fromUserInput('/podii')],
      'map' => ['label' => 'Карта', 'url' => Url::fromRoute('mybg_matrix.map')],
      'community' => ['label' => 'Спільнота', 'url' => Url::fromUserInput('/spilnota')],
      'city' => ['label' => 'Місто', 'url' => Url::fromUserInput('/misto')],
    ];
  }

  /**
   * All portal sections keyed by machine id (for Layout Builder landing pages).
   *
   * @return array<string, array>
   */
  public function getAllSectionsById(): array {
    $sections = [];
    foreach (array_merge(self::VIEW_SECTIONS, self::ROUTE_SECTIONS) as $section) {
      if (!empty($section['id'])) {
        $sections[$section['id']] = $section;
      }
    }
    return $sections;
  }

  /**
   * Content type bundles used in a section listing.
   */
  public function getContentBundles(array $section): array {
    return $section['content_bundles'] ?? [];
  }

  /**
   * Section definition by portal section id (news, listings, …).
   */
  public function getSectionById(string $section_id): ?array {
    foreach ($this->getAllSectionsById() as $id => $section) {
      if ($id === $section_id) {
        return $section;
      }
    }
    return NULL;
  }

  /**
   * Quick navigation links for front-page hero.
   */
  public function getQuickNavLinks(): array {
    return [
      'news' => Url::fromUserInput('/novyny'),
      'listings' => Url::fromUserInput('/ogoloshennya'),
      'jobs' => Url::fromUserInput('/robota'),
      'businesses' => Url::fromUserInput('/biznes'),
      'events' => Url::fromUserInput('/podii'),
      'map' => Url::fromRoute('mybg_matrix.map'),
      'community' => Url::fromUserInput('/spilnota'),
      'city' => Url::fromUserInput('/misto'),
      'search' => Url::fromRoute('mybg_matrix.search'),
    ];
  }

  /**
   * Maps section/view key to quick-nav link key.
   */
  public function getNavKeyForSection(string $section_key): ?string {
    return match ($section_key) {
      'city_news' => 'news',
      'city_listings' => 'listings',
      'city_jobs' => 'jobs',
      'city_businesses' => 'businesses',
      'city_events' => 'events',
      'city_community' => 'community',
      'city_places' => 'city',
      'mybg_matrix.map' => 'map',
      default => NULL,
    };
  }

  /**
   * Whether the current request is a portal section page.
   */
  public function isSectionPage(): bool {
    return $this->resolveCurrentSection() !== NULL;
  }

  /**
   * Section definition for a Views ID, if any.
   */
  public function getSectionByViewId(string $viewId): ?array {
    return self::VIEW_SECTIONS[$viewId] ?? NULL;
  }

  /**
   * Section definition for a route name, if any.
   */
  public function getSectionByRoute(string $routeName): ?array {
    return self::ROUTE_SECTIONS[$routeName] ?? NULL;
  }

  /**
   * Poster asset path relative to theme assets/branding/.
   */
  public function getSectionPosterPath(array $section): string {
    return $section['poster'] ?? 'hero-poster.jpg';
  }

  /**
   * Whether section supports in-page search.
   */
  public function hasSectionSearch(array $section): bool {
    return !empty($section['search_path']);
  }

  public function canAdd(array $section): bool {
    if ($this->currentUser->isAnonymous()) {
      return FALSE;
    }

    if (!empty($section['permissions'])) {
      foreach ($section['permissions'] as $permission) {
        if ($this->currentUser->hasPermission($permission)) {
          return TRUE;
        }
      }
    }

    if (!empty($section['roles'])) {
      $user_roles = $this->currentUser->getRoles();
      foreach ($section['roles'] as $role) {
        if (in_array($role, $user_roles, TRUE)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Render array for the section intro block.
   */
  public function buildHeader(array $section, ?string $section_key = NULL): array {
    $add_url = NULL;
    if ($this->canAdd($section) && !empty($section['add_url'])) {
      $add_url = Url::fromUserInput($section['add_url'])->toString();
    }

    return [
      '#theme' => 'mybg_section_header',
      '#description' => $section['description'],
      '#add_url' => $add_url,
      '#add_label' => $section['add_label'] ?? '',
      '#section_key' => $section_key ?? '',
      '#attached' => [
        'library' => ['mybg_matrix/section_header'],
      ],
      '#cache' => [
        'contexts' => ['user.permissions', 'user.roles'],
      ],
    ];
  }

}
