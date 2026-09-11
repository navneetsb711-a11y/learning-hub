<?php

namespace Drupal\course_enrollment\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *  id = "enrollment_progress_block",
 *  admin_label = @Translation("Your Enrollment Progress"),
 *  category = @Translation("Learning Hub")
 * )
 */


class EnrollmentProgressBlock extends BlockBase implements ContainerFactoryPluginInterface{
    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;

    public function __construct(array $configuration, $plugin_id, $plugin_definition,EntityTypeManagerInterface $entity_Type_Manager,AccountProxyInterface $current_user)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->entityTypeManager = $entity_Type_Manager;
        $this->currentUser = $current_user;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
            $container->get('current_user')
        );
    }

    public function build()
    {
        if($this->currentUser->isAnonymous()){
            return [];
        }

        $storage = $this->entityTypeManager->getStorage('enrollment');

        $total = (int) $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('user_id',$this->currentUser->id())
            ->count()
            ->execute();

        $completed = (int) $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('user_id',$this->currentUser->id())
            ->condition('status','completed')
            ->count()
            ->execute();

        $percentage = $total>0 ? round(($completed / $total)*100) : 0;

        return [
            '#theme' => 'course_enrollment_progress',
            '#completed' => $completed,
            '#total' => $total,
            '#percentage' => $percentage,
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['enrollment_list']
            ],
            
        ];  

        
    }


}
