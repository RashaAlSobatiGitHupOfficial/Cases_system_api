<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\GmailController;

Route::get('/gmail/auth', function () {
    $client = new \Google\Client();
    $client->setAuthConfig(storage_path('app/gmail/credentials.json'));
    $client->setRedirectUri('http://127.0.0.1:8000/gmail/callback');
    $client->setScopes([
        'https://www.googleapis.com/auth/gmail.readonly'
    ]);
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return redirect($client->createAuthUrl());
});

Route::get('/gmail/callback', [GmailController::class, 'callback']);
