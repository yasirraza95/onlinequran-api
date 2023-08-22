<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;
class SelectedClaimsExport implements
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
        $ids = explode('+', $this->saleId);

        return $result = Sale::select(
            'users.emp_number',
            'users.username',
            'users.first_name',
            'users.last_name',
            'users.email',
            'sales.deliver_invoice',
            'users.address1',
            'users.address2',
            'store.account',
            'sales.role',
            'sales.file',
            'sales.sale_status',
            'sales.split_sale_status',
            'sales.ship_quantity',
            'sales.double_spiff'
        )
            ->selectRaw(
                'store_states.name as store_state,
                store.name as store_name,
                store.zip as store_zip,
                store_cities.name as store_city,
                products.name as product_name,
                products.number as product_number,
                product_size.size as product_size,
                product_size.price as product_price,
                product_size.code as product_code'
            )
            ->join('users', 'users.id', '=', 'sales.created_by')
            ->join('products', 'products.id', '=', 'sales.product_id')
            ->join('product_size', 'product_size.id', '=', 'sales.size_id')
            ->join('store_states', 'store_states.id', '=', 'sales.state_id')
            ->join('store_cities', 'store_cities.id', '=', 'sales.city_id')
            ->join('store', 'store.id', '=', 'sales.store_id')
            ->where('sales.deleted_status', 'active')
            ->whereIn('sales.id', $ids)
            ->orderBy('users.id', 'desc')
            ->get();
    }

    public function map($data): array
    {
        $pdfLink = 'https://ashleysleepelite.com/uploads/' . $data->file;
        $strLink = '=HYPERLINK("' . $pdfLink . '","See Attachment")';

        $splitStatus = 'No';
        $totalPrice = $data->ship_quantity * $data->product_price;
        if ($data->split_sale_status == 'split') {
            $splitStatus = 'Yes';
            $totalPrice = ($data->ship_quantity * $data->product_price) / 2;
        }

        $productNumber = $data->product_number . ' ' . $data->product_code;
        $productSize = $data->product_size . ' (' . $data->product_code . ')';

        return [
            $data->emp_number,
            $data->username,
            $data->first_name,
            $data->last_name,
            $data->email,
            $data->deliver_invoice,
            $data->address1,
            $data->address2,
            $data->account,
            $data->store_state,
            $data->store_name,
            $data->store_city,
            $data->store_zip,
            $data->product_name,
            $productNumber,
            $productSize,
            ucfirst($data->role),
            $splitStatus,
            $data->ship_quantity,
            '$' . $data->product_price,
            '$' . $totalPrice,
            '$' . $data->double_spiff,
            $strLink,
            ucfirst($data->sale_status),
        ];
    }

    public function headings(): array
    {
        return [
            'Employee No',
            'User Name',
            'First Name',
            'Last Name',
            'Email',
            'Invoice No',
            'Address 1',
            'Address 2',
            'Account No',
            'Store State',
            'Store Name',
            'Store City',
            'Zip',
            'Product Name',
            'Product Number',
            'Product Size',
            'Role',
            'Split Sale',
            'Quantity',
            'Unit Price',
            'Total Price',
            'Double Spiff',
            'Attachment',
            'Status',
        ];
    }
}
