<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Resolves the active city (default: Богуслав).
 */
final class CityContext {

  public const DEFAULT_CITY_TITLE = 'Богуслав';

  public const DEFAULT_CITY_SLUG = 'boguslav';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the default city node, creating it when missing.
   */
  public function getDefaultCity(): ?NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'city')
      ->condition('status', 1)
      ->sort('nid', 'ASC')
      ->range(0, 1)
      ->execute();

    if ($ids) {
      $city = $storage->load(reset($ids));
      return $city instanceof NodeInterface ? $city : NULL;
    }

    return NULL;
  }

  /**
   * Returns configured city title for UI.
   */
  public function getCityLabel(): string {
    $city = $this->getDefaultCity();
    return $city ? $city->label() : self::DEFAULT_CITY_TITLE;
  }

  /**
   * Site slogan from system.site config.
   */
  public function getSlogan(): string {
    return (string) $this->configFactory->get('system.site')->get('slogan');
  }

}
