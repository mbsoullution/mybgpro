<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;

/**
 * Loads editable dashboard hero settings from Config Pages.
 */
final class DashboardSettings {

  public const CONFIG_ID = 'dashboard_settings';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Returns dashboard settings with sensible defaults.
   */
  public function getSettings(): array {
    $defaults = [
      'title' => 'Мій Богуслав',
      'tagline' => 'Соціальна матриця міста',
      'slogan' => $this->configFactory->get('system.site')->get('slogan') ?: 'Найрідніше місто у світі',
      'video_url' => NULL,
      'poster_url' => '/themes/custom/mybg_radix/assets/branding/hero-poster.jpg',
    ];

    if (!\Drupal::moduleHandler()->moduleExists('config_pages')) {
      return $defaults;
    }

    $entity = $this->loadEntity();
    if (!$entity) {
      return $defaults;
    }

    if ($entity->hasField('field_dash_title') && !$entity->get('field_dash_title')->isEmpty()) {
      $defaults['title'] = $entity->get('field_dash_title')->value;
    }
    if ($entity->hasField('field_dash_tagline') && !$entity->get('field_dash_tagline')->isEmpty()) {
      $defaults['tagline'] = $entity->get('field_dash_tagline')->value;
    }
    if ($entity->hasField('field_dash_slogan') && !$entity->get('field_dash_slogan')->isEmpty()) {
      $defaults['slogan'] = $entity->get('field_dash_slogan')->value;
    }
    if ($entity->hasField('field_dash_video') && !$entity->get('field_dash_video')->isEmpty()) {
      $file = $entity->get('field_dash_video')->entity;
      if ($file instanceof FileInterface) {
        $defaults['video_url'] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
    if ($entity->hasField('field_dash_poster') && !$entity->get('field_dash_poster')->isEmpty()) {
      $file = $entity->get('field_dash_poster')->entity;
      if ($file instanceof FileInterface) {
        $defaults['poster_url'] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    return $defaults;
  }

  /**
   * Admin edit URL for dashboard settings.
   */
  public function getEditUrl(): ?string {
    if (!\Drupal::moduleHandler()->moduleExists('config_pages')) {
      return NULL;
    }
    $entity = $this->loadEntity();
    if (!$entity) {
      return '/admin/config/mybg/dashboard';
    }
    return '/admin/config/mybg/dashboard';
  }

  private function loadEntity(): ?object {
    $storage = $this->entityTypeManager->getStorage('config_pages');
    $entities = $storage->loadByProperties(['type' => self::CONFIG_ID]);
    return $entities ? reset($entities) : NULL;
  }

}
