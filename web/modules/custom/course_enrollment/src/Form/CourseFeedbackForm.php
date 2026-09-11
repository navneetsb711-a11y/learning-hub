<?php

namespace Drupal\course_enrollment\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CourseFeedbackForm extends FormBase
{
    protected Connection $database;
    protected AccountProxyInterface $currentUser;
    protected TimeInterface $time;

    public function __construct(Connection $database, AccountProxyInterface $currentUser, TimeInterface $time)
    {
        $this->database = $database;
        $this->currentUser = $currentUser;
        $this->time = $time;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database'),
            $container->get('current_user'),
            $container->get('datetime.time')
        );
    }

    public function getFormId()
    {
        return 'course_feedback_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $course = NULL)
    {
        if (!$course || $course->bundle() !== 'course') {
            $form['message'] = [
                '#markup' => $this->t('Invalid Course')
            ];
            return $form;
        }

        $form['course_id'] = [
            '#type' => 'hidden',
            '#value' => $course->id()
        ];

        $form['course_title'] = [
            '#type' => 'item',
            '#title' => $this->t('Course'),
            '#markup' => $course->label()
        ];

        $form['rating'] = [
            '#type' => 'radios',
            '#title' => $this->t('How would you rate this course?'),
            '#options' => [
                1 => $this->t('1 - Poor'),
                2 => $this->t('2 - Fair'),
                3 => $this->t('3 - good'),
                4 => $this->t('4 - Very good'),
                5 => $this->t('5 - Excellent'),
            ],
            '#required' => TRUE
        ];

        $form['recommend'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('I would recommend this course to other.')
        ];

        $form['favorite_part'] = [
            '#type' => 'select',
            '#title' => $this->t('What did you find most useful?'),
            '#options' => [
                '' => $this->t('--Select--'),
                'video' => $this->t('Video'),
                'images' => $this->t('Images'),
                'documents' => $this->t('Documents'),
                'text' => $this->t('Written lessons'),
                'overall' => $this->t('Overall course structure'),
            ],
            '#required' => TRUE
        ];

        $form['comments'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Comments'),
            '#description' => $this->t('Tell us What you likeed or what could be improved.'),
            '#required' => TRUE,
            '#rows' => 6
        ];

        $form['actions'] = [
            '#type' => 'actions'
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit feedback'),
            '#button_type' => 'primary'
        ];
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $rating = (int) $form_state->getValue('rating');
        if ($rating < 1 || $rating > 5) {
            $form_state->setErrorByName(
                'rating',
                $this->t('Please select a rating between 1 and 5')
            );
        }

        $comments = trim((string) $form_state->getValue('comments'));

        if (mb_strlen($comments) < 20) {
            $form_state->setErrorByName('comments', $this->t('Please provide atleast 20 characters of feedback.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->database
            ->insert('course_feedback')
            ->fields([
                'uid' =>  $this->currentUser()->id(),
                'course_id' => $form_state->getValue('course_id'),
                'rating' => (int) $form_state->getValue('rating'),
                'recommend' => $form_state->getValue('recommend') ? 1 : 0,
                'favorite_part' => $form_state->getValue('favorite_part'),
                'comments' => trim(
                    (string) $form_state->getValue('comments')
                ),
                'created' => $this->time->getRequestTime(),

            ])->execute();

            $this->messenger()->addStatus(
                $this->t('Thank you. Your course feedback has been submited.')
            );

            $form_state->setRedirect('course_enrollment.my_learning');
    }
}
