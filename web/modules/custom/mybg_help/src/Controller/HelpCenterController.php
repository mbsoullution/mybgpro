<?php

declare(strict_types=1);

namespace Drupal\mybg_help\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mybg_help\DocumentationRepository;
use Drupal\mybg_help\HelpContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Help Center and Encyclopedia pages.
 */
final class HelpCenterController extends ControllerBase {

  public function __construct(
    private readonly HelpContext $helpContext,
    private readonly DocumentationRepository $docs,
    private readonly CsrfTokenGenerator $csrfToken,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('mybg_help.context'),
      $container->get('mybg_help.documentation'),
      $container->get('csrf_token'),
    );
  }

  /**
   * Main help center landing.
   */
  public function center(): array {
    $faq = [];
    foreach ($this->docs->getFaqItems(8) as $node) {
      $faq[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
      ];
    }

    return [
      '#theme' => 'mybg_help_center',
      '#faq' => $faq,
      '#encyclopedia_url' => '/encyklopediya',
      '#mission_url' => '/misiya',
      '#roadmap_url' => '/roadmap',
      '#search_url' => '/dopomoga/poshuk',
      '#modules' => HelpContext::MODULES,
      '#attached' => ['library' => ['mybg_help/help_ui']],
    ];
  }

  /**
   * Encyclopedia index with category tree.
   */
  public function encyclopedia(): array {
    return [
      '#theme' => 'mybg_help_encyclopedia',
      '#tree' => $this->docs->getEncyclopediaTree(),
      '#search_url' => '/dopomoga/poshuk',
      '#attached' => ['library' => ['mybg_help/help_ui']],
    ];
  }

  /**
   * Module-specific help — redirect to primary doc or show fallback.
   */
  public function moduleHelp(string $module): array|RedirectResponse {
    $doc = $this->docs->getModuleDoc($module);
    if ($doc) {
      return new RedirectResponse($doc->toUrl()->toString());
    }

    return [
      '#theme' => 'mybg_help_fallback',
      '#module_label' => $this->helpContext->getModuleLabel($module),
      '#center_url' => '/dopomoga',
      '#encyclopedia_url' => '/encyklopediya',
    ];
  }

  /**
   * Documentation search page.
   */
  public function docSearch(Request $request): array {
    $query = trim((string) $request->query->get('q', ''));
    return [
      '#theme' => 'mybg_help_search',
      '#query' => $query,
      '#results' => $this->docs->search($query),
      '#attached' => ['library' => ['mybg_help/help_ui']],
      '#cache' => ['contexts' => ['url.query_args:q']],
    ];
  }

  /**
   * Public product roadmap.
   */
  public function roadmap(): array {
    return [
      '#theme' => 'mybg_help_roadmap',
      '#phases' => [
        ['version' => 'V0.1', 'status' => 'done', 'title' => 'Проєктування', 'items' => ['Контентна модель', 'Radix тема', 'Ролі користувачів']],
        ['version' => 'V0.2', 'status' => 'done', 'title' => 'Базовий контент', 'items' => ['Новини', 'Оголошення', 'Робота', 'Бізнес-каталог']],
        ['version' => 'V0.3', 'status' => 'done', 'title' => 'Навігація та карта', 'items' => ['Пошук', 'Карта OSM', 'Профілі', 'Help Center']],
        ['version' => 'V0.4', 'status' => 'progress', 'title' => 'Соціальний шар', 'items' => ['Коментарі', 'Реакції', 'Рейтинги', 'Модерація']],
        ['version' => 'V1.0', 'status' => 'planned', 'title' => 'Публічний запуск', 'items' => ['SEO', 'Seed-контент', 'Facets', 'Open Graph']],
        ['version' => 'V1.5', 'status' => 'planned', 'title' => 'Спільнота', 'items' => ['Ідеї', 'Допомога', 'Проблеми міста']],
        ['version' => 'V2.0', 'status' => 'planned', 'title' => 'Гордість міста', 'items' => ['Галерея', '360', 'Видатні люди', 'Маршрути']],
        ['version' => 'V3.0', 'status' => 'planned', 'title' => 'Масштабування', 'items' => ['Інші міста', 'API', 'PWA / мобільний']],
      ],
      '#attached' => ['library' => ['mybg_help/help_ui']],
    ];
  }

  /**
   * Mission and values page.
   */
  public function mission(): array {
    $doc = $this->docs->getMissionDoc();
    $body = '';
    if ($doc && $doc->hasField('body') && !$doc->get('body')->isEmpty()) {
      $body = $doc->get('body')->processed;
    }

    return [
      '#theme' => 'mybg_help_mission',
      '#title' => $doc ? $doc->label() : 'Місія та цінності',
      '#body' => $body,
      '#attached' => ['library' => ['mybg_help/help_ui']],
    ];
  }

  /**
   * Mark onboarding tour as completed for current user.
   */
  public function completeOnboarding(Request $request): JsonResponse {
    $token = $request->headers->get('X-CSRF-Token', '');
    if (!$this->csrfToken->validate($token, 'mybg_help_onboarding')) {
      return new JsonResponse(['status' => 'forbidden'], 403);
    }
    $account = $this->currentUser();
    $user = $this->entityTypeManager()->getStorage('user')->load($account->id());
    if ($user && $user->hasField('field_onboarding_done')) {
      $user->set('field_onboarding_done', 1);
      $user->save();
    }
    return new JsonResponse(['status' => 'ok']);
  }

}
