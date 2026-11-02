<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index()
    {
        // Gather some basic stats for the reports dashboard
        $dailyBookings = Shipment::whereDate('created_at', today())->count();
        
        $topSeller = DB::table('shipments')
            ->join('users', 'shipments.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->first();
            
        $pendingCod = Shipment::where('is_cod', true)
            ->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])
            ->where(function($q) {
                $q->whereNull('cod_remitted')->orWhere('cod_remitted', false);
            })
            ->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));

        return view('admin.reports.index', compact('dailyBookings', 'topSeller', 'pendingCod'));
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'master_shipments_report_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'ID', 'AWB Number', 'Seller Name', 'Customer Name', 
                'Customer Phone', 'City', 'State', 'Pincode', 
                'Shipment Type', 'COD Value', 'Status', 'Courier', 'Date'
            ]);

            // Chunking to prevent memory exhaustion on large datasets
            Shipment::with('user')->orderBy('created_at', 'desc')->chunk(500, function($shipments) use ($file) {
                foreach ($shipments as $s) {
                    fputcsv($file, [
                        $s->id,
                        $s->awb_number,
                        $s->user->name ?? 'Unknown',
                        $s->receiver_name,
                        $s->receiver_phone,
                        $s->delivery_city,
                        $s->delivery_state ?? 'N/A',
                        $s->delivery_pincode,
                        $s->is_cod ? 'COD' : 'Prepaid',
                        $s->invoice_value,
                        $s->status,
                        $s->courier_partner ?? 'Unassigned',
                        $s->created_at->format('Y-m-d H:i')
                    ]);
                }
            });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
