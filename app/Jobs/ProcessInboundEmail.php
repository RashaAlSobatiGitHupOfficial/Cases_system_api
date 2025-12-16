<?php

namespace App\Jobs;

use App\Models\InboundEmail;
use App\Services\InboundClientResolver;
use App\Services\InboundCaseCreator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $inboundEmailId;

    public function __construct(int $inboundEmailId)
    {
        $this->inboundEmailId = $inboundEmailId;
    }

    public function handle(): void
    {
        $email = InboundEmail::find($this->inboundEmailId);

        if (!$email || $email->status === 'processed') {
            return;
        }

        //  Resolve client
        (new InboundClientResolver())->resolve($email);
        $email->refresh();

        if (!$email->client_id) {
            throw new \Exception('Client not linked');
        }

        //  Create case
        (new InboundCaseCreator())->createFromEmail($email);

        //  Mark processed
        $email->update([
            'status' => 'processed',
        ]);
    }
}
