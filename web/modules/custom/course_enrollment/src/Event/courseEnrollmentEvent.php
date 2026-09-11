<?php

namespace Drupal\course_enrollment\Event;

use Symfony\Contracts\EventDispatcher\Event;

class courseEnrollmentEvent extends Event{
    public const ENORLLED =  'course_enrollment.enrolled';
    public const COMPLETED = 'course_enrollment.completed';

    protected int $userId;
    protected int $courseId;
    protected string $status;

    public function __construct(int $userId,int $courseId , string $status){
        $this->userId = $userId;
        $this->courseId = $courseId;
        $this->status = $status; 
    }

    public function getUserId():int{
        return $this->userId;
    }

    public function getCourseId():int{
        return $this->courseId;
    }

    public function getStatus() : string {
        return $this->status;
    }
}