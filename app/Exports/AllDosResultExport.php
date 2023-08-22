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

class AllDosResultExport implements
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
            $query = "select u.id as rsaID, u.emp_number, u.city, u.zip, u.state, u.username, u.first_name, u.last_name, u.email, u.address, u.created_at, u.phone, u.store_id, u.store_state_id, s.id, s.name, s.account, ss.name as state_name from users u, store s, store_states ss where u.store_id = s.id and u.store_state_id = ss.id AND s.dos = '$this->dosId' AND u.deleted_at is NULL ";
        } elseif ($this->dosId == 1244) {
            $query =
                "select u.id as rsaID, u.emp_number, u.city, u.zip, u.state, u.username, u.first_name, u.last_name, u.email, u.address, u.created_at, u.phone, u.store_id, u.store_state_id, s.id, s.name, s.account, ss.name as state_name from users u, store s, store_states ss where u.store_id = s.id and u.store_state_id = ss.id AND s.account = '9977700' AND u.deleted_at is NULL ";
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
