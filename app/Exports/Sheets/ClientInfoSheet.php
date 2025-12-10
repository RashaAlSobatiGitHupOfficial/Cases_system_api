<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;

class ClientInfoSheet implements FromArray
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
}
