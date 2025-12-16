<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use App\Models\InboundEmail;
use Carbon\Carbon;
use App\Services\InboundClientResolver;
use App\Services\InboundCaseCreator;
use App\Jobs\ProcessInboundEmail;


class GmailFetch extends Command
{
    protected $signature = 'gmail:fetch {--limit=5}';
    protected $description = 'Fetch emails from Gmail inbox';

    public function handle()
    {
        $credentialsPath = storage_path('app/gmail/credentials.json');
        $tokenPath = storage_path('app/gmail/token.json');

        if (!file_exists($credentialsPath) || !file_exists($tokenPath)) {
            $this->error('Missing credentials.json or token.json');
            return;
        }

        // Init Google Client
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes(['https://www.googleapis.com/auth/gmail.readonly']);
        $client->setAccessType('offline');

        // Load token
        $token = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($token);

        // Refresh token if expired
        if ($client->isAccessTokenExpired()) {
            if (!empty($token['refresh_token'])) {
                $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                file_put_contents($tokenPath, json_encode($client->getAccessToken(), JSON_PRETTY_PRINT));
            } else {
                $this->error('Access token expired and no refresh token available.');
                return;
            }
        }

        $service = new Gmail($client);

        // Fetch messages list
        $limit = (int) $this->option('limit');
        try {
            $messages = $service->users_messages->listUsersMessages('me', [
                'labelIds' => ['INBOX'],
                'maxResults' => $limit,
            ]);
        } catch (\Exception $e) {
            $this->error('Temporary Gmail connection issue. Retry later.');
            $this->error($e->getMessage());
            return;
        }


        if (empty($messages->getMessages())) {
            $this->info('No messages found.');
            return;
        }

        foreach ($messages->getMessages() as $msg) {

    //  Check duplicate
    
    $inbound = InboundEmail::where('gmail_message_id', $msg->getId())->first();

    if ($inbound?->status === 'processed') {
            $this->line('Skipped processed email: ' . $msg->getId());
            continue;
        }


    // git the data
    $message = $service->users_messages->get('me', $msg->getId(), [
        'format' => 'full'
    ]);


    $headers = collect($message->getPayload()->getHeaders())
        ->keyBy('name')
        ->map(fn($h) => $h->getValue());
    $body = $this->extractEmailBody($message->getPayload());

    $subject = $headers['Subject'] ?? null;


    //  Parse From
    $fromRaw = $headers['From'] ?? null;
    $fromEmail = null;
    $fromName = null;

    if ($fromRaw) {
        if (preg_match('/(.*)<(.+)>/', $fromRaw, $matches)) {
            $fromName = trim($matches[1], '" ');
            $fromEmail = trim($matches[2]);
        } else {
            $fromEmail = trim($fromRaw);
        }
    }

    //  Parse date
    $receivedAt = null;
    if (!empty($headers['Date'])) {
        try {
            $receivedAt = Carbon::parse($headers['Date']);
        } catch (\Exception $e) {
            $receivedAt = now();
        }
    }
        // 🔴 تجاهل الرسائل النظامية
    if ($this->isSystemEmail($fromEmail, $subject)) {
        $this->line('Skipped system email: ' . ($fromEmail ?? 'unknown'));
        continue;
    }


    //  Create inbound email ONLY if not exists
    if (!$inbound) {
        $inbound = InboundEmail::create([
            'provider' => 'gmail',
            'gmail_message_id' => $message->getId(),
            'gmail_thread_id'  => $message->getThreadId(),
            'from_email'       => $fromEmail,
            'from_name'        => $fromName,
            'subject'          => $headers['Subject'] ?? null,
            'received_at'      => $receivedAt,
            'status'           => 'new',
            'raw_headers'      => $headers->toArray(),
            'raw_body'         => $body,

        ]);
    }

ProcessInboundEmail::dispatch($inbound->id);

$this->info('Queued email #' . $inbound->id);

            // 3️⃣ إخراج معلومات للإطلاع
            $this->info('Saved new email: ' . $message->getId());
            $this->line('From: ' . ($fromEmail ?? 'N/A'));
            $this->line('Subject: ' . ($headers['Subject'] ?? 'N/A'));

}


        $this->info('Done fetching emails.');
    }
    protected function isSystemEmail(?string $fromEmail, ?string $subject): bool
    {
        if (!$fromEmail && !$subject) {
            return true;
        }

        $fromEmail = strtolower($fromEmail ?? '');
        $subject   = strtolower($subject ?? '');

        $blockedSenders = [
            'no-reply@google.com',
            'no-reply@accounts.google.com',
            'security-noreply@google.com',
        ];

        foreach ($blockedSenders as $blocked) {
            if (str_contains($fromEmail, $blocked)) {
                return true;
            }
        }

        $blockedSubjects = [
            'security alert',
            'تنبيه أمني',
            'verify your account',
            'new sign-in',
            'password changed',
            'finish setting up',
        ];

        foreach ($blockedSubjects as $word) {
            if (str_contains($subject, $word)) {
                return true;
            }
        }

        return false;
    }
protected function extractEmailBody($payload): string
{
    // حالة بسيطة (بدون parts)
    if ($payload->getBody() && $payload->getBody()->getData()) {
        return $this->decodeBody($payload->getBody()->getData());
    }

    // حالة متعددة الأجزاء (HTML / TEXT)
    if ($payload->getParts()) {
        foreach ($payload->getParts() as $part) {
            if ($part->getMimeType() === 'text/plain' && $part->getBody()->getData()) {
                return $this->decodeBody($part->getBody()->getData());
            }
        }

        // fallback: HTML
        foreach ($payload->getParts() as $part) {
            if ($part->getMimeType() === 'text/html' && $part->getBody()->getData()) {
                return strip_tags($this->decodeBody($part->getBody()->getData()));
            }
        }
    }

    return 'No email body found.';
}

protected function decodeBody(string $data): string
{
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

}
