<?php

namespace Drupal\course_enrollment\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\course_enrollment\Service\EnrollmentManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Override;

class EnrollmentController extends ControllerBase{

    protected EnrollmentManager $enrollmentManager;

    // protected AccountProxyInterface $currentUser;

    public function __construct(EnrollmentManager $enrollmentManager, AccountProxyInterface $currentUser){
        $this->enrollmentManager = $enrollmentManager;
        // $this->currentUser = $currentUser;
    }
    
    #[Override]
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('course_enrollment.manager'),
            $container->get('current_user')
        );
    }

    public function  enroll(NodeInterface $course) : RedirectResponse {
        $user = $this->entityTypeManager()
            ->getStorage('user')
            ->load($this->currentUser()->id());

        try{
            $this->enrollmentManager->enroll($user,$course);
        }catch(\RuntimeException $exception){
            $this->messenger()->addError($this->t('@message',[
                '@message' => $exception->getMessage()
            ]));
        };

        return $this->redirect('entity.node.canonical',[
            'node' => $course->id()
        ]);
    }
}