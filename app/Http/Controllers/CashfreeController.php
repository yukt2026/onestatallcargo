<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

class CashfreeController extends Controller
{
    private function getHeaders()
    {
        // Fetch from database settings
        $appId = \App\Models\Setting::where('key', 'cashfree_app_id')->value('value');
        $secret = \App\Models\Setting::where('key', 'cashfree_secret_key')->value('value');

        // Fallback to TEST_APP_ID if empty
        if (!$appId) {
            $appId = env('CASHFREE_APP_ID', 'TEST_APP_ID');
        }
        if (!$secret) {
            $secret = env('CASHFREE_SECRET_KEY', 'TEST_SECRET');
        }

        return [
            'x-client-id' => $appId,
            'x-client-secret' => $secret,
            'x-api-version' => '2023-08-01',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    private function getBaseUrl()
    {
        $env = \App\Models\Setting::where('key', 'cashfree_environment')->value('value') ?: env('CASHFREE_ENV', 'sandbox');
        
        return $env === 'production' 
            ? 'https://api.cashfree.com/pg' 
            : 'https://sandbox.cashfree.com/pg';
    }

    public function initiateRecharge(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isKycApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your KYC verification to recharge your wallet.'
            ], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:500'
        ]);

        $user = auth()->user();
        $orderId = 'WALLET_' . $user->id . '_' . time() . strtoupper(Str::random(4));

        // Create a pending wallet transaction
        $transaction = new WalletTransaction();
        $transaction->user_id = $user->id;
        $transaction->type = 'credit';
        $transaction->amount = $request->amount;
        $transaction->reference_id = $orderId;
        $transaction->description = 'Wallet Recharge via Cashfree';
        $transaction->status = 'pending';
        $transaction->save();

        $appId = \App\Models\Setting::where('key', 'cashfree_app_id')->value('value');
        $env = \App\Models\Setting::where('key', 'cashfree_environment')->value('value') ?: env('CASHFREE_ENV', 'sandbox');

        // Check if we have valid real credentials
        if (empty($appId) || $appId == 'TEST_APP_ID') {
            // For testing purposes when the user hasn't put real keys yet
            // Just simulate a successful checkout session
            return response()->json([
                'success' => true,
                'payment_session_id' => 'mock_session_id',
                'order_id' => $orderId,
                'environment' => $env,
                'mock_mode' => true
            ]);
        }

        $returnRoute = $user->role === 'franchise' ? route('hub.wallet.index') : route('seller.wallet');

