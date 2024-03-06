<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
    use MassPrunable;

    /**
     * Get the prunable model query.
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        $period = now()->subMonths(3);

        return static::where('created_at', '<=', $period)->where(
            fn ($query) => $query->whereNull('last_used_at')->orWhere('last_used_at', '<=', $period)
        );
    }
}
