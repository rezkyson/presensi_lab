<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Validator;

class UpdateJadwalRequest extends StoreJadwalRequest
{
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateScheduleConflicts($validator, $this->route('jadwal')?->id);
            },
        ];
    }
}
