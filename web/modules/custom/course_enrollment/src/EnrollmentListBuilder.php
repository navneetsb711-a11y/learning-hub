<?php

namespace Drupal\course_enrollment;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\course_enrollment\Entity\Enrollment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the admin listing for Enrollment entities.
 */
class EnrollmentListBuilder extends EntityListBuilder
{


  protected DateFormatterInterface $dateFormatter;
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * Creates the list builder using the service container.
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type,
  ) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
    );
  }


  public function buildHeader()
  {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('User');
    $header['course'] = $this->t('Course');
    $header['status'] = $this->t('Status');
    $header['enrolled_date'] = $this->t('Enrolled date');
    $header['completed_date'] = $this->t('Completed date');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity)
  {
    if (!$entity instanceof Enrollment) {
      return parent::buildRow($entity);
    }

    $user = $entity->get('user_id')->entity;
    $course = $entity->get('course_id')->entity;

    $row['id'] = $entity->id();

    $row['user'] = $user
      ? $user->label()
      : $this->t('Unknown user');

    $row['course'] = $course
      ? $course->label()
      : $this->t('Unknown course');

    $status = $entity->get('status')->value;

    $row['status'] = match ($status) {
      'completed' => $this->t('Completed'),
      default => $this->t('Enrolled'),
    };

    $enrolled_date = $entity->get('enrolled_date')->value;

    $row['enrolled_date'] = $enrolled_date
      ? $this->dateFormatter->format(
        (int) $enrolled_date,
        'custom',
        'd M Y, h:i A',
        'Asia/Kolkata',
      )
      : '-';

    $completed_date = $entity->get('completed_date')->value;

    $row['completed_date'] = $completed_date
      ? $this->dateFormatter->format(
        (int) $completed_date,
        'custom',
        'd M Y, h:i A',
        'Asia/Kolkata',
      )
      : '-';

    return $row + parent::buildRow($entity);
  }
}
