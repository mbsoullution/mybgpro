<?php

declare(strict_types=1);

namespace Drupal\mybg_matrix;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;

/**
 * Editable section background images from Config Pages.
 */
final class SectionSettings {

  public const CONFIG_ID = 'section_backgrounds';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly ThemeExtensionList $themeExtensionList,
  ) {}

  /**
   * Poster URL for a portal section id (news, listings, …).
   */
  public function getPosterUrl(string $section_id, string $fallback_relative = 'hero-poster.jpg'): string {
    $field_name = $this->getPosterFieldName($section_id);

    if (\Drupal::moduleHandler()->moduleExists('config_pages')) {
      $entity = $this->loadEntity();
      if ($entity && $entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $file = $entity->get($field_name)->entity;
        if ($file instanceof FileInterface) {
          return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
    }

    return $this->getThemePosterUrl($fallback_relative);
  }

  /**
   * Image field machine name for a section id.
   */
  public function getPosterFieldName(string $section_id): string {
    return 'field_bg_' . str_replace('-', '_', $section_id);
  }

  /**
   * Admin edit path.
   */
  public function getEditUrl(): string {
    return '/admin/config/mybg/section-backgrounds';
  }

  private function loadEntity(): ?object {
    $entities = $this->entityTypeManager->getStorage('config_pages')
      ->loadByProperties(['type' => self::CONFIG_ID]);
    return $entities ? reset($entities) : NULL;
  }

  private function getThemePosterUrl(string $relative): string {
    $theme_path = $this->themeExtensionList->getPath('mybg_radix');
    $full_path = DRUPAL_ROOT . '/' . $theme_path . '/assets/branding/' . $relative;

    if (is_readable($full_path)) {
      return '/' . $theme_path . '/assets/branding/' . $relative;
    }

    return '/' . $theme_path . '/assets/branding/hero-poster.jpg';
  }

}
