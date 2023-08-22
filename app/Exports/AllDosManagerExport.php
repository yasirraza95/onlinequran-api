<?php

namespace App\Exports;

use App\Models\User;
use App\Models\UserCity;
use App\Models\LevelUnlocked;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
class AllDosManagerExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $dosId;

    function __construct($dosId)
    {
        $this->dosId = $dosId;
    }

    public function collection()
    {
        $query = '';
        if ($this->dosId != 1244) {
            $query = "SELECT u.*, s.name AS storeName FROM users u, store_cities sc, store s WHERE u.user_type = 'manager' AND u.store_id = s.id AND s.city_id = sc.id AND s.dos = '$this->dosId' AND u.deleted_at is NULL ";
        } elseif ($this->dosId == 1244) {
            $query =
                "SELECT u.id, u.username, u.address, u.emp_number, u.email, u.password, u.first_name, u.last_name, u.zip, u.phone, u.created_at, u.status, s.name AS storeName, s.address AS storeAddress, s.zip AS storeZip, sc.name AS cityName FROM users u, store_cities sc, store s WHERE u.user_type = 'manager' AND u.store_id = s.id AND s.city_id = sc.id AND s.account = '9977700' AND u.deleted_at is NULL ";
        }

        $newResult = DB::select($query);
        return collect($newResult);
    }

    function getOnLevel($userId)
    {
        $getPassed = LevelUnlocked::select(DB::raw('max(level_no) as level'))
            ->where('passed_by', $userId)
            ->first();

        if (!empty($getPassed)) {
            return $getPassed->level;
        }
        return 0;
    }

    function getCityName($id)
    {
        $result = UserCity::select('city')->find($id);
        if (!empty($result)) {
            return $result->city;
        }

        return '';
    }

    public function map($data): array
    {
        $userId = $data->id;
        $cityId = $data->city_id;
        $cityName = $this->getCityName($cityId);
        $lastCourse = '';
        $level = [
            1 => 'Freshman',
            2 => 'Sophomore',
            3 => 'Junior',
            4 => 'Senior',
        ];

        if ($this->getOnLevel($userId) > 0) {
            $lastCourse = $level[$this->getOnLevel($userId)];
        } else {
            $lastCourse = 'Not Passed Yet';
        }

        if ($lastCourse == 'Not Passed Yet') {
            $status = 'On Freshman';
        } elseif ($lastCourse == 'Freshman') {
            $status = 'Sophomore';
        } elseif ($lastCourse == 'Sophomore') {
            $status = 'Junior';
        } elseif ($lastCourse == 'Junior') {
            $status = 'Senior';
        } else {
            $status = 'Graduated';
        }

        return [
            $data->username,
            $data->first_name,
            $data->last_name,
            $data->address,
            $data->email,
            $cityName,
            $data->state,
            $data->zip,
            $data->phone,
            date('m-d-Y', strtotime($data->created_at)),
            $data->storeName,
            $lastCourse,
            $status,
        ];
    }

    public function headings(): array
    {
        return [
            'User Name',
            'First Name',
            'Last Name',
            'Address',
            'Email',
            'City',
            'State',
            'Zip',
            'Phone',
            'Date',
            'Store Name',
            'Last Course Completed',
            'Status',
        ];
    }
}
