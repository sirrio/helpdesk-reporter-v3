<?php

namespace App\Actions;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportAttendancesCsv
{
    /**
     * Stream the filtered attendance records as an Excel-compatible CSV file.
     *
     * @param  array<string, mixed>  $filters
     */
    public function handle(array $filters): StreamedResponse
    {
        $filename = 'tutorienbesuche_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new RuntimeException('The attendance CSV output stream could not be opened.');
            }

            fwrite($output, "\xEF\xBB\xBFsep=,\r\n");
            fputcsv($output, $this->headers(), ',', '"', '', "\r\n");

            foreach ($this->attendanceQuery($filters)->lazy(500) as $attendance) {
                fputcsv($output, $this->row($attendance), ',', '"', '', "\r\n");
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Attendance>
     */
    private function attendanceQuery(array $filters): Builder
    {
        return Attendance::query()
            ->with('user:id,name')
            ->filter($filters)
            ->when(
                filled($filters['user'] ?? null),
                fn (Builder $query) => $query->where('user_id', $filters['user']),
            )
            ->orderBy('date')
            ->orderBy('startTime')
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    private function headers(): array
    {
        return [
            'Semester',
            'Wochentag',
            'Datum',
            'von',
            'bis',
            'Studiengang',
            'Fachbereich',
            ...array_values(Attendance::topicOptions()),
            'Tutor',
            'Online',
            'Besucher',
        ];
    }

    /**
     * @return array<int, int|string>
     */
    private function row(Attendance $attendance): array
    {
        $weekdays = [
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            7 => 'Sonntag',
        ];
        $topicValues = collect(Attendance::topicOptions())
            ->keys()
            ->map(fn (string $column): string => $attendance->{$column} ? 'x' : '')
            ->all();

        return [
            $this->safeText($attendance->semester),
            $weekdays[$attendance->date->dayOfWeekIso],
            $attendance->date->toDateString(),
            $attendance->startTime,
            $attendance->endTime,
            $this->safeText($attendance->degree),
            $this->safeText($attendance->faculty),
            ...$topicValues,
            $this->safeText($attendance->user?->name ?? ''),
            $attendance->online ? 'x' : '',
            $attendance->visitors,
        ];
    }

    private function safeText(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) === 1
            ? "'{$value}"
            : $value;
    }
}
