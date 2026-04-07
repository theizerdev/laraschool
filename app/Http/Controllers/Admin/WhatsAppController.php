<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;

class WhatsAppController extends Controller
{
    private $apiUrl;
    private $jwtSecret;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url', 'http://localhost:3001');
        $this->jwtSecret = config('whatsapp.jwt_secret', 'base64:ItiVlmjSSgrh2LFDfR0JGtPXHRAthPOWSMw6WyrgwIk=');
    }

    public function dashboard()
    {
        try {
            // Obtener lista de empresas
            $companies = $this->getCompanies();

            return view('admin.whatsapp.dashboard', compact('companies'));
        } catch (\Exception $e) {
            return view('admin.whatsapp.dashboard', [
                'companies' => [],
                'error' => 'Error conectando con API de WhatsApp: ' . $e->getMessage()
            ]);
        }
    }

    public function createCompany(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'webhook_url' => 'nullable|url',
            'rate_limit' => 'nullable|integer|min:1|max:1000'
        ]);

        try {
            $apiKey = 'api-key-' . Str::slug($request->name) . '-' . Str::random(8);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getJwtToken(),
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/api/companies/register', [
                'name' => $request->name,
                'api_key' => $apiKey,
                'webhook_url' => $request->webhook_url,
                'rate_limit_per_minute' => $request->rate_limit ?? 60
            ]);

            if ($response->successful()) {
                return redirect()->route('admin.whatsapp.dashboard')
                    ->with('success', 'Empresa creada exitosamente. API Key: ' . $apiKey);
            } else {
                return back()->withErrors(['error' => 'Error creando empresa: ' . $response->body()]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error de conexión: ' . $e->getMessage()]);
        }
    }

    public function getStatus($companyId, $apiKey)
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-Company-Id' => $companyId
            ])->get($this->apiUrl . '/api/whatsapp/status');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getQR($companyId, $apiKey)
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-Company-Id' => $companyId
            ])->get($this->apiUrl . '/api/whatsapp/qr');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request, $companyId, $apiKey)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'X-Company-Id' => $companyId,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl . '/api/whatsapp/send', [
                'to' => $request->to,
                'message' => $request->message
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getCompanies()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getJwtToken()
        ])->get($this->apiUrl . '/api/companies/list');

        if ($response->successful()) {
            return $response->json()['companies'] ?? [];
        }

        return [];
    }

    private function getJwtToken()
    {
        $payload = [
            'iss' => config('app.name'),
            'iat' => time(),
            'exp' => time() + 3600,
            'service' => 'whatsapp-admin'
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
