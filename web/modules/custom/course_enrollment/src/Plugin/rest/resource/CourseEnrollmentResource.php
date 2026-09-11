<?php

namespace Drupal\course_enrollment\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\course_enrollment\Service\EnrollmentManager;
use Drupal\node\NodeInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a REST resource for Course enrollment.
 *
 * @RestResource(
 *   id = "course_enrollment_resource",
 *   label = @Translation("Course Enrollment Resource"),
 *   uri_paths = {
 *     "create" = "/api/course/enroll"
 *   }
 * )
 */

class CourseEnrollmentResource extends ResourceBase implements ContainerFactoryPluginInterface{
    protected EnrollmentManager $enrollmentManager;
    protected AccountProxy $currentUser;
    protected EntityTypeManagerInterface $entityTypeManager;

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition, 
        array $serializer_formats, 
        LoggerInterface $logger,
        EnrollmentManager $enrollmentManager,
        AccountProxy $currentUser,
        EntityTypeManagerInterface $entityTypeManager

    ){
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->enrollmentManager = $enrollmentManager;
        $this->currentUser = $currentUser;
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('course_enrollment'),
      $container->get('course_enrollment.manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
    }

    public function post($data){
        if($this->currentUser->isAnonymous()){
            return new ModifiedResourceResponse(
                [
                    'success' => FALSE,
                    'message' => $this->t('Authentication is required.')
                ],401
            );
        }

        if(!is_array($data) || empty($data['course_id'])){
            return new ModifiedResourceResponse(
                [
                    'success' => FALSE,
                    'message' => $this->t('course_id is required')
                ],400
            );
        }

        $course_id = (int) $data['course_id'];

        $course = $this->entityTypeManager->getStorage('node')->load($course_id);

        if(!$course instanceof NodeInterface || $course->bundle() !== 'course'){
            return new ModifiedResourceResponse(
                [
                    'success' => FALSE,
                    'message' => $this->t('The requested course doesnot exist.')
                ],404
            );
        }

        $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

        if(!$user){
            return new ModifiedResourceResponse(
                [
                    'success' => FALSE,
                    'message' => $this->t('Unable to load the current user.')
                ],500
            );
        }

        try{
            $enrollment = $this->enrollmentManager->enroll($user,$course);
            return new ModifiedResourceResponse(
                [
                    'success' => TRUE,
                    'message' => $this->t('You have successfully enrolled in this course.'),
                    'enrollment' => [
                        'id' => (int) $enrollment->id(),
                        'course_id' => (int) $course->id(),
                        'course' => $course->label(),
                        'user_id'=> $user->id(),
                        'status' => $enrollment->get('status')->value
                    ],
                ],201
            );
        }catch(\RuntimeException $exception){
            return new ModifiedResourceResponse(
                [
                    'success' => FALSE,
                    'message' => $exception->getMessage()
                ],400
            );
        }
        
    }
}

