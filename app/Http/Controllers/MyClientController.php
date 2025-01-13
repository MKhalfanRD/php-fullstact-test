<?php

namespace App\Http\Controllers;

use App\Models\MyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

class MyClientController extends Controller
{
    // Menampilkan semua data
    public function index()
    {
        $clients = MyClient::all();
        return response()->json($clients);
    }

    // Menampilkan data berdasarkan slug (slug sebagai key redis)
    public function show($slug)
    {
        // Cek apakah data ada di Redis
        $cachedData = Redis::get($slug);
        if ($cachedData) {
            return response()->json(json_decode($cachedData));
        }

        // Jika tidak ada di Redis, ambil dari database
        $client = MyClient::where('slug', $slug)->first();
        if ($client) {
            Redis::set($slug, json_encode($client)); // Simpan ke Redis
            return response()->json($client);
        }

        return response()->json(['message' => 'Client not found'], 404);
    }

    // Menyimpan data baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:250',
            'slug' => 'required|string|max:100|unique:my_client',
            'client_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            // tambahkan validasi lain sesuai kebutuhan
        ]);

        // Jika ada file image untuk logo
        if ($request->hasFile('client_logo')) {
            $logoPath = $request->file('client_logo')->store('public/client_logos');
            $validatedData['client_logo'] = Storage::url($logoPath);
        }

        $client = MyClient::create($validatedData);
        Redis::set($client->slug, json_encode($client)); // Simpan ke Redis

        return response()->json($client, 201);
    }

    // Memperbarui data
    public function update(Request $request, $slug)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:250',
            'slug' => 'nullable|string|max:100',
            'client_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            // tambahkan validasi lain sesuai kebutuhan
        ]);

        $client = MyClient::where('slug', $slug)->first();
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        // Update data
        $client->update($validatedData);

        // Menghapus data Redis lama dan menyimpan yang baru
        Redis::del($slug);
        Redis::set($client->slug, json_encode($client));

        return response()->json($client);
    }

    // Menghapus data
    public function destroy($slug)
    {
        $client = MyClient::where('slug', $slug)->first();
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        // Hanya mengupdate kolom deleted_at dan menghapus data dari Redis
        $client->deleted_at = now();
        $client->save();

        Redis::del($slug); // Hapus data Redis

        return response()->json(['message' => 'Client deleted']);
    }
}
