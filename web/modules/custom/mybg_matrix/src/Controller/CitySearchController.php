<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Global search page backed by Search API.
 */
final class CitySearchController extends ControllerBase {

  /**
   * Simple search results page.
   */
  public function searchPage(Request $request): array {
    $query = trim((string) $request->query->get('q', ''));
    $results = [];

    if ($query !== '' && $this->moduleHandler()->moduleExists('search_api')) {
      try {
        $index = $this->entityTypeManager()->getStorage('search_api_index')->load('city_content_index');
        if ($index) {
          /** @var \Drupal\search_api\Entity\Index $index */
          $search_query = $index->query();
          $search_query->keys($query);
          $search_query->range(0, 20);
          $result_set = $search_query->execute();
          foreach ($result_set->getResultItems() as $item) {
            $entity = $item->getOriginalObject()->getValue();
            if ($entity instanceof \Drupal\node\NodeInterface) {
              $results[] = [
                'title' => $entity->label(),
                'url' => $entity->toUrl()->toString(),
                'type' => $entity->type->entity->label(),
                'changed' => $entity->getChangedTime(),
              ];
            }
          }
        }
      }
      catch (\Exception) {
        // Fallback below.
      }
    }

    if ($query !== '' && empty($results)) {
      $nids = $this->entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('title', '%' . $query . '%', 'LIKE')
        ->sort('changed', 'DESC')
        ->range(0, 20)
        ->execute();
      if ($nids) {
        $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($nids);
        foreach ($nodes as $node) {
          $results[] = [
            'title' => $node->label(),
            'url' => $node->toUrl()->toString(),
            'type' => $node->type->entity->label(),
            'changed' => $node->getChangedTime(),
          ];
        }
      }
    }

    return [
      '#theme' => 'mybg_city_search',
      '#query' => $query,
      '#results' => $results,
      '#cache' => [
        'contexts' => ['url.query_args:q'],
      ],
    ];
  }

}
