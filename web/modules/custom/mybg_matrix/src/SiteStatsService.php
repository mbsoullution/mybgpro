<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Aggregates public portal statistics for the sticky footer bar.
 */
final class SiteStatsService {

  private const CACHE_CID = 'mybg_matrix.site_stats';

  private const CACHE_TAGS = ['node_list', 'user_list', 'comment_list'];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database,
    private readonly StateInterface $state,
    private readonly CacheBackendInterface $cache,
  ) {}

  /**
   * @return array<int, array{key: string, label: string, value: int, icon: string}>
   */
  public function getItems(): array {
    if ($cached = $this->cache->get(self::CACHE_CID)) {
      return $cached->data;
    }

    $items = [
      $this->item('visits', 'Відвідування', $this->countVisits(), 'door-open'),
      $this->item('users', 'Користувачі', $this->countUsers(), 'people'),
      $this->item('listings', 'Оголошення', $this->countListings(), 'megaphone'),
      $this->item('comments', 'Коментарі', $this->countComments(), 'chat'),
      $this->item('shares', 'Поширення', $this->countShares(), 'share'),
    ];

    $this->cache->set(self::CACHE_CID, $items, time() + 300, self::CACHE_TAGS);
    return $items;
  }

  /**
   * @return array{key: string, label: string, value: int, icon: string}
   */
  private function item(string $key, string $label, int $value, string $icon): array {
    return [
      'key' => $key,
      'label' => $label,
      'value' => $value,
      'icon' => $icon,
    ];
  }

  private function countListings(): int {
    return $this->countNodes(['listing', 'real_estate']);
  }

  /**
   * @param list<string> $bundles
   */
  private function countNodes(array $bundles): int {
    return (int) $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $bundles, 'IN')
      ->condition('status', 1)
      ->count()
      ->execute();
  }

  private function countUsers(): int {
    return (int) $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', 0, '>')
      ->condition('status', 1)
      ->count()
      ->execute();
  }

  private function countComments(): int {
    if (!$this->entityTypeManager->hasDefinition('comment')) {
      return 0;
    }
    return (int) $this->entityTypeManager->getStorage('comment')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->count()
      ->execute();
  }

  private function countVisits(): int {
    return (int) $this->state->get('mybg_matrix.stats.visits', 0);
  }

  private function countShares(): int {
    return (int) $this->state->get('mybg_matrix.stats.shares', 0);
  }

  /**
   * Records a page view and unique visit (once per session).
   */
  public function trackRequest(bool $isUniqueVisit): void {
    $this->state->set(
      'mybg_matrix.stats.page_views',
      (int) $this->state->get('mybg_matrix.stats.page_views', 0) + 1,
    );
    if ($isUniqueVisit) {
      $this->state->set(
        'mybg_matrix.stats.visits',
        (int) $this->state->get('mybg_matrix.stats.visits', 0) + 1,
      );
    }
    $this->cache->delete(self::CACHE_CID);
  }

}
