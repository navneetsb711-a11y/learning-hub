<?php

namespace Drupal\course_enrollment\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\course_enrollment\Event\courseEnrollmentEvent;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CourseEnrollmentSubscriber implements EventSubscriberInterface{

    use StringTranslationTrait;
    protected EntityTypeManagerInterface $entityTypeManager;
    protected MessengerInterface $messenger;
    protected LoggerChannelFactoryInterface $loggerFactory;

    public function __construct(
        LoggerChannelFactoryInterface $loggerFactory,
        EntityTypeManagerInterface $entityTypeManager,
        MessengerInterface $messenger,
    ){
        $this->loggerFactory = $loggerFactory;
        $this->entityTypeManager = $entityTypeManager;
        $this->messenger = $messenger;
    }
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            courseEnrollmentEvent::ENORLLED => 'onEnrollement',
            courseEnrollmentEvent::COMPLETED => 'onCompletion'
        ];
    }


    public function onEnrollement(courseEnrollmentEvent $event){
        $course = $this->entityTypeManager
            ->getStorage('node')
            ->load($event->getCourseId());

        $course_title = $course ? $course->label() : $this->t('Unknown course');

        $this->loggerFactory
            ->get('course_enrollment')
            ->notice(
                'user @uid enrolled in course @course.',
                [
                    '@uid' => $event->getUserId(),
                    '@course' => $course_title
                ]
            );

        $this->messenger->addStatus(
            $this->t('You have successfully enrolled in @course',['@course' => $course_title]),
        );
    }

    public function onCompletion(courseEnrollmentEvent $event) : void {
        $course = $this->entityTypeManager->getStorage('node')->load($event->getCourseId());

        $course_title = $course? $course->label() : $this->t("unknown course");

        $this->loggerFactory
            ->get('course_enrollment')
            ->notice(
                'User @uid completed the @course.',
                [
                    '@uid' => $event->getUserId(),
                    '@course' => $course_title
                ]
            );
        

        $this->messenger->addStatus(
            $this->t(
                'Congratulations! You completed @course.',
                [
                    '@course' => $course_title
                ]
            )
        );
        
    }

}
