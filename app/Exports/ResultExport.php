<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Store;
use App\Models\StoreState;
use App\Models\StoreCity;
use App\Models\LevelUnlocked;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class ResultExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $_storeId;
    protected $_stateId;
    protected $_cityId;

    function __construct($storeId, $stateId, $cityId)
    {
        $this->_storeId = $storeId;
        $this->_stateId = $stateId;
        $this->_cityId = $cityId;
    }

    public function collection()
    {
        $result = User::select([
            DB::raw('users.id as user_id'),
            DB::raw('users.first_name'),
            DB::raw('users.last_name'),
            DB::raw('users.email'),
            DB::raw('users.store_id'),
            DB::raw('users.store_state_id'),
            DB::raw('users.store_city_id'),
            DB::raw('store_cities.name as city_name'),
            DB::raw('store.id'),
            DB::raw('store.name as store_name'),
            DB::raw('store.account'),
            DB::raw('store_states.name as state_name'),
        ])
            ->join('store', 'store.id', '=', 'users.store_id')
            ->join(
                'store_states',
                'store_states.id',
                '=',
                'users.store_state_id'
            )
            ->join(
                'store_cities',
                'users.store_city_id',
                '=',
                'store_cities.id'
            )
            ->where('users.status', 'active');

        if ($this->_storeId != '0') {
            $storeName = Store::select('name')
                ->where('id', $this->_storeId)
                ->first();
            $result = $result->where(
                'store.name',
                'like',
                '%' . $storeName->name . '%'
            );
        }

        if ($this->_stateId != '0') {
            $stateName = StoreState::select('name')
                ->where('id', $this->_stateId)
                ->first();
            $result = $result->where(
                'store_states.name',
                'like',
                '%' . $stateName->name . '%'
            );
        }

        if ($this->_cityId != '0') {
            $cityName = StoreCity::select('name')
                ->where('id', $this->_cityId)
                ->first();
            $result = $result->where(
                'store_cities.name',
                'like',
                '%' . $cityName->name . '%'
            );
        }

        $result = $result->get();
        return $result;
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
        $level = [
            1 => 'Freshman',
            2 => 'Sophomore',
            3 => 'Junior',
            4 => 'Senior',
        ];

        $userId = $data->user_id;
        $lastCourse = '';

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

        $storeName = $data->store_name;
        $storeState = $data->state_name;
        $storeCity = $data->city_name;
        $asscName = $data->first_name . ' ' . $data->last_name;
        $email = $data->email;

        return [
            $storeName,
            $storeState,
            $storeCity,
            $asscName,
            $email,
            $lastCourse,
            $status,
        ];
    }

    public function headings(): array
    {
        return [
            'Licensee Group/Store Name',
            'Store State',
            'Store City',
            'Sales Associate Name',
            'Email',
            'Last Course Completed',
            'Status',
        ];
    }
}
