<?php

namespace App\Exports;

use App\Models\Sale;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
class All1099ClaimsExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $start;
    protected $end;
    protected $udf;

    function __construct($start, $end, $udf)
    {
        $this->start = $start;
        $this->end = $end;
        $this->udf = $udf;
    }

    public function collection()
    {
        $claimDetail = [];

        $rsaDetail = Sale::select('created_by')
            ->distinct()
            ->where('role', 'rsa')
            ->where('sale_status', 'approved')
            ->where('eligibility', 'yes');

        // TODO start here
        if (empty($this->udf)) {
            // $rsaDetail = $rsaDetail->whereBetween('testingdate', [
            //     $this->start,
            //     $this->end,
            // ]);
            $rsaDetail = $rsaDetail
                ->whereDate('testingdate', '>=', $this->start)
                ->whereDate('testingdate', '<=', $this->end);
        } else {
            // $rsaDetail = $rsaDetail->whereBetween('testingdate', [
            //     $this->start,
            //     $this->end,
            // ]);
            $rsaDetail = $rsaDetail
                ->whereDate('testingdate', '>=', $this->start)
                ->whereDate('testingdate', '<=', $this->end);
        }

        $rsaDetail = $rsaDetail->get();

        foreach ($rsaDetail as $detail) {
            $addedByID = $detail->created_by;

            $row1 = User::select(
                'id',
                'first_name',
                'last_name',
                'email',
                'username',
                'emp_number',
                'address1',
                'address2',
                'city',
                'state',
                'zip'
            )
                ->where('status', 'active')
                ->where('user_type', 'rsa')
                ->where('id', $addedByID)
                ->first();

            $nestedDetail = Sale::selectRaw('sum(reward) as t_reward')
                ->where('role', 'rsa')
                ->where('created_by', $addedByID)
                ->where('sale_status', 'approved')
                ->where('eligibility', 'yes')
                // ->whereBetween('testing_date', [$this->start, $this->end])
                ->whereDate('testing_date', '>=', $this->start)
                ->whereDate('testing_date', '<=', $this->end)
                ->get();

            foreach ($nestedDetail as $row) {
                if ($row->t_reward > 0) {
                    $claim = [];

                    $claim['addedByID'] = $addedByID;
                    $claim['role'] = 'rsa';
                    $claim['first_name'] = $row1->first_name;
                    $claim['last_name'] = $row1->last_name;
                    $claim['address1'] = $row1->address1;
                    $claim['address2'] = $row1->address2;
                    $claim['city'] = $row1->city;
                    $claim['state'] = $row1->state;
                    $claim['zip'] = $row1->zip;
                    $claim['email'] = $row1->email;
                    $claim['username'] = $row1->username;
                    $claim['emp_number'] = $row1->emp_number;
                    $claim['total_reward'] = $row->t_reward;

                    $claimDetail[] = $claim;
                }
            }
        }

        return collect($claimDetail);
    }

    public function map($data): array
    {
        return [
            '$' . $data['total_reward'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['address1'],
            $data['address2'],
            $data['city'],
            $data['state'],
            $data['zip'],
            'US',
            'ASHLEY SLEEP ELITE',
            '',
            $data['emp_number'],
        ];
    }

    public function headings(): array
    {
        return [
            'Denomination',
            'Email',
            'First Name',
            'Last Name',
            'Address 1',
            'City',
            'State',
            'Zip',
            'UDF2',
        ];
    }
}
