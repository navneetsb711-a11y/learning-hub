<?php

namespace Drupal\course_enrollment\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\course_enrollment\Service\EnrollmentManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class CompleteCourseForm extends FormBase {

  protected EnrollmentManager $enrollmentManager;
  protected AccountProxyInterface $currentUser;
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    EnrollmentManager $enrollment_manager,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->enrollmentManager = $enrollment_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('course_enrollment.manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  public function getFormId() {
    return 'course_enrollment_complete_course_form';
  }

  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?NodeInterface $course = NULL
  ) {
    if (!$course || $course->bundle() !== 'course') {
      return [];
    }

    $form['course_id'] = [
      '#type' => 'hidden',
      '#value' => $course->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mark Course Complete'),
      '#attributes' => [
        'class' => [
          'course-complete-button',
        ],
      ],
    ];

    return $form;
  }

  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $course_id = $form_state->getValue('course_id');

    $course = $this->entityTypeManager
      ->getStorage('node')
      ->load($course_id);

    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    if (!$course || !$user) {
      $this->messenger()->addError(
        $this->t('Unable to complete the course.')
      );

      return;
    }

    try {
      $this->enrollmentManager->complete($user, $course);
    }
    catch (\RuntimeException $exception) {
      $this->messenger()->addError(
        $this->t('@message', [
          '@message' => $exception->getMessage(),
        ])
      );
    }

    $form_state->setRedirect('entity.node.canonical', [
      'node' => $course->id(),
    ]);
  }

}