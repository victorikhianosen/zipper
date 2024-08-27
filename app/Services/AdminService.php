<?php

namespace App\Services;

use App\Enums\Status\AdminStatus;
use App\Models\Admin;
use App\Models\BookFlight;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AdminService extends BaseService
{
    public function __construct(
        protected BookFlight $flight,
        public Request $request
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAllAdmins(): array
    {
        $conditions = [];
        /** @var array<string, mixed> $filter */
        $filter = $this->request->query('filter');
        /** @var string|null $search * */
        $search = $this->request->query('search');
        if ($filter) {
            foreach ($filter as $column => $value) {
                $conditions[] = [$column, '=', $value];
            }
        }
        // @phpstan-ignore-next-line
        $query = Admin::query()->where($conditions)->when(($search), fn ($q) => $q->search($search))->latest();

        return $this->paginate($query);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAdmin(array $data): Admin
    {
        $password = Str::random();
        $role = $data['role'];
        unset($data['role']);
        $data['status'] = AdminStatus::ACTIVE->value;
        $data['password'] = bcrypt($password);
        $admin = Admin::create($data);
        $admin->assignRole($role);
        $admin->notify(new AccountCreatedNotification($password));

        return $admin;
    }

    public function toggleAdminStatus(Admin $admin): Admin
    {
        if ($admin->id == 1) {
            throw new BadRequestException('Can\'t mutate default admin', 400);
        }
        $status = $admin['status'] == AdminStatus::ACTIVE ? AdminStatus::RESTRICTED : AdminStatus::ACTIVE;
        $admin->update(['status' => $status->value]);

        return $admin->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUserProfile(Admin $admin, array $data): Admin
    {
        $admin->update($data);

        return $admin->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserFlightBookings(User $user): array
    {
        $query = $this->flight->whereUserId($user->id);

        return $this->paginate($query);
    }

    public function makeReseller(User $user): void
    {
        $data = request()->validate(['callback_url' => 'bail|required|url']);
        $user->update(['is_reseller' => true, 'callback_url' => $data['callback_url']]);
    }
}
