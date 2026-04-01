<?php

namespace Database\Seeders;

use App\ApplicationStatus;
use App\Models\Answer;
use App\Models\Application;
use App\Models\Guest;
use App\Models\Payment;
use App\PaymentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Application::factory(200)->create()->each(function($application){
            // Create a random number of guests for each application
            // The number of guests is between 0 and the maxGuests of the activity
            Guest::factory(rand(0, $application->activity->maxGuests))->create([
                'application_id' => $application->id,
            ]);
            // Update the participants count in the application
            // This is needed because the guests are created after the application
            $application->update([
                'participants' => $application->guests()->count() + 1,
            ]);
            // Add the payment to the application
            $payment = Payment::factory()->create([
                'application_id' => $application->id,
                'price' => $application->activity->price * $application->participants,
            ]);
            // Check if the payment went through
            if ($payment->status == PaymentStatus::paid) {
                $application->update([
                    'status' => ApplicationStatus::Active,
                ]);
            }

            // Randomly decide to change the status to cancelled, based on realistic user randomness
            if($application->status === ApplicationStatus::Active && fake()->boolean()){
                $application->update([
                    'status' => ApplicationStatus::Cancelled,
                ]);
            }

            // Add reserve status to overflow
            if($application->activity->maxParticipants > 0 && $application->activity->participants->all->count() > $application->activity->maxParticipants){
                $application->update([
                    'status' => ApplicationStatus::Reserve,
                ]);
            }

            foreach ($application->activity->questions as $question) {
                Answer::factory()->answer($question)->create([
                    'application_id' => $application->id,
                ]);
            }

            $application->activity->updateApplications();
        });
    }
}
