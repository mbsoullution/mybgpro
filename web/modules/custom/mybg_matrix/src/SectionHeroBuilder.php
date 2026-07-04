<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;

/**
 * Builds front-page hero and compact section page headers.
 */
final class SectionHeroBuilder {

  public function __construct(
    private readonly DashboardSettings $dashboardSettings,
    private readonly SectionRegistry $sectionRegistry,
    private readonly SectionSettings $sectionSettings,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly ThemeExtensionList $themeExtensionList,
  ) {}

  /**
   * Front page hero with global search.
   */
  public function buildDashboardHero(): array {
    $settings = $this->dashboardSettings->getSettings();

    return $this->buildFrontHero(
      search_action: Url::fromRoute('mybg_matrix.search')->toString(),
      search_placeholder: 'Що шукаєте в місті?',
      video_url: $settings['video_url'],
      poster_url: $settings['poster_url'],
    );
  }

  /**
   * Compact section page header (3 rows, not front-page hero).
   */
  public function buildSectionHero(array $section, string $section_key): array {
    $settings = $this->dashboardSettings->getSettings();

    $add_url = NULL;
    if ($this->sectionRegistry->canAdd($section) && !empty($section['add_url'])) {
      $add_url = Url::fromUserInput($section['add_url'])->toString();
    }

    $search_action = NULL;
    $search_placeholder = NULL;
    if ($this->sectionRegistry->hasSectionSearch($section)) {
      $search_action = $section['search_path'];
      $search_placeholder = $section['search_placeholder'] ?? 'Пошук…';
    }

    $nav_items = [];
    foreach ($this->sectionRegistry->getSectionNavItems() as $key => $item) {
      $nav_items[$key] = [
        'label' => $item['label'],
        'url' => $item['url']->toString(),
      ];
    }

    return [
      '#theme' => 'mybg_section_page_header',
      '#title' => $settings['title'],
      '#logo_url' => $this->getSiteLogoUrl(),
      '#poster_url' => $this->getSectionPosterUrl($section),
      '#nav_items' => $nav_items,
      '#links' => [
        'home' => Url::fromRoute('<front>')->toString(),
      ],
      '#show_search' => $search_action !== NULL,
      '#search_action' => $search_action,
      '#search_placeholder' => $search_placeholder,
      '#search_query' => trim((string) \Drupal::request()->query->get('q', '')),
      '#section_description' => $section['description'],
      '#add_url' => $add_url,
      '#add_label' => $section['add_label'] ?? '',
      '#section_key' => $section_key,
      '#active_link' => $this->sectionRegistry->getNavKeyForSection($section_key),
      '#attached' => [
        'library' => ['mybg_matrix/section_page_header', 'mybg_matrix/section_header'],
      ],
      '#cache' => [
        'tags' => [
          'config_pages:' . DashboardSettings::CONFIG_ID,
          'config_pages:' . SectionSettings::CONFIG_ID,
          'config_pages_list',
        ],
        'contexts' => ['user.permissions', 'user.roles', 'url.path', 'url.query_args:q'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Full-screen front page hero.
   */
  private function buildFrontHero(
    string $search_action,
    string $search_placeholder,
    ?string $poster_url = NULL,
    ?string $video_url = NULL,
  ): array {
    $settings = $this->dashboardSettings->getSettings();

    return [
      '#theme' => 'mybg_matrix_hero',
      '#title' => $settings['title'],
      '#tagline' => $settings['tagline'],
      '#slogan' => $settings['slogan'],
      '#logo_url' => $this->getSiteLogoUrl(),
      '#video_url' => $video_url,
      '#poster_url' => $poster_url ?? $settings['poster_url'],
      '#links' => $this->sectionRegistry->getQuickNavLinks(),
      '#show_search' => TRUE,
      '#search_action' => $search_action,
      '#search_placeholder' => $search_placeholder,
      '#search_query' => trim((string) \Drupal::request()->query->get('q', '')),
      '#attached' => [
        'library' => ['mybg_matrix/city_dashboard'],
      ],
      '#cache' => [
        'tags' => ['config_pages:' . DashboardSettings::CONFIG_ID, 'config_pages_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Section-specific background image URL (editable in admin).
   */
  private function getSectionPosterUrl(array $section): string {
    $section_id = $section['id'] ?? '';
    $fallback = $section['poster'] ?? 'hero-poster.jpg';
    if ($section_id) {
      return $this->sectionSettings->getPosterUrl($section_id, $fallback);
    }

    $theme_path = $this->themeExtensionList->getPath('mybg_radix');
    return '/' . $theme_path . '/assets/branding/' . $fallback;
  }

  /**
   * Site logo URL from theme settings or bundled asset.
   */
  private function getSiteLogoUrl(): string {
    $logo_path = $this->configFactory->get('mybg_radix.settings')->get('logo.path');
    if ($logo_path) {
      return $this->fileUrlGenerator->generateString($logo_path);
    }

    $theme_path = $this->themeExtensionList->getPath('mybg_radix');
    return '/' . $theme_path . '/assets/branding/logo.png';
  }

}
