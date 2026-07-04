<?php

declare(strict_types=1);

namespace Drupal\mybg_help;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Maps current page context to a portal module key for contextual help.
 */
final class HelpContext {

  /**
   * Module keys used in documentation.field_doc_module.
   */
  public const MODULES = [
    'getting_started' => 'Початок роботи',
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
  ];

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Detect module key from current route/path.
   */
  public function getCurrentModuleKey(): ?string {
    $route = $this->routeMatch->getRouteName() ?? '';

    $map = [
      'mybg_matrix.map' => 'map',
      'mybg_help.center' => 'faq',
      'mybg_help.encyclopedia' => 'getting_started',
      'entity.user.canonical' => 'profile',
      'entity.user.edit_form' => 'profile',
      'view.city_news' => 'news',
      'view.city_listings' => 'listings',
      'view.city_jobs' => 'jobs',
      'view.city_businesses' => 'business',
      'view.city_events' => 'events',
      'view.city_community' => 'community',
      'view.city_places' => 'city',
    ];

    if (isset($map[$route])) {
      return $map[$route];
    }

    if (str_starts_with($route, 'entity.node.')) {
      $node = $this->routeMatch->getParameter('node');
      if (is_object($node)) {
        return $this->bundleToModule($node->bundle());
      }
    }

    if (str_starts_with($route, 'entity.node.add_form')) {
      $type = $this->routeMatch->getParameter('node_type');
      $bundle = is_object($type) ? $type->id() : (string) $type;
      return $this->bundleToModule($bundle);
    }

    $path = \Drupal::service('path.current')->getPath();
    $prefixes = [
      '/novyny' => 'news',
      '/ogoloshennya' => 'listings',
      '/robota' => 'jobs',
      '/biznes' => 'business',
      '/podii' => 'events',
      '/spilnota' => 'community',
      '/misto' => 'city',
      '/karta' => 'map',
      '/encyklopediya' => 'getting_started',
      '/dopomoga' => 'faq',
      '/user' => 'profile',
    ];
    foreach ($prefixes as $prefix => $key) {
      if (str_starts_with($path, $prefix)) {
        return $key;
      }
    }

    return NULL;
  }

  /**
   * Human label for a module key.
   */
  public function getModuleLabel(?string $key): string {
    if ($key && isset(self::MODULES[$key])) {
      return self::MODULES[$key];
    }
    return 'Портал';
  }

  /**
   * Maps node bundle to help module key.
   */
  public function bundleToModule(string $bundle): ?string {
    return match ($bundle) {
      'news' => 'news',
      'listing', 'real_estate' => 'listings',
      'job' => 'jobs',
      'organization' => 'business',
      'event' => 'events',
      'city_page' => 'city',
      'article' => 'community',
      'documentation' => 'getting_started',
      default => NULL,
    };
  }

}
