<?php

namespace App\Services;

use App\Enums\Status\AlertStatus;
use App\Models\Alert;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Traits\GetsAuthUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Throwable;

class AlertService extends BaseService
{
    use GetsAuthUser;

    public function __construct(
        public Request $request
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAllAlerts(): array
    {
        $conditions = [];
        /** @var string $include */
        $include = $this->request->query('include');
        if ($filter = $this->request->query('filter')) {
            $this->dateRangeFilter($conditions, $filter, 'dispatched_at');
            if (isset($filter['dispatch_date']) && $filter['dispatch_date']) {
                $conditions[] = ['dispatched_at', '>=', $filter['dispatch_date'].' 00:00:00'];
                $conditions[] = ['dispatched_at', '<=', $filter['dispatch_date'].' 23:59:59'];
                unset($filter['dispatch_date']);
            }
            foreach ($filter as $column => $value) {
                if ($value) {
                    $conditions[] = ["$column", 'LIKE', "%$value%"];
                }
            }
        }

        $query = Alert::with($include ? explode(',', $include) : [])
            ->withCount('users')
            ->where($conditions);

        return $this->paginate($query);
    }

    public function show(Alert $alert): Alert
    {
        return $alert->load(['creator', 'users']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Alert
    {
        $data['dispatched_at'] = $data['dispatch_datetime'];
        $data['creator_id'] = $this->authAdmin()->id;
        $users = $data['target'] == 'specific' ? $data['users'] : [];
        unset($data['dispatch_datetime'], $data['users']);

        DB::beginTransaction();

        try {
            /** @var Alert $alert */
            $alert = Alert::create($data);
            $alert->users()->sync($users);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }

        return $alert->withoutRelations()->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Alert $alert, array $data): Alert
    {
        throw_if(
            $alert['status'] != AlertStatus::PENDING->value,
            BadRequestException::class,
            "Alert's current status is {$alert['status']}, therefore, cannot be updated."
        );

        $data['dispatched_at'] = $data['dispatch_datetime'];
        $users = $data['target'] == 'specific' ? $data['users'] : [];
        unset($data['dispatch_datetime'], $data['users']);

        DB::beginTransaction();

        try {
            /** @var Alert $alert */
            $alert = $alert->update($data);
            $alert->users()->sync($users);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new BadRequestException($e->getMessage());
        }

        return $alert->withoutRelations()->refresh();
    }

    public function destroy(Alert $alert): void
    {
        $alert->users()->sync([]);
        $alert->delete();
    }

    public function restore(string|int $alertId): Alert
    {
        /** @var Alert $alert */
        $alert = Alert::withTrashed()->find($alertId);
        $alert->restore();

        return $alert;
    }

    public function dispatch(Alert $alert): void
    {
        DB::beginTransaction();
        try {
            $recipients = $alert->recipients();
            $channels = $alert['channels'];

            if (in_array('email', $channels)) {
                $this->sendEmailAlert($recipients, $alert);
            }

            if (in_array('push', $channels)) {
                $this->sendPushAlert($recipients, $alert);
            }

            $alert->markAsSuccessful(count($recipients));

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $alert->markAsFailed($e->getMessage());
        }
    }

    private function sendEmailAlert(Collection $recipients, Alert $alert): void
    {
        foreach ($recipients as $recipient) {
            $this->sendMailNotification($recipient, $alert);
        }
    }

    private function sendPushAlert(Collection $recipients, Alert $alert): void
    {
        foreach ($recipients as $recipient) {
            if ($recipient->fcm_token) {
                $this->sendPushNotification($recipient, $alert);
            }
        }
    }

    private function sendPushNotification(User $recipient, Alert $alert): void
    {
        Notification::send($recipient, new AppNotification($alert['title'], $alert['body'], ['firebase']));
    }

    private function sendMailNotification(User $recipient, Alert $alert): void
    {
        Notification::send($recipient, new AppNotification($alert['title'], $alert['body'], ['mail']));
    }

    /**
     * @param  array<int, mixed>  $conditions
     * @param  array<string, mixed>  $filter
     */
    public function dateRangeFilter(array &$conditions, array &$filter, string $column = 'created_at'): void
    {
        if (isset($filter['from']) && $filter['from']) {
            $conditions[] = ["$column", '>=', $filter['from']];
            if (isset($filter['to'])) {
                $conditions[] = ["$column", '<=', ($filter['to'] ?? now()->format('Y-m-d')).' 23:59:59'];
                unset($filter['to']);
            }
            unset($filter['from']);
        }
    }
}
