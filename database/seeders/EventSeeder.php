<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Conferencia de Tecnología 2025',
                'description' => 'Una conferencia anual sobre las últimas tendencias en tecnología y desarrollo de software.',
                'location' => 'Auditorio AV 5',
                'event_date' => '2025-08-15 09:00:00',
                'color' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'title' => 'Workshop de Laravel',
                'description' => 'Aprende las mejores prácticas para desarrollar aplicaciones web con Laravel.',
                'location' => 'C 101',
                'event_date' => '2025-09-10 14:00:00',
                'color' => '#EF4444',
                'is_active' => true,
            ],
            [
                'title' => 'Hackathon 2025',
                'description' => 'Aqui deben de darle un carton a nelly porque es fasti.',
                'location' => 'C304',
                'event_date' => '2025-10-20 18:00:00',
                'color' => '#10B981',
                'is_active' => true,
            ],
            [
                'title' => 'Vue.js',
                'description' => 'Encuentro mensual de desarrolladores.',
                'location' => 'A 104',
                'event_date' => '2025-07-25 19:00:00',
                'color' => '#8B5CF6',
                'is_active' => true,
            ],
            [
                'title' => 'Conferencia de Postman',
                'description' => 'Explorando las últimas tendencias en postman para pruebas.',
                'location' => 'Break fesc',
                'event_date' => '2025-11-05 10:00:00',
                'color' => '#F59E0B',
                'is_active' => true,
            ],
        ];

        foreach ($events as $eventData) {
            Event::create($eventData);
        }
    }
}
