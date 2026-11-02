<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shipment;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Real Data
        $yesterday = today()->subDay();
        $yesterdayShipments = Shipment::whereDate('created_at', $yesterday)->count();
        $yesterdayDelivered = Shipment::whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->whereDate('updated_at', $yesterday)->count();
        $yesterdayRto = Shipment::where(function($q) { $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%'); })->whereDate('updated_at', $yesterday)->count();
        $yesterdayNdr = Shipment::whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])->whereDate('updated_at', $yesterday)->count();
        $yesterdayRevenue = WalletTransaction::where('type', 'debit')->where('status', '!=', 'failed')->whereDate('created_at', $yesterday)->sum('amount');
        $yesterdaySettlements = Shipment::where('is_cod', true)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->where('cod_remitted', true)->whereDate('updated_at', $yesterday)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));
        
        $totalShipments = Shipment::count();
        $inTransit = Shipment::whereIn('status', ['In Transit', 'in_transit', 'transit', 'Transit', 'In-Transit', 'Received', 'Out for Delivery', 'out_for_delivery'])->count();
        $outForDelivery = Shipment::whereIn('status', ['Out for Delivery', 'out_for_delivery', 'ofd', 'OFD'])->count();
        $deliveredToday = Shipment::whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->whereDate('updated_at', today())->count();
        $pendingPickups = Shipment::whereIn('status', ['Pending', 'pending', 'Pickup Scheduled', 'pickup_scheduled', 'Manifested', 'manifested', 'Booked', 'booked', 'new', 'New', 'new order'])->count();
        $ndrCount = Shipment::whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])->count();
        $rtoCount = Shipment::where(function($q) { $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%'); })->count();
        
        $totalRevenue = WalletTransaction::where('type', 'debit')->where('status', '!=', 'failed')->sum('amount');
        $totalSettlements = Shipment::where('is_cod', true)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->where('cod_remitted', true)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'));

        // Couriers performance
        $couriers = DB::table('shipments')
            ->select('courier_partner', DB::raw('count(*) as total'), DB::raw('sum(case when status = "Delivered" then 1 else 0 end) as delivered'), DB::raw('sum(case when status like "RTO%" then 1 else 0 end) as rto_count'))
            ->whereNotNull('courier_partner')
            ->groupBy('courier_partner')
            ->get();
            
        $dbCouriers = DB::table('couriers')->pluck('is_active', 'name');
            
        $courierPerformance = [];
        foreach ($couriers as $c) {
            $efficiency = $c->total > 0 ? round(($c->delivered / $c->total) * 100) : 0;
            $courierPerformance[] = [
                'name' => $c->courier_partner,
                'load' => $c->total,
                'efficiency' => $efficiency,
                'rto' => $c->rto_count,
                'is_active' => $dbCouriers[$c->courier_partner] ?? false
            ];
        }

        // Weak zones based on NDR (calculate percentage)
        $weakZones = DB::table('shipments')
            ->select('delivery_pincode as pincode', 'delivery_city as city', DB::raw('count(*) as total_orders'), DB::raw('sum(case when status = "NDR" then 1 else 0 end) as total_ndr'))
            ->groupBy('delivery_pincode', 'delivery_city')
            ->having('total_ndr', '>', 0)
            ->orderByDesc('total_ndr')
            ->limit(4)
            ->get()->map(function ($z) {
                $rate = $z->total_orders > 0 ? round(($z->total_ndr / $z->total_orders) * 100) : 0;
                return ['pincode' => $z->pincode, 'city' => $z->city, 'ndr_rate' => $rate, 'total_ndr' => $z->total_ndr];
            });

        // System Overview Data
        $activeSellers = User::where('role', 'seller')->count();
        $activeHubs = User::where('role', 'franchise')->count();
        $totalRiders = User::whereIn('role', ['rider', 'pickup_rider', 'delivery_rider'])->count();
        
        // System Health (Bottom Bar)
        $dbVersion = "MySQL 8.x Optimized";
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = "MySQL " . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {}
        
        $activeApiCount = \App\Models\Courier::where('is_active', true)->count();
        $latestRemittance = WalletTransaction::where('type', 'cod_remittance')->latest()->first();
        $remittanceStatus = $latestRemittance ? 'Last done: ' . $latestRemittance->created_at->diffForHumans() : 'Auto-settlements active';

        // Recent Bookings
        $recentShipmentsRaw = Shipment::with('user')->latest()->limit(5)->get();
        $recentShipments = $recentShipmentsRaw->map(function ($s) {
            return [
                'awb' => $s->awb_number,
                'seller' => $s->user->name ?? 'Unknown',
                'status' => $s->status
            ];
        });

        return view('admin.dashboard', compact(
            'totalShipments',
            'inTransit',
            'outForDelivery',
            'deliveredToday',
            'pendingPickups',
            'ndrCount',
            'rtoCount',
            'totalRevenue',
            'totalSettlements',
            'courierPerformance',
            'weakZones',
            'activeSellers',
            'activeHubs',
            'totalRiders',
            'dbVersion', 'activeApiCount', 'remittanceStatus',
            'recentShipments', 'yesterdayShipments', 'yesterdayDelivered', 'yesterdayRto', 'yesterdayNdr', 'yesterdayRevenue', 'yesterdaySettlements'
        ));
    }

    public function liveMap()
    {
        $activeRiders = User::whereIn('role', ['rider', 'pickup_rider', 'delivery_rider'])
                        ->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->get();
        return view('admin.map', compact('activeRiders'));
    }
}



