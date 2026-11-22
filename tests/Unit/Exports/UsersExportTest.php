<?php

namespace Tests\Unit\Exports;

use App\Exports\UsersExport;
use App\Models\CitizenProfile;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UsersExportTest extends TestCase
{
    public function test_headings_and_row_match_the_exported_user_profile_fields(): void
    {
        $createdAt = Carbon::parse('2026-11-16 00:00:00');
        $user = new User;
        $user->setRawAttributes([
            'id' => 17,
            'name' => 'Carlo Cruz',
            'email' => 'carlo@example.test',
            'phone' => '+639171234567',
            'role' => 'staff',
            'created_at' => $createdAt,
        ]);
        $user->setRelation('employeeProfile', new EmployeeProfile([
            'employee_id' => 'EMP-17',
            'department' => 'Records',
            'position' => 'Clerk',
            'office' => 'Front desk',
            'date_hired' => '2020-01-02',
        ]));
        $user->setRelation('citizenProfile', new CitizenProfile([
            'date_of_birth' => '1990-04-05',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'Town',
            'occupation' => 'Teacher',
            'house_number' => '12',
            'street' => 'Pine Street',
            'barangay' => 'Central',
            'city_municipality' => 'Town',
            'province' => 'Province',
            'zip_code' => '1234',
        ]));

        $export = new UsersExport;

        $this->assertSame([
            'ID', 'Name', 'Email', 'Phone', 'Role', 'Date Created', 'Employee ID', 'Department',
            'Position', 'Office', 'Date Hired', 'Gender', 'Date of Birth', 'Civil Status',
            'Nationality', 'Place of Birth', 'Religion', 'Occupation', 'House Number', 'Barangay',
            'City/Municipality', 'Province', 'ZIP Code', 'Full Address',
        ], $export->headings());
        $this->assertSame([
            17, 'Carlo Cruz', 'carlo@example.test', '+639171234567', 'staff', '2026-11-16',
            'EMP-17', 'Records', 'Clerk', 'Front desk', '2020-01-02', 'Female', '1990-04-05',
            'Single', 'Filipino', 'Town', null, 'Teacher', '12', 'Central', 'Town', 'Province',
            '1234', '12, Pine Street, Central, Town, Province',
        ], $export->row($user));
    }
}
