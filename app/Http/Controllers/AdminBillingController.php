<?php
namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBillingController extends Controller {

    public function index()
    {
        // Calculate COD pending remittance grouped by Seller
        $ledgers = Shipment::where('is_cod', true)
            ->where('status', 'Delivered')
            ->where('cod_remitted', false)
            ->select('user_id', DB::raw('SUM(COALESCE(cod_amount, invoice_value, 0)) as total_cod'), DB::raw('COUNT(id) as total_shipments'))
            ->groupBy('user_id')
            ->with('user')
            ->paginate(15)->withQueryString();
            
        $totalPendingCOD = Shipment::where('is_cod', true)->where('status', 'Delivered')->where('cod_remitted', false)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));
        $totalSettledCOD = Shipment::where('is_cod', true)->where('status', 'Delivered')->where('cod_remitted', true)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));

        return view('admin.billing.index', compact('ledgers', 'totalPendingCOD', 'totalSettledCOD'));
    }

    public function remit(Request $request, $userId)
    {
        DB::transaction(function () use ($userId) {
            $user = User::findOrFail($userId);

            // Get total pending COD
            $totalCod = Shipment::where('user_id', $userId)
                ->where('is_cod', true)
                ->where('status', 'Delivered')
                ->where('cod_remitted', false)
                ->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));

            // If wallet is negative, clear it from the COD payout (up to the total COD amount)
            if ($user->wallet_balance < 0) {
                $due = abs($user->wallet_balance);
                
                if ($totalCod >= $due) {
                    // Settle full due
                    $user->wallet_balance = 0;
                } else {
                    // Settle partial due
                    $user->wallet_balance += $totalCod;
                }
                $user->save();
            }

            // Mark COD as remitted
            Shipment::where('user_id', $userId)
                ->where('is_cod', true)
                ->where('status', 'Delivered')
                ->where('cod_remitted', false)
                ->update([
                    'cod_remitted' => true,
                    'cod_remittance_status' => 'processed',
                    'cod_remittance_date' => now(),
                ]);
        });

        return back()->with('success', 'COD Remittance settled successfully, and any pending wallet dues were adjusted automatically.');
    }

    public function remitEarlyCod(Request $request, $userId)
    {
        DB::transaction(function () use ($userId) {
            $user = User::findOrFail($userId);

            // Get all pending COD shipments
            $shipments = Shipment::where('user_id', $userId)
                ->where('is_cod', true)
                ->where('status', 'Delivered')
                ->where('cod_remitted', false)
                ->get();

            if ($shipments->isEmpty()) return;

            $totalCod = $shipments->sum(function($s) {
                return (float)($s->cod_amount ?? $s->invoice_value ?? 0);
            });
            
            // Calculate fee
            $feePercent = $user->early_cod_fee ?? 0;
            $feeAmount = ($totalCod * $feePercent) / 100;
            $netPayout = $totalCod - $feeAmount;

            // Credit the seller's wallet
            $user->wallet_balance += $netPayout;
            $user->save();

            // Record transaction
            \App\Models\WalletTransaction::create([
                'user_id' => $userId,
                'type' => 'credit',
                'amount' => $netPayout,
                'balance_after' => $user->wallet_balance,
                'reference_id' => 'EARLY_COD_' . time(),
                'description' => 'Early COD Payout (' . $shipments->count() . ' parcels). Fee deducted: ' . $feePercent . '%'
            ]);

            // Mark COD as remitted and record fee
            foreach($shipments as $shipment) {
                $shipmentVal = (float)($shipment->cod_amount ?? $shipment->invoice_value ?? 0);
                $shipment->cod_remittance_status = 'processed';
                $shipment->cod_remittance_date = now();
                $shipment->early_cod_fee_deducted = ($shipmentVal * $feePercent) / 100;
                $shipment->cod_remitted = true;
                $shipment->save();
            }
        });

        return back()->with('success', 'Early COD successfully processed and credited to seller wallet!');
    }

    public function invoices(Request $request)
    {
        $query = Shipment::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('awb_number', 'LIKE', "%{$search}%")
                  ->orWhere('order_id', 'LIKE', "%{$search}%")
                  ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('company_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('payment_mode')) {
            if ($request->payment_mode === 'cod') {
                $query->where('is_cod', true);
            } elseif ($request->payment_mode === 'prepaid') {
                $query->where('is_cod', false);
            }
        }

        $invoices = $query->paginate(20)->withQueryString();
        return view('admin.billing.invoices', compact('invoices'));
    }

    public function recordPayment(Request $request, $id)
    {
        return $this->remit($request, $id);
    }
}
