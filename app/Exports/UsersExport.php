<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class UsersExport implements AdminExport
{
    public function filename(): string
    {
        return 'users';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Role',
            'Date Created',
            'Employee ID',
            'Department',
            'Position',
            'Office',
            'Date Hired',
            'Gender',
            'Date of Birth',
            'Civil Status',
            'Nationality',
            'Place of Birth',
            'Religion',
            'Occupation',
            'House Number',
            'Barangay',
            'City/Municipality',
            'Province',
            'ZIP Code',
            'Full Address',
        ];
    }

    public function query(Request $request): Builder
    {
        $query = $this->queryForTab($this->tab($request));
        $status = $request->query('users_status');

        return $query->when(in_array($status, ['active', 'inactive', 'suspended'], true),
            fn (Builder $query) => $query->where('status', $status));
    }

    public function queryForTab(string $tab): Builder
    {
        return User::query()
            ->with(['employeeProfile', 'citizenProfile'])
            ->when($tab === 'employees', fn (Builder $query) => $query->where('role', 'staff'))
            ->when($tab === 'citizens', fn (Builder $query) => $query->where('role', 'citizen'))
            ->when($tab === 'administrators', fn (Builder $query) => $query->where('role', 'admin'))
            ->latest()->orderByDesc('id');
    }

    public function tab(Request $request): string
    {
        $tab = $request->query('users_tab');

        return in_array($tab, ['employees', 'citizens', 'administrators'], true) ? $tab : 'all';
    }

    public function row(Model $model): array
    {
        if (! $model instanceof User) {
            throw new InvalidArgumentException('Users exports require User models.');
        }

        $employee = $model->employeeProfile;
        $citizen = $model->citizenProfile;

        return [
            $model->id,
            $model->name,
            $model->email,
            $model->phone,
            $model->role,
            $model->created_at?->format('Y-m-d'),
            $employee?->employee_id,
            $employee?->department,
            $employee?->position,
            $employee?->office,
            $employee?->date_hired?->format('Y-m-d'),
            $citizen?->gender,
            $citizen?->date_of_birth?->format('Y-m-d'),
            $citizen?->civil_status,
            $citizen?->nationality,
            $citizen?->place_of_birth,
            $citizen?->religion,
            $citizen?->occupation,
            $citizen?->house_number,
            $citizen?->barangay,
            $citizen?->city_municipality,
            $citizen?->province,
            $citizen?->zip_code,
            $citizen?->full_address,
        ];
    }
}
