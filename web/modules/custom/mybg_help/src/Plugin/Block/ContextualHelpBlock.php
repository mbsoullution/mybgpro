<?php

declare(strict_types=1);

namespace Drupal\mybg_help\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\mybg_help\DocumentationRepository;
use Drupal\mybg_help\HelpContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Floating contextual help button (?).
 *
 * @Block(
 *   id = "mybg_contextual_help",
 *   admin_label = @Translation("Контекстна довідка (?)"),
 *   category = @Translation("MyBG Help"),
 * )
 */
final class ContextualHelpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly HelpContext $helpContext,
    private readonly DocumentationRepository $docs,
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
      $container->get('mybg_help.context'),
      $container->get('mybg_help.documentation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $module = $this->helpContext->getCurrentModuleKey();
    if (!$module) {
      if (!\Drupal::service('path.matcher')->isFrontPage()) {
        return [];
      }
      $url = Url::fromRoute('mybg_help.center');
      $label = $this->t('Портал');
    }
    else {
      $doc = $this->docs->getModuleDoc($module);
      $url = $doc
        ? $doc->toUrl()
        : Url::fromRoute('mybg_help.module_help', ['module' => $module]);
      $label = $this->helpContext->getModuleLabel($module);
    }

    return [
      '#theme' => 'mybg_help_contextual_button',
      '#label' => $label,
      '#url' => $url->toString(),
      '#attached' => ['library' => ['mybg_help/help_ui']],
      '#cache' => ['contexts' => ['route']],
    ];
  }

}
