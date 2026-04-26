<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Dashboard\Providers\DashboardServiceProvider::class,
    Modules\Students\Providers\StudentsServiceProvider::class,
    Modules\Managers\Providers\ManagersServiceProvider::class,
    Modules\Series\Providers\SeriesServiceProvider::class,
    Modules\Results\Providers\ResultsServiceProvider::class,
    Modules\ExamRequests\Providers\ExamRequestsServiceProvider::class,
    Modules\Settings\Providers\SettingsServiceProvider::class,
    Modules\Reservations\Providers\ReservationsServiceProvider::class,
    Modules\Feedback\Providers\FeedbackServiceProvider::class,
    Modules\Evaluations\Providers\EvaluationServiceProvider::class,
];
