<?php

namespace Drupal\course_enrollment\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\course_enrollment\Entity\Enrollment;
use Drupal\course_enrollment\Event\courseEnrollmentEvent;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EnrollmentManager
{
    protected EntityTypeManagerInterface $entityTypeManager;
    protected TimeInterface $time;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        TimeInterface $timeInterface,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->time  = $timeInterface;
        $this->eventDispatcher = $eventDispatcher;
    }


    public function getEnrollment(UserInterface $user, NodeInterface $course): ?Enrollment
    {
        $storage = $this->entityTypeManager->getStorage('enrollment');

        $enrollments = $storage->loadByProperties([
            'user_id' => $user->id(),
            'course_id' => $course->id()
        ]);

        $enrollment = reset($enrollments);

        return $enrollment instanceof Enrollment ? $enrollment : null;
    }

    public function isEnrolled(UserInterface $user, NodeInterface $course)
    {
        return $this->getEnrollment($user, $course) !== null;
    }

    public function getEnrollmentStatus(UserInterface $user, NodeInterface $course)
    {
        $enrollment = $this->getEnrollment($user, $course);
        if (!$enrollment) {
            return null;
        }
        return $enrollment->get('status')->value;
    }

    public function getEnrollmentCount(NodeInterface $course)
    {
        $storage = $this->entityTypeManager->getStorage('enrollment');
        $count = $storage->getQuery('AND')
            ->accessCheck(False)
            ->condition('course_id', $course->id())
            ->count()
            ->execute();
        return $count;
    }

    public function getSeatsLeft(NodeInterface $course)
    {
        if (!$course->hasField('field_capacity') || $course->get('field_capacity')->isEmpty()) {
            return null;
        }

        $capacity = $course->get('field_capacity')->value;
        if ($capacity <= 0) {
            return null;
        }
        $enrolled = $this->getEnrollmentCount($course);
        return $capacity - $enrolled;
    }

    public function isCourseFull(NodeInterface $course)
    {
        if (
            !$course->hasField('field_capacity') ||
            $course->get('field_capacity')->isEmpty()
        ) {
            return false;
        }

        $capacity = (int) $course->get('field_capacity')->value;

        if ($capacity <= 0) {
            return false;
        }
        return $this->getEnrollmentCount($course) >= $capacity;
    }

    public function enroll(UserInterface $user, NodeInterface $course): Enrollment
    {

        if ($user->isAnonymous()) {
            throw new \RuntimeException(
                'You must log in before enrolling in a course.'
            );
        }
        if ($course->bundle() !== 'course') {
            throw new \InvalidArgumentException(
                'Enrollment is only avalilable for course content.'
            );
        }

        if ($this->isEnrolled($user, $course)) {
            throw new \RuntimeException(
                'This user is already enrolled in the course'
            );
        }

        if ($this->isCourseFull($course)) {
            throw new \RuntimeException(
                'The course has reached its capacity'
            );
        }

        $storage = $this->entityTypeManager->getStorage('enrollment');

        $enrollment = $storage->create([
            'user_id' => $user->id(),
            'course_id' => $course->id(),
            'status' => 'enrolled',
            'enrolled_date' => $this->time->getRequestTime()
        ]);

        $enrollment->save();
        // -------------------- event dispatcher -----------------------------

        $event = new courseEnrollmentEvent($user->id(), $course->id(), 'enrolled');
        $this->eventDispatcher->dispatch($event, courseEnrollmentEvent::ENORLLED);

        // -------------------------------------------------------------------

        return $enrollment;
    }

    public function complete(
        UserInterface $user,
        NodeInterface $course
    ) {
        $enrollment = $this->getEnrollment($user, $course);

        if (!$enrollment) {
            throw new \RuntimeException(
                'The user is not Enrolled in the course'
            );
        }

        if ($enrollment->get('status')->value === 'completed') {
            return $enrollment;
        }

        $enrollment->set('status', 'completed');
        $enrollment->set('completed_date', $this->time->getRequestTime());
        $enrollment->save();

        // --------------dispatch event ----------------------

        $event = new courseEnrollmentEvent($user->id(), $course->id(), 'completed');
        $this->eventDispatcher->dispatch($event, courseEnrollmentEvent::COMPLETED);

        // -------------------------------------------------------

        return $enrollment;
    }
}
