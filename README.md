# Learning Hub

Learning Hub is a Drupal 10 training portal where Trainers manage
courses and Trainees can browse, enroll in, complete, and review
courses.

## Main Features

-   Trainer and Trainee roles with permissions
-   Course catalog with category and difficulty filters
-   Custom Enrollment content entity
-   My Learning dashboard and progress tracking
-   Course content using Paragraphs and Media
-   Video, image, document, and text course sections
-   Course completion and feedback
-   Custom difficulty badge formatter
-   Enrollment events and event subscriber
-   JSON:API for courses and custom REST enrollment API
-   English and German multilingual support

## Custom Components

-   **Theme:** `learning_hub`
-   **Module:** `course_enrollment`
-   **Enrollment API:** `POST /api/course/enroll`
-   **Course JSON:API:** `GET /jsonapi/node/course`

## Setup

``` bash
composer install
vendor/bin/drush cim -y
vendor/bin/drush cr
```

Import the project database, configure `settings.php`, and ensure the
public files directory is writable.

## Technology

Drupal 10, PHP, Twig, JavaScript, CSS, MySQL, Drush and Composer.
