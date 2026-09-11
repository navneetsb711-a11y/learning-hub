<?php

namespace Drupal\course_enrollment\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\course_enrollment\Entity\Enrollment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the My Learning dashboard.
 */
class MyLearningController extends ControllerBase {

  protected EntityTypeManagerInterface $entityManager;

  protected AccountProxyInterface $account;

  protected BlockManagerInterface $blockManager;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $account,
    BlockManagerInterface $block_manager,
  ) {
    $this->entityManager = $entity_type_manager;
    $this->account = $account;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.block'),
    );
  }

  /**
   * Builds the My Learning page.
   */
  public function page(): array {

    $storage = $this->entityManager->getStorage('enrollment');

    $enrollments = $storage->loadByProperties([
      'user_id' => $this->account->id(),
    ]);

    $total = 0;
    $completed = 0;
    $in_progress = 0;

    $latest_enrollment = NULL;
    $latest_timestamp = 0;

    foreach ($enrollments as $enrollment) {
      $total++;

      if(!$enrollment instanceof Enrollment){
        continue;
      }

      $status = $enrollment->get('status')->value;

      if ($status === 'completed') {
        $completed++;
      }

      if ($status === 'enrolled') {
        $in_progress++;

        $enrolled_date = (int) $enrollment
          ->get('enrolled_date')
          ->value;

        if ($enrolled_date > $latest_timestamp) {
          $latest_timestamp = $enrolled_date;
          $latest_enrollment = $enrollment;
        }
      }
    }

    // Build Continue Learning data.
    $continue_learning = NULL;

    if ($latest_enrollment) {
      $course = $latest_enrollment
        ->get('course_id')
        ->entity;

      if ($course) {
        $continue_learning = [
          'title' => $course->label(),
          'url' => $course->toUrl()->toString()
        ];
      }
    }

    // Build Enrollment Progress custom block.
    $progress_plugin = $this->blockManager
      ->createInstance('enrollment_progress_block', []);

    $progress_block = $progress_plugin->build();

    // Build My Enrolled Courses Views block.
    $enrolled_courses_plugin = $this->blockManager
      ->createInstance(
        'views_block:my_enrolled_courses-block_1',
        []
      );

    $enrolled_courses_block = $enrolled_courses_plugin->build();

    return [
      '#theme' => 'course_enrollment_my_learning',

      '#username' => $this->account->getDisplayName(),

      '#total' => $total,
      '#completed' => $completed,
      '#in_progress' => $in_progress,

      '#continue_learning' => $continue_learning,

      '#progress_block' => $progress_block,

      '#enrolled_courses_block' => $enrolled_courses_block,


      '#cache' => [
        'contexts' => [
          'user',
        ],
        'tags' => [
          'enrollment_list',
        ],
      ],
    ];
  }

}


