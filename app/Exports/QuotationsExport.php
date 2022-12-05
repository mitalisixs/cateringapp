<?php

namespace App\Exports;

use App\Quotation;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QuotationsExport implements FromArray, WithHeadings
{
    protected $quotations;

    public function headings(): array
    {
        return [
            'quotation_id',
            'restaurant_name',
            'restaurant_id',
            'created',
            'last_status',
            'client_name',
            'client_id',
            'area_name',
            'area_id',
            'address',
            'address_id',
            'driver_name',
            'driver_id',
            'quotation_value',
            'quotation_delivery',
            
        ];
    }

    public function __construct(array $quotations)
    {
        $this->quotations = $quotations;
    }

    public function array(): array
    {
        return $this->quotations;
    }
}
