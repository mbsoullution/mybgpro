<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\mybg_matrix\SectionHeroBuilder;
use Drupal\mybg_matrix\SectionRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Section page hero with dashboard background.
 *
 * @Block(
 *   id = "mybg_section_page_hero",
 *   admin_label = @Translation("Hero розділу"),
 *   category = @Translation("MyBG Matrix"),
 * )
 */
final class SectionPageHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly SectionRegistry $sectionRegistry,
    private readonly SectionHeroBuilder $heroBuilder,
    private readonly RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mybg_matrix.section_registry'),
      $container->get('mybg_matrix.section_hero_builder'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $resolved = $this->sectionRegistry->resolveCurrentSection();
    if (!$resolved) {
      return [
        '#cache' => [
          'contexts' => ['route'],
          'max-age' => Cache::PERMANENT,
        ],
      ];
    }

    return $this->heroBuilder->buildSectionHero($resolved['section'], $resolved['key']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
