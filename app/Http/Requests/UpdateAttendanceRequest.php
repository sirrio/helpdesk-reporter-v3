<?php

namespace App\Http\Requests;

use App\Models\Attendance;

class UpdateAttendanceRequest extends StoreAttendanceRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance instanceof Attendance
            && ($attendance->user_id === $this->user()?->id
                || $this->user()?->isMod === true
                || $this->user()?->isAdmin === true);
    }
}
