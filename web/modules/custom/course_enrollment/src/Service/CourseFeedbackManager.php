<?php

namespace Drupal\course_enrollment\Service;

use Drupal\Core\Database\Connection;

class CourseFeedbackManager {

  public function __construct(
    protected Connection $database,
  ) {}

  public function getFeedbackByCourse(int $course_id): array {
    $query = $this->database->select('course_feedback', 'cf');

    $query->leftJoin(
      'users_field_data',
      'u',
      'u.uid = cf.uid'
    );

    $query->fields('cf', [
      'id',
      'uid',
      'course_id',
      'rating',
      'recommend',
      'favorite_part',
      'comments',
      'created',
    ]);

    $query->addField('u', 'name', 'user_name');

    $query->condition('cf.course_id', $course_id);
    $query->orderBy('cf.created', 'DESC');

    return $query->execute()->fetchAll();
  }

}