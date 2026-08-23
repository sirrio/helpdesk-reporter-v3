<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Degree;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semesters = Semester::query()
            ->orderBy('start')
            ->get()
            ->keyBy('semester');
        $degrees = Degree::query()
            ->orderBy('name')
            ->get()
            ->keyBy('name');
        $faculties = Faculty::query()
            ->orderBy('name')
            ->get()
            ->keyBy('name');

        $tutors = collect([
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'entries' => [
                    [
                        'semester' => 'WS 2024/2025',
                        'date' => '2025-01-13',
                        'startTime' => '09:00:00',
                        'endTime' => '11:00:00',
                        'degree' => 'Informatik',
                        'faculty' => 'Informatik',
                        'online' => true,
                        'topics' => ['programming', 'mathBasic'],
                    ],
                    [
                        'semester' => 'SS 2025',
                        'date' => '2025-04-18',
                        'startTime' => '10:00:00',
                        'endTime' => '12:00:00',
                        'degree' => 'Mathematik',
                        'faculty' => 'Naturwissenschaften',
                        'online' => false,
                        'topics' => ['mathFractions', 'organization'],
                    ],
                ],
            ],
            [
                'name' => 'Tutor Alpha',
                'email' => 'tutor.alpha@example.com',
                'entries' => [
                    [
                        'semester' => 'WS 2024/2025',
                        'date' => '2025-02-05',
                        'startTime' => '08:30:00',
                        'endTime' => '10:30:00',
                        'degree' => 'Informatik',
                        'faculty' => 'Informatik',
                        'online' => true,
                        'topics' => ['programming', 'organization'],
                    ],
                    [
                        'semester' => 'SS 2025',
                        'date' => '2025-05-09',
                        'startTime' => '13:00:00',
                        'endTime' => '15:00:00',
                        'degree' => 'Physik',
                        'faculty' => 'Ingenieurwissenschaften',
                        'online' => false,
                        'topics' => ['physics', 'chemistry'],
                    ],
                ],
            ],
            [
                'name' => 'Tutor Beta',
                'email' => 'tutor.beta@example.com',
                'entries' => [
                    [
                        'semester' => 'WS 2024/2025',
                        'date' => '2024-11-20',
                        'startTime' => '14:00:00',
                        'endTime' => '16:00:00',
                        'degree' => 'Mathematik',
                        'faculty' => 'Naturwissenschaften',
                        'online' => false,
                        'topics' => ['mathLow', 'mathHigh'],
                    ],
                    [
                        'semester' => 'SS 2025',
                        'date' => '2025-06-03',
                        'startTime' => '11:15:00',
                        'endTime' => '12:45:00',
                        'degree' => 'Informatik',
                        'faculty' => 'Informatik',
                        'online' => true,
                        'topics' => ['programming', 'mathBasic'],
                    ],
                ],
            ],
            [
                'name' => 'Tutor Gamma',
                'email' => 'tutor.gamma@example.com',
                'entries' => [
                    [
                        'semester' => 'WS 2024/2025',
                        'date' => '2024-12-11',
                        'startTime' => '09:45:00',
                        'endTime' => '11:15:00',
                        'degree' => 'Physik',
                        'faculty' => 'Ingenieurwissenschaften',
                        'online' => true,
                        'topics' => ['physics', 'organization'],
                    ],
                    [
                        'semester' => 'SS 2025',
                        'date' => '2025-07-01',
                        'startTime' => '15:00:00',
                        'endTime' => '17:00:00',
                        'degree' => 'Mathematik',
                        'faculty' => 'Naturwissenschaften',
                        'online' => false,
                        'topics' => ['mathFractions', 'chemistry'],
                    ],
                ],
            ],
        ]);

        $topicColumns = array_fill_keys(array_keys(Attendance::topicOptions()), false);

        $tutors->each(function (array $tutor) use ($semesters, $degrees, $faculties, $topicColumns): void {
            $user = User::query()->updateOrCreate(
                ['email' => $tutor['email']],
                [
                    'name' => $tutor['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'approved_at' => now(),
                    'isMod' => false,
                    'isAdmin' => false,
                ],
            );

            collect($tutor['entries'])->each(function (array $entry) use (
                $user,
                $semesters,
                $degrees,
                $faculties,
                $topicColumns,
            ): void {
                $topicState = collect($entry['topics'])
                    ->mapWithKeys(fn (string $topic) => [$topic => true])
                    ->all();

                Attendance::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'semester' => $semesters[$entry['semester']]->semester,
                        'date' => $entry['date'],
                        'startTime' => $entry['startTime'],
                        'endTime' => $entry['endTime'],
                        'degree' => $degrees[$entry['degree']]->name,
                        'faculty' => $faculties[$entry['faculty']]->name,
                    ],
                    [
                        ...$topicColumns,
                        ...$topicState,
                        'online' => $entry['online'],
                    ],
                );
            });
        });
    }
}
