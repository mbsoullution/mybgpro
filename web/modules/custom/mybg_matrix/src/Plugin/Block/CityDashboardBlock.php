<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mybg_matrix\SectionHeroBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Homepage city dashboard hero block.
 *
 * @Block(
 *   id = "mybg_city_dashboard",
 *   admin_label = @Translation("Міський дашборд"),
 *   category = @Translation("MyBG Matrix"),
 * )
 */
final class CityDashboardBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly SectionHeroBuilder $heroBuilder,
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
      $container->get('mybg_matrix.section_hero_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return $this->heroBuilder->buildDashboardHero();
  }

}