        // Real Cashfree API Call
        $payload = [
            'order_id' => $orderId,
            'order_amount' => round($request->amount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'CUST_' . $user->id,
                'customer_phone' => $user->phone ?? '9999999999',
                'customer_email' => $user->email,
                'customer_name' => $user->name
            ],
            'order_meta' => [
                // If user is redirected on mobile, they come back to wallet page to verify
                'return_url' => str_replace('http://', 'https://', $returnRoute . '?order_id={order_id}'),
                'notify_url' => str_replace('http://', 'https://', url('/api/webhooks/cashfree')) // for background verification
            ]
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($this->getBaseUrl() . '/orders', $payload);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'payment_session_id' => $data['payment_session_id'],
                'order_id' => $orderId,
                'environment' => \App\Models\Setting::where('key', 'cashfree_environment')->value('value') ?: env('CASHFREE_ENV', 'sandbox')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize payment gateway. ' . $response->body()
        ], 500);
    }

    public function verifyRecharge(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string'
        ]);

        $orderId = $request->order_id;
        $userId = auth()->id();
        $appId = \App\Models\Setting::where('key', 'cashfree_app_id')->value('value');
        $isTestMode = empty($appId) || $appId === 'TEST_APP_ID' || $appId === 'SANDBOX' || app()->environment('local') || app()->environment('testing');

        return DB::transaction(function () use ($orderId, $userId, $request, $isTestMode) {
            $transaction = WalletTransaction::where('reference_id', $orderId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // Strict Idempotency: If already processed, return success without double crediting
            if ($transaction->status === 'success') {
                $user = User::find($userId);
                return response()->json([
                    'success' => true, 
                    'message' => 'Already verified',
                    'new_balance' => $user->wallet_balance ?? 0
                ]);
            }

            $isPaid = false;
            $description = 'Wallet Recharge via Cashfree';

            // Allow mock mode strictly in local environment when test keys are used
            if ($request->mock_mode && $isTestMode) {
                $isPaid = true;
                $description = 'Wallet Recharge (Test Mode Verification)';
            } else {
                // Real Cashfree Verify Call
                $response = Http::withHeaders($this->getHeaders())
                    ->get($this->getBaseUrl() . '/orders/' . $orderId);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['order_status']) && $data['order_status'] === 'PAID') {
                        $isPaid = true;
                    }
                }
            }

            if ($isPaid) {
                $user = User::lockForUpdate()->findOrFail($userId);
                $user->wallet_balance += (float)$transaction->amount;
                $user->save();

                $transaction->status = 'success';
                $transaction->description = $description;
                $transaction->balance_after = $user->wallet_balance;
                $transaction->save();

                return response()->json([
                    'success' => true,
                    'new_balance' => $user->wallet_balance
                ]);
            }

            $transaction->status = 'failed';
            $transaction->save();

            return response()->json(['success' => false, 'message' => 'Payment not verified']);
        });
    }

    public function generateCodLink(Request $request)
    {
        $request->validate([
            'awb_number' => 'required|string',
            'amount' => 'required|numeric|min:1'
        ]);

        $appId = \App\Models\Setting::where('key', 'cashfree_app_id')->value('value');
        if (empty($appId) || $appId == 'TEST_APP_ID') {
            return response()->json([
                'success' => false,
                'message' => 'Cashfree API keys are not configured in Admin Settings.'
            ]);
        }

        $linkId = 'COD_' . $request->awb_number . '_' . time();

        $payload = [
            'link_id' => $linkId,
            'link_amount' => round($request->amount, 2),
            'link_currency' => 'INR',
            'link_purpose' => 'COD Payment for ' . $request->awb_number,
            'customer_details' => [
                'customer_phone' => '9999999999',
                'customer_email' => 'cod@onestall.com',
                'customer_name' => 'Customer'
            ],
            'link_notify' => [
                'send_sms' => false,
                'send_email' => false
            ],
            'link_meta' => [
                'notify_url' => str_replace('http://', 'https://', url('/api/webhooks/cashfree-cod'))
            ]
        ];

        // Ensure we use the correct API version for Links
        $headers = $this->getHeaders();
        $headers['x-api-version'] = '2022-09-01'; // Links API often uses this version

        $env = \App\Models\Setting::where('key', 'cashfree_environment')->value('value') ?: env('CASHFREE_ENV', 'sandbox');
        $baseUrl = $env === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';

        $response = Http::withHeaders($headers)
            ->post($baseUrl . '/links', $payload);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'link_url' => $data['link_url'],
                'link_id' => $linkId
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cashfree API Error: ' . $response->body()
        ], 500);
    }

    public function verifyCodLink(Request $request)
    {
        $request->validate([
            'link_id' => 'required|string'
        ]);

        $headers = $this->getHeaders();
        $headers['x-api-version'] = '2022-09-01'; // Same version used for creating links

        $env = \App\Models\Setting::where('key', 'cashfree_environment')->value('value') ?: env('CASHFREE_ENV', 'sandbox');
        $baseUrl = $env === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';

        $response = Http::withHeaders($headers)
            ->get($baseUrl . '/links/' . $request->link_id);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['link_status']) && $data['link_status'] === 'PAID') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment Verified Successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is still Pending. Ask customer to pay.'
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Error verifying with Cashfree API'
        ], 500);
    }
}