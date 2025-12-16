<?php

namespace App\Services;

use App\Models\Client;
use App\Models\InboundEmail;

class InboundClientResolver
{
    public function resolve(InboundEmail $email): Client
    {
        $client = Client::where('email', $email->from_email)->first();

        if (!$client) {
            $client = Client::create([
                'client_name' => $email->from_name ?? $email->from_email,
                'email'       => $email->from_email,
                'address'     => 'Unknown',
            ]);
        }

        $email->update([
            'client_id' => $client->id,
        ]);

        return $client;
    }
}
