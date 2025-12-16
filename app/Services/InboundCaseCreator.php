<?php

namespace App\Services;

use App\Models\InboundEmail;
use App\Models\CaseModel;

class InboundCaseCreator
{
    public function createFromEmail(InboundEmail $email)
    {
        if (!$email->client_id) {
            throw new \RuntimeException(
                'InboundEmail #' . $email->id . ' has no client_id'
            );
        }

        if ($email->case_id) {
            return $email->case;
        }

        $case = CaseModel::create([
            'client_id' => $email->client_id,
            'priority_id' => null,
            'user_id' => null,
            'title' => $email->subject ?? 'Email case',
            'description' => $email->raw_body ?? 'Created from email',
            'type' => 'enquery',
            'way_entry' => 'email',
            'status' => 'opened',
        ]);

        $email->update([
            'case_id' => $case->id,
            'status'  => 'processed',
        ]);

        return $case;
    }
}
