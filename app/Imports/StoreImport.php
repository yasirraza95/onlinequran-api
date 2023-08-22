<?php

namespace App\Imports;

use App\Models\Store;
use App\Models\StoreState;
use App\Models\StoreCity;
use App\Models\User;
use Illuminate\Database\DBAL\TimestampType;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
class StoreImport implements
    ToModel,
    SkipsEmptyRows,
    WithHeadingRow,
    SkipsOnError,
    SkipsOnFailure,
    WithValidation
{
    use Importable, SkipsErrors, SkipsFailures;

    private $dup = [];
    private $nondup = [];
    private $i = 0;
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

    public function rules(): array
    {
        return [
            'account' => 'required|numeric',
            'store_name' => 'required|string',
            'store_address' => 'required|string',
            'city' => 'required|alpha',
            'state' => 'required|alpha',
            'zip' => 'required|numeric',
            'dos' => 'required|string',
            'vp' => 'required|string',
        ];
    }

    public function model(array $row)
    {
        if (
            $row['account'] != '' ||
            $row['store_name'] != '' ||
            $row['store_address'] != '' ||
            $row['city'] != '' ||
            $row['state'] != '' ||
            $row['zip'] != '' ||
            $row['dos'] != '' ||
            $row['vp'] != ''
        ) {
            $storeName = $row['store_name'];
            $storeAddress = $row['store_address'];
            $storeZip = $row['zip'];
            $storeAccount = $row['account'];
            $cityName = $row['city'];
            $stateName = $row['state'];
            $dosName = $row['dos'];
            $nDosName = $row['vp'];
            $visaCard = 'no';

            $dosId = $this->getUser($dosName);
            $nDosId = $this->getUser($nDosName);
            // $cityId = '1';

            $getStoreStateName = $this->checkState($stateName);
            $cityId = $this->checkCity(
                trim($cityName),
                $getStoreStateName,
                $storeZip
            );

            $counter = Store::where(
                'address',
                'like',
                '%' . $storeAddress . '%'
            )
                ->where('zip', $storeZip)
                ->count();

            if ($counter > 0) {
                $this->dup[$this->i]['name'] = $storeName;
                $this->dup[$this->i]['address'] = $storeAddress;
                $this->dup[$this->i]['zip'] = $storeZip;
                $this->dup[$this->i]['account'] = $storeAccount;
                $this->i++;

                $exist = Store::where(
                    'address',
                    'like',
                    '%' . $storeAddress . '%'
                )
                    ->where('zip', $storeZip)
                    ->first();

                $update = [
                    'name' => $storeName,
                    'address' => $storeAddress,
                    'zip' => $storeZip,
                    'account' => $storeAccount,
                    'dos' => $dosId,
                    'ndos' => $nDosId,
                    'visa_card' => $visaCard,
                    'updated_by' => request()->created_by,
                    'updated_ip' => request()->created_ip,
                ];

                $update = Store::where('id', $exist->id)->update($update);
            } else {
                $this->nondup[] = $storeName;

                $insertData = [
                    'name' => $storeName,
                    'city_id' => $cityId,
                    'address' => $storeAddress,
                    'zip' => $storeZip,
                    'dos' => $dosId,
                    'ndos' => $nDosId,
                    'account' => $storeAccount,
                    'visa_card' => 'yes',
                    'created_by' => request()->created_by,
                    'created_ip' => request()->created_ip,
                ];

                $insertQry = Store::create($insertData);
            }
        }
    }

    function getUser($username)
    {
        $getUser = User::select('id')
            ->where('username', $username)
            ->first();

        if (!empty($getUser)) {
            return $getUser->id;
        }
        return 0;
    }

    public function checkState($name)
    {
        $state = StoreState::select()
            ->where('name', $name)
            ->get();

        if ($state->count() > 0) {
            return $state[0]->id;
        } else {
            $data = [
                'name' => $name,
                'created_by' => request()->created_by,
                'created_ip' => request()->created_ip,
            ];

            return $id = StoreState::create($data)->id;
        }
    }

    public function checkCity($name, $stateId, $storeZip)
    {
        $city = StoreCity::select()
            ->where('name', $name)
            ->where('state_id', $stateId)
            ->get();

        if ($city->count() > 0) {
            return $city[0]->id;
        } else {
            $data = [
                'state_id' => $stateId,
                'name' => $name,
                'zip' => $storeZip,
                'created_by' => request()->created_by,
                'created_ip' => request()->created_ip,
            ];

            return $id = StoreCity::create($data)->id;
        }
    }

    public function dupData(): array
    {
        return ['dataDup' => $this->dup];
    }

    public function nondupData(): array
    {
        return ['nondataDup' => $this->nondup];
    }
}
