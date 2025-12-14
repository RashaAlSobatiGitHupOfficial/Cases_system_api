<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;



class ClientStatisticExport implements WithMultipleSheets
{
  protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new Sheets\ClientInfoSheet($this->data),
            new Sheets\ClientStatsSheet($this->data),
            new Sheets\ClientLatestCasesSheet($this->data),
            new Sheets\ClientTeamSheet($this->data),
        ];
    }
}
