<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mybg_matrix\SiteStatsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sticky bottom bar with portal statistics.
 *
 * @Block(
 *   id = "mybg_site_stats_bar",
 *   admin_label = @Translation("Статистика порталу (нижня смуга)"),
 *   category = @Translation("MyBG Matrix"),
 * )
 */
final class SiteStatsBarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly SiteStatsService $siteStats,
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
      $container->get('mybg_matrix.site_stats'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'mybg_site_stats_bar',
      '#items' => $this->siteStats->getItems(),
      '#attached' => ['library' => ['mybg_matrix/site_stats_bar']],
      '#cache' => [
        'tags' => ['node_list', 'user_list', 'comment_list'],
        'max-age' => 300,
      ],
    ];
  }

}
