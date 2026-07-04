<?php

declare(strict_types=1);

namespace Drupal\mybg_help\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mybg_help\DocumentationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Random tip of the day from documentation.
 *
 * @Block(
 *   id = "mybg_tip_of_day",
 *   admin_label = @Translation("Порада дня"),
 *   category = @Translation("MyBG Help"),
 * )
 */
final class TipOfDayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
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
      $container->get('mybg_help.documentation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $tip = $this->docs->getRandomTip();
    if (!$tip) {
      return [];
    }

    $text = $tip->hasField('body') && !$tip->get('body')->isEmpty()
      ? text_summary($tip->get('body')->value, NULL, 200)
      : $tip->label();

    return [
      '#theme' => 'mybg_help_tip',
      '#title' => $tip->label(),
      '#text' => $text,
      '#url' => $tip->toUrl()->toString(),
      '#attached' => ['library' => ['mybg_help/help_ui']],
      '#cache' => ['max-age' => 3600, 'tags' => ['node_list']],
    ];
  }

}
