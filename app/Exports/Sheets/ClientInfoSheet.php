<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientInfoSheet implements FromArray, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            ['Client Info'],
            ['ID', $this->data['client']['id']],
            ['Name', $this->data['client']['client_name']],
            ['Email', $this->data['client']['email']],
            ['Address', $this->data['client']['address']],
        ];
    }

    public function title(): string
    {
        return 'Client Info';   
    }
}
