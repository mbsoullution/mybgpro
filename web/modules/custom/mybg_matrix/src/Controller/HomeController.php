<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Front page controller — content comes from blocks only.
 */
final class HomeController extends ControllerBase {

  /**
   * Empty main content; dashboard and feed blocks fill the page.
   */
  public function front(): array {
    return [
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:system.site'],
      ],
    ];
  }

}
