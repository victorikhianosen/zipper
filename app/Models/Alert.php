<?php

namespace App\Models;

use App\Enums\Status\AlertStatus;
use App\Enums\Status\UserStatus;
use App\Jobs\AlertDispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Alert extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'channels' => 'array',
    ];

    /**
     * Mark alert as ongoing.
     */
    public function markAsOngoing(): void
    {
        $this->updateOrFail(['status' => AlertStatus::ONGOING->value]);

        dispatch(new AlertDispatcher($this));
    }

    /**
     * Mark alert as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->updateOrFail(['status' => AlertStatus::FAILED->value, 'failed_note' => $reason]);
    }

    /**
     * Mark alert as successful.
     */
    public function markAsSuccessful(int $recipientsCount): void
    {
        $this->updateOrFail(['status' => AlertStatus::SUCCESSFUL->value, 'user_count' => $recipientsCount, 'failed_note' => null]);
    }

    /**
     * @return Collection<int, mixed>
     *
     * @throws BadRequestException
     */
    public function recipients(): Collection
    {
        $recipients[] = match ($this['target']) {
            'specific' => $this->users()->select(['id', 'firstname', 'lastname', 'email', 'fcm_token'])->get(),
            'all' => User::query()->select(['id', 'firstname', 'lastname', 'email', 'fcm_token'])->get(),
            'verified' => User::query()->where('status', UserStatus::ACTIVE->value)->select(['id', 'firstname', 'lastname', 'email', 'fcm_token'])->get(),
            default => throw new BadRequestException('Invalid target', 400)
        };

        return collect($recipients)->collapse();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'creator_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alert_user');
    }
}
