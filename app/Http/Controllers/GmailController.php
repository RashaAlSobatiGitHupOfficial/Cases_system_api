<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Storage;

class GmailController extends Controller
{
 public function callback(Request $request)
{
    if (!$request->has('code')) {
        return response('Authorization code missing', 400);
    }

    $client = new \Google\Client();
    $client->setAuthConfig(storage_path('app/gmail/credentials.json'));
    $client->setRedirectUri('http://127.0.0.1:8000/gmail/callback');
    $client->setScopes([
        'https://www.googleapis.com/auth/gmail.readonly'
    ]);
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    $token = $client->fetchAccessTokenWithAuthCode($request->code);

    if (isset($token['error'])) {
        return response()->json($token, 500);
    }

    $dir = storage_path('app/gmail');
    $file = $dir . '/token.json';

    //  إنشاء المجلد يدويًا إن لم يوجد
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    //  كتابة مباشرة بدون Laravel
    file_put_contents(
        $file,
        json_encode($token, JSON_PRETTY_PRINT)
    );

    return response()->json([
        'message' => 'Token written using native PHP',
        'file_exists' => file_exists($file),
        'path' => $file,
    ]);
}


}
