<?php

namespace App\Exports;

use App\Models\User;
use App\Models\LevelUnlocked;
use App\Models\UserCity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class AllCorporateRsaExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        return $result = User::select([
            DB::raw('users.id'),
            DB::raw('users.username'),
            DB::raw('users.first_name'),
            DB::raw('users.last_name'),
            DB::raw('users.address'),
            DB::raw('users.email'),
            DB::raw('store_cities.name as city'),
            DB::raw('store_states.name as state'),
            DB::raw('users.zip'),
            DB::raw('users.phone'),
            DB::raw('users.city_id'),
            DB::raw('users.created_at'),
            DB::raw('store.name as store_name'),
        ])
            ->join('store', 'store.id', '=', 'users.store_id')
            ->join(
                'store_cities',
                'users.store_city_id',
                '=',
                'store_cities.id'
            )
            ->join(
                'store_states',
                'users.store_state_id',
                '=',
                'store_states.id'
            )
            ->where('users.status', 'active')
            ->where('users.user_type', 'corporate')
            ->orderBy('users.id', 'desc')
            ->get();
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
            $data->store_name,
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
