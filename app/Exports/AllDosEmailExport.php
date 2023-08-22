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

class AllDosEmailExport implements
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

    //FIXME
    public function collection()
    {
        $query = '';
        if ($this->dosId != 1244) {
            $query = "select u.email from users u, store s, store_states ss where u.store_id = s.id and u.store_state_id = ss.id AND s.dos = '$this->dosId' AND u.deleted_at is NULL ";
        } elseif ($this->dosId == 1244) {
            $query =
                "select u.email where u.store_id = s.id and u.store_state_id = ss.id AND s.account = '9977700' AND u.deleted_at is NULL ";
        }

        $newResult = DB::select($query);
        return collect($newResult);
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
