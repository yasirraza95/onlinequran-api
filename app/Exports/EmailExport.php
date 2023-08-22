<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Store;
use App\Models\StoreState;
use App\Models\StoreCity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class EmailExport implements
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
        $result = User::select([DB::raw('users.email')])
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

    public function map($data): array
    {
        $email = $data->email;

        return [$email];
    }

    public function headings(): array
    {
        return ['Email'];
    }
}
