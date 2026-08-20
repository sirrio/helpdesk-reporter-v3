<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_anonymization_months'])]
class SystemSetting extends Model
{
    public const DEFAULT_USER_ANONYMIZATION_MONTHS = 12;

    public const HEARTBEAT_GRACE_MINUTES = 5;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'user_anonymization_months' => self::DEFAULT_USER_ANONYMIZATION_MONTHS,
    ];

    /**
     * Get the singleton system settings record.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            ['user_anonymization_months' => self::DEFAULT_USER_ANONYMIZATION_MONTHS],
        );
    }

    /**
     * Get a safe anonymization period for legacy or incomplete settings rows.
     */
    public function anonymizationMonths(): int
    {
        $months = (int) ($this->user_anonymization_months ?? self::DEFAULT_USER_ANONYMIZATION_MONTHS);

        return min(120, max(1, $months));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_anonymization_months' => 'integer',
            'scheduler_heartbeat_at' => 'datetime',
            'queue_heartbeat_at' => 'datetime',
            'automation_check_requested_at' => 'datetime',
            'automation_check_completed_at' => 'datetime',
        ];
    }
}
