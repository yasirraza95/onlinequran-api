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

class SelectedPendingClaimsExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $saleId;

    function __construct($saleId)
    {
        $this->saleId = $saleId;
    }

    public function collection()
    {
        $expIds = explode('+', $this->saleId);
        foreach ($expIds as $saleId) {
            $this->_id[] = $saleId;
        }

        $whereIn = '"' . implode('","', $this->_id) . '"';
        
        $query =
            "SELECT u.*, s.name AS storeName FROM users u, store_cities sc, store s WHERE u.user_type = 'rsa' AND u.store_id = s.id AND s.city_id = sc.id AND u.deleted_at is NULL AND u.id in (" .
            $whereIn .
            ")";

        $newResult = DB::select($query);
        return collect($newResult);
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
            'Address 1',
            'Address 2',
            'Email',
            'Employee No',
            'Account No',
            'Invoice No',
            'Store Name',
            'State Name',
            'City Name',
            'Zip',
            'Product Name',
            'Product Number',
            'Role',
            'Split Sale',
            'Quantity',
            'Unit Price',
            'Total Price',
            'Attachment',
            'Status',
        ];
    }
}
