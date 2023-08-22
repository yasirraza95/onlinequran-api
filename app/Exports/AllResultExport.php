<?php

namespace App\Exports;

use App\Models\User;
use App\Models\LevelUnlocked;
use App\Models\StoreState;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class AllResultExport implements
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
            DB::raw('users.store_id'),
            DB::raw('users.store_state_id'),
            DB::raw('users.username'),
            DB::raw('users.first_name'),
            DB::raw('users.last_name'),
            DB::raw('users.address'),
            DB::raw('users.email'),
            DB::raw('users.city'),
            DB::raw('users.state'),
            DB::raw('users.zip'),
            DB::raw('users.phone'),
            DB::raw('users.created_at'),
            DB::raw('store.name as store_name'),
        ])
            ->join('store', 'store.id', '=', 'users.store_id')
            ->join(
                'store_states',
                'users.store_state_id',
                '=',
                'store_states.id'
            )
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

    function getStore($id)
    {
        $result = Store::select('name')
            ->where('id', $id)
            ->first();
        return $result->name;
    }

    function getState($id)
    {
        $result = StoreState::select('name')
            ->where('id', $id)
            ->first();
        return $result->name;
    }

    public function map($data): array
    {
        $lastCourse = '';
        $level = [
            1 => 'Freshman',
            2 => 'Sophomore',
            3 => 'Junior',
            4 => 'Senior',
        ];

        $storeId = $data->store_id;
        $stateId = $data->store_state_id;
        $userId = $data->id;
        $store = $this->getStore($storeId);
        $state = $this->getState($stateId);

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
            $data->city,
            $data->state,
            $data->zip,
            $data->phone,
            $data->created_at,
            $store,
            $state,
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
            'Licensee Group/Store Name',
            'Store State',
            'Last Course Completed',
            'Status',
        ];
    }
}
