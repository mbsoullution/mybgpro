<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mybg_matrix\CityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interactive city map page.
 */
final class CityMapController extends ControllerBase {

  public function __construct(
    private readonly CityContext $cityContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('mybg_matrix.city_context'),
    );
  }

  /**
   * Map page with geolocated nodes.
   */
  public function mapPage(): array {
    $markers = [];
    $bundles = ['organization', 'event', 'city_page'];

    $nids = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $bundles, 'IN')
      ->condition('status', 1)
      ->exists('field_geolocation')
      ->execute();

    if ($nids) {
      $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($nids);
      foreach ($nodes as $node) {
        if ($node->get('field_geolocation')->isEmpty()) {
          continue;
        }
        $value = $node->get('field_geolocation')->first()->getValue();
        $lat = (float) ($value['lat'] ?? 0);
        $lng = (float) ($value['lon'] ?? $value['lng'] ?? 0);
        if (!$lat && !$lng) {
          continue;
        }
        $markers[] = [
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
          'type' => $node->bundle(),
          'lat' => $lat,
          'lng' => $lng,
        ];
      }
    }

    // Boguslav center fallback.
    $center = ['lat' => 49.5467, 'lng' => 30.8744];

    return [
      '#theme' => 'mybg_city_map',
      '#city_label' => $this->cityContext->getCityLabel(),
      '#markers' => $markers,
      '#center' => $center,
      '#attached' => [
        'library' => ['mybg_matrix/city_map'],
        'drupalSettings' => [
          'mybgMatrixMap' => [
            'center' => $center,
            'markers' => $markers,
            'tileUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; OpenStreetMap',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => ['languages:language_interface'],
      ],
    ];
  }

}
