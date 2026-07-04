<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Latest 10 content items for homepage feed.
 *
 * @Block(
 *   id = "mybg_latest_feed",
 *   admin_label = @Translation("Останні публікації (10)"),
 *   category = @Translation("MyBG Matrix"),
 * )
 */
final class LatestFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Bundles shown in the public feed.
   */
  private const FEED_BUNDLES = [
    'news',
    'listing',
    'real_estate',
    'job',
    'organization',
    'event',
    'city_page',
    'article',
  ];

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = $this->loadItems(10);

    return [
      '#theme' => 'mybg_latest_feed',
      '#items' => $items,
      '#attached' => ['library' => ['mybg_matrix/city_dashboard']],
      '#cache' => ['tags' => ['node_list'], 'max-age' => 120],
    ];
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function loadItems(int $limit): array {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', self::FEED_BUNDLES, 'IN')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (!$nids) {
      return [];
    }

    $items = [];
    $url_generator = \Drupal::service('file_url_generator');
    foreach ($this->entityTypeManager->getStorage('node')->loadMultiple($nids) as $node) {
      $image = NULL;
      foreach (['field_news_image', 'field_images'] as $field) {
        if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
          $image = $node->get($field)->entity;
          break;
        }
      }
      $type_label = $node->type->entity ? $node->type->entity->label() : $node->bundle();
      $author = $node->getOwner();
      $items[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'type' => $type_label,
        'type_key' => $node->bundle(),
        'created' => $node->getCreatedTime(),
        'author' => $author ? $author->getDisplayName() : '',
        'image_url' => $image ? $url_generator->generateAbsoluteString($image->getFileUri()) : NULL,
      ];
    }
    return $items;
  }

}
