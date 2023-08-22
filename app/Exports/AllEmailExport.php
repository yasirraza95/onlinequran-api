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

class AllEmailExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    //FIXME
    public function collection()
    {
        return $result = User::select([DB::raw('users.email')])
            ->join('store', 'store.id', '=', 'users.store_id')
            ->join(
                'store_states',
                'users.store_state_id',
                '=',
                'store_states.id'
            )
            ->whereIn('users.user_type', ['rsa', 'manager'])
            ->get();
    }

    public function map($data): array
    {
        return [$data->email];
    }

    public function headings(): array
    {
        return ['Email'];
    }
}
