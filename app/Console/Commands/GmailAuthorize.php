<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as GoogleClient;

class GmailAuthorize extends Command
{
    protected $signature = 'gmail:authorize';
    protected $description = 'Authorize Gmail API and store access token';

    public function handle()
    {
        $credentialsPath = storage_path('app/gmail/credentials.json');
        $tokenPath = storage_path('app/gmail/token.json');

        if (!file_exists($credentialsPath)) {
            $this->error('credentials.json not found in storage/app/gmail');
            return;
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.readonly'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // Load existing token if exists
        if (file_exists($tokenPath)) {
            $this->info('Token already exists. Authorization already done.');
            return;
        }

        // Generate auth URL
        $authUrl = $client->createAuthUrl();

        $this->info("Open this URL in your browser:");
        $this->line($authUrl);

        $this->line('');
        $authCode = $this->ask('Paste the authorization code here');

        // Exchange auth code for access token
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if (isset($accessToken['error'])) {
            $this->error('Authorization failed');
            return;
        }

        // Save token
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }

        file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT));

        $this->info('Authorization successful!');
        $this->info('Token saved to/app/gmail/token.json');
    }
}
