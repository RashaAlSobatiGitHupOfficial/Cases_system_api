<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    // public function index()
    // {
    //     $clients = Client::all()->map(function ($client) {
    //         $client->logo_url = $client->logo ? asset('storage/' . $client->logo) : null;
    //         return $client;
    //     });

    //     return response()->json($clients);
    // }

    // public function index()
    // {
    //     $clients = Client::latest()
    //         ->paginate(5)
    //         ->through(function ($client) {
    //             $client->logo_url = $client->logo ? asset('storage/' . $client->logo) : null;
    //             return $client;
    //         });

    //     return response()->json($clients);
    // }
    public function index(Request $request)
    {
        $search = $request->input('search');

        $clients = Client::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        })
            ->latest()
            ->paginate(5)
            ->through(function ($client) {
                $client->logo_url = $client->logo ? asset('storage/' . $client->logo) : null;
                return $client;
            });

        return response()->json($clients);
    }


    public function store(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:100',
            'address'     => 'required|string|max:255',
            'email'       => 'required|email|unique:clients,email',
            'logo'        => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // Save logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('clients', 'public');
        }

        $client = Client::create([
            'client_name' => $request->client_name,
            'address'     => $request->address,
            'email'       => $request->email,
            'logo'        => $logoPath,
        ]);

        $client->logo_url = $logoPath ? asset('storage/' . $logoPath) : null;

        return response()->json([
            'message' => 'Client created successfully',
            'client'  => $client,
        ], 201);
    }

    public function show(Client $client)
    {
        $client->logo_url = $client->logo ? asset('storage/' . $client->logo) : null;

        return response()->json(['client' => $client]);
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'client_name' => 'sometimes|required|string|max:100',
            'address'     => 'sometimes|required|string|max:255',
            'email'       => 'sometimes|required|email|unique:clients,email,' . $client->id,
            'logo'        => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // If new logo uploaded replace old one
        if ($request->hasFile('logo')) {
            if ($client->logo && Storage::disk('public')->exists($client->logo)) {
                Storage::disk('public')->delete($client->logo);
            }

            $client->logo = $request->file('logo')->store('clients', 'public');
        }

        $client->update($request->only(['client_name', 'address', 'email']));

        $client->logo_url = $client->logo ? asset('storage/' . $client->logo) : null;

        return response()->json([
            'message' => 'Client updated successfully',
            'client'  => $client,
        ]);
    }

    public function destroy(Client $client)
    {
        if ($client->logo && Storage::disk('public')->exists($client->logo)) {
            Storage::disk('public')->delete($client->logo);
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted successfully']);
    }
}
