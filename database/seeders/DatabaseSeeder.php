<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\ExamRequests\Models\ExamRequest;
use Modules\Results\Models\Result;
use Modules\Series\Models\Answer;
use Modules\Series\Models\Question;
use Modules\Series\Models\Series;
use Modules\Series\Models\StudentSeriesProgress;
use Modules\Series\Models\Video;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Demo Tenant (Driving School)
        $tenant = Tenant::create([
            'school_name' => 'Auto-École Al Amal',
            'slug'        => 'autoecole-al-amal-demo',
            'phone'       => '+212 6 12 34 56 78',
            'address'     => '12 Avenue Mohammed V',
            'city'        => 'Casablanca',
            'is_active'   => true,
        ]);

        // Create Owner
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ahmed Benali',
            'email'     => 'owner@autoecole.ma',
            'password'  => Hash::make('password'),
            'role'      => 'owner',
            'phone'     => '+212 6 11 11 11 11',
            'is_active' => true,
        ]);

        // Create Manager
        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Fatima Zahra',
            'email'     => 'manager@autoecole.ma',
            'password'  => Hash::make('password'),
            'role'      => 'manager',
            'phone'     => '+212 6 22 22 22 22',
            'is_active' => true,
        ]);

        // Create Students
        $students = [];
        $studentData = [
            ['name' => 'Youssef Alami', 'email' => 'youssef@student.ma'],
            ['name' => 'Khadija Ouali', 'email' => 'khadija@student.ma'],
            ['name' => 'Omar Tazi', 'email' => 'omar@student.ma'],
        ];

        foreach ($studentData as $data) {
            $students[] = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make('password'),
                'role'      => 'student',
                'is_active' => true,
            ]);
        }

        // Create Series with Videos & Questions
        $seriesData = [
            [
                'title'       => 'Code de la Route - Série 1',
                'description' => 'Règles fondamentales de la circulation routière',
                'order'       => 1,
                'video_url'   => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'questions'   => [
                    [
                        'text' => 'Que signifie un feu rouge ?',
                        'answers' => [
                            ['text' => 'Arrêt obligatoire', 'correct' => true],
                            ['text' => 'Ralentir', 'correct' => false],
                            ['text' => 'Passer prudemment', 'correct' => false],
                            ['text' => 'Accélérer', 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'La vitesse maximale en agglomération est de ?',
                        'answers' => [
                            ['text' => '60 km/h', 'correct' => true],
                            ['text' => '80 km/h', 'correct' => false],
                            ['text' => '100 km/h', 'correct' => false],
                            ['text' => '40 km/h', 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'Quand doit-on utiliser les feux de croisement ?',
                        'answers' => [
                            ['text' => 'La nuit et par mauvaise visibilité', 'correct' => true],
                            ['text' => 'Uniquement la nuit', 'correct' => false],
                            ['text' => 'Jamais en ville', 'correct' => false],
                            ['text' => 'Seulement sur autoroute', 'correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title'       => 'Panneaux de Signalisation - Série 2',
                'description' => 'Reconnaissance et compréhension des panneaux routiers',
                'order'       => 2,
                'video_url'   => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'questions'   => [
                    [
                        'text' => 'Un panneau triangulaire rouge signifie ?',
                        'answers' => [
                            ['text' => 'Danger - Cédez le passage', 'correct' => true],
                            ['text' => 'Interdiction', 'correct' => false],
                            ['text' => 'Information', 'correct' => false],
                            ['text' => 'Obligation', 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'Que signifie un panneau rond bleu ?',
                        'answers' => [
                            ['text' => 'Obligation', 'correct' => true],
                            ['text' => 'Interdiction', 'correct' => false],
                            ['text' => 'Danger', 'correct' => false],
                            ['text' => 'Information', 'correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title'       => 'Priorités et Intersections - Série 3',
                'description' => 'Gestion des intersections et règles de priorité',
                'order'       => 3,
                'video_url'   => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'questions'   => [
                    [
                        'text' => 'Qui a la priorité dans un rond-point ?',
                        'answers' => [
                            ['text' => 'Les véhicules déjà dans le rond-point', 'correct' => true],
                            ['text' => 'Les véhicules entrant', 'correct' => false],
                            ['text' => 'Les piétons', 'correct' => false],
                            ['text' => 'Les camions', 'correct' => false],
                        ],
                    ],
                    [
                        'text' => 'À une intersection sans signalisation, qui a la priorité ?',
                        'answers' => [
                            ['text' => 'Le véhicule venant de droite', 'correct' => true],
                            ['text' => 'Le véhicule venant de gauche', 'correct' => false],
                            ['text' => 'Le plus grand véhicule', 'correct' => false],
                            ['text' => 'Celui qui arrive le premier', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ];

        $createdSeries = [];
        foreach ($seriesData as $sData) {
            $series = Series::create([
                'tenant_id'   => $tenant->id,
                'title'       => $sData['title'],
                'description' => $sData['description'],
                'order'       => $sData['order'],
                'is_active'   => true,
            ]);

            Video::create([
                'series_id'   => $series->id,
                'title'       => 'Vidéo - ' . $sData['title'],
                'url'         => $sData['video_url'],
                'description' => 'Vidéo pédagogique pour ' . $sData['title'],
                'duration'    => 600,
                'order'       => 1,
            ]);

            foreach ($sData['questions'] as $qIdx => $qData) {
                $question = Question::create([
                    'series_id'     => $series->id,
                    'question_text' => $qData['text'],
                    'order'         => $qIdx + 1,
                ]);

                foreach ($qData['answers'] as $aData) {
                    Answer::create([
                        'question_id' => $question->id,
                        'text'        => $aData['text'],
                        'is_correct'  => $aData['correct'],
                    ]);
                }
            }

            $createdSeries[] = $series;
        }

        // Create Results for first student
        $student1 = $students[0];
        foreach ($createdSeries as $idx => $series) {
            $correctCount = rand(1, $series->questions()->count());
            $totalQ       = $series->questions()->count();
            $percentage   = round(($correctCount / $totalQ) * 100, 2);

            Result::create([
                'user_id'         => $student1->id,
                'series_id'       => $series->id,
                'tenant_id'       => $tenant->id,
                'score'           => $correctCount,
                'total_questions' => $totalQ,
                'correct_answers' => $correctCount,
                'percentage'      => $percentage,
                'attempt_number'  => 1,
                'completed_at'    => now()->subDays($idx + 1),
            ]);

            StudentSeriesProgress::create([
                'user_id'           => $student1->id,
                'series_id'         => $series->id,
                'tenant_id'         => $tenant->id,
                'video_watched'     => true,
                'video_watched_at'  => now()->subDays($idx + 2),
                'quiz_completed'    => true,
                'quiz_completed_at' => now()->subDays($idx + 1),
                'best_score'        => $correctCount,
                'attempts_count'    => 1,
            ]);
        }

        // Create an exam request
        ExamRequest::create([
            'user_id'   => $student1->id,
            'tenant_id' => $tenant->id,
            'status'    => 'pending',
        ]);

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('📧 Login credentials:');
        $this->command->info('  Owner:   owner@autoecole.ma / password');
        $this->command->info('  Manager: manager@autoecole.ma / password');
        $this->command->info('  Student: youssef@student.ma / password');
    }
}
