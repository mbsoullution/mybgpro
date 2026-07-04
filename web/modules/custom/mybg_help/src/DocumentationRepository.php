<?php

declare(strict_types=1);

namespace Drupal\mybg_help;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Loads documentation nodes for help UI.
 */
final class DocumentationRepository {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Primary help article for a portal module.
   */
  public function getModuleDoc(string $module_key): ?NodeInterface {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'documentation')
      ->condition('status', 1)
      ->condition('field_doc_module', $module_key)
      ->sort('field_doc_weight', 'ASC')
      ->range(0, 1)
      ->execute();

    if (!$nids) {
      return NULL;
    }
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Random tip-of-the-day article.
   */
  public function getRandomTip(): ?NodeInterface {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'documentation')
      ->condition('status', 1)
      ->condition('field_doc_is_tip', 1)
      ->execute();

    if (!$nids) {
      return NULL;
    }
    $nid = $nids[array_rand($nids)];
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * FAQ items.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function getFaqItems(int $limit = 10): array {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'documentation')
      ->condition('status', 1)
      ->condition('field_doc_faq', 1)
      ->sort('field_doc_weight', 'ASC')
      ->range(0, $limit)
      ->execute();

    if (!$nids) {
      return [];
    }
    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }

  /**
   * Mission page documentation node.
   */
  public function getMissionDoc(): ?NodeInterface {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'documentation')
      ->condition('status', 1)
      ->condition('field_doc_module', 'mission')
      ->range(0, 1)
      ->execute();

    if (!$nids) {
      return NULL;
    }
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Encyclopedia tree grouped by category term.
   */
  public function getEncyclopediaTree(): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree('help_category', 0, NULL, TRUE);

    $tree = [];
    foreach ($terms as $term) {
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'documentation')
        ->condition('status', 1)
        ->condition('field_doc_category', $term->id())
        ->sort('field_doc_weight', 'ASC')
        ->execute();

      if (!$nids) {
        continue;
      }
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      $items = [];
      foreach ($nodes as $node) {
        $items[] = [
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
          'summary' => $node->hasField('body') && !$node->get('body')->isEmpty()
            ? text_summary($node->get('body')->value, NULL, 120)
            : '',
        ];
      }
      $tree[] = [
        'category' => $term->label(),
        'items' => $items,
      ];
    }
    return $tree;
  }

  /**
   * Search documentation via Search API or fallback.
   */
  public function search(string $query, int $limit = 20): array {
    $results = [];
    if ($query === '') {
      return $results;
    }

    if (\Drupal::moduleHandler()->moduleExists('search_api')) {
      try {
        $index = $this->entityTypeManager->getStorage('search_api_index')->load('help_docs_index');
        if ($index) {
          $search = $index->query();
          $search->keys($query);
          $search->range(0, $limit);
          foreach ($search->execute()->getResultItems() as $item) {
            $entity = $item->getOriginalObject()->getValue();
            if ($entity instanceof NodeInterface) {
              $results[] = [
                'title' => $entity->label(),
                'url' => $entity->toUrl()->toString(),
                'summary' => $entity->hasField('body') && !$entity->get('body')->isEmpty()
                  ? text_summary($entity->get('body')->value, NULL, 160)
                  : '',
              ];
            }
          }
          return $results;
        }
      }
      catch (\Exception) {
        // Fallback below.
      }
    }

    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'documentation')
      ->condition('status', 1)
      ->condition('title', '%' . $query . '%', 'LIKE')
      ->range(0, $limit)
      ->execute();

    foreach ($this->entityTypeManager->getStorage('node')->loadMultiple($nids) as $node) {
      $results[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'summary' => '',
      ];
    }
    return $results;
  }

}
