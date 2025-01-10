<?php

namespace App\Listeners;

use App\Events\CoursePurchased;
use App\Models\CelcashPayments;
use App\Models\Lesson;
use App\Models\ProgressTracking;
use App\Models\StudentHasAccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InitializeLessonProgress implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public $tries = 3; // Número de tentativas
    public $timeout = 30; // Tempo máximo em segundos
    public $queue = 'course-purchased';
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CoursePurchased $event)
    {
        $userId = $event->userId;
        $galax_pay_id = $event->galax_pay_id;

        // Recupera as ofertas e area de membros que essas ofertas estao atreladas.
        $data = CelcashPayments::query()
            ->select([
                'celcash_payments.galax_pay_id',
                'celcash_payments.status',
                'celcash_payments_offers.products_offerings_id',
                'celcash_payments_offers.type',
                'celcash_payments.buyer_user_id',
                'members_area_offers.id'
            ])
            ->join('celcash_payments_offers', 'celcash_payments_offers.celcash_payments_id', '=', 'celcash_payments.id')
            ->join('members_area_offers', 'members_area_offers.product_offering_id', '=', 'celcash_payments_offers.products_offerings_id')
            ->where('celcash_payments.galax_pay_id', $galax_pay_id)
            ->where('celcash_payments.buyer_user_id', $userId)
            ->get();

        $this->add_membership_to_student($data);
    }

    public function add_membership_to_student($data)
    {
        $data->each(function ($record) {
            if ($record['members_area_id'] && $record['buyer_user_id']) {
                StudentHasAccess::updateOrCreate(
                    [
                        'user_id' => $record['buyer_user_id'],
                        'members_area_offers_id' => $record['id'],
                        'is_active' => True
                    ],
                    []
                );
            }
        });
    }

    public function ProgressTrackingUpdateToStudent($data)
    {
        $lessons = Lesson::whereHas('module', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->get();

        foreach ($lessons as $lesson) {
            ProgressTracking::updateOrCreate(
                [
                    'user_id' => $userId,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'progress_percentage' => 0,
                    'watched_at' => null,
                ]
            );
        }

    }
}
