<?php

namespace App\Http\Controllers;

use App\Models\Franchise;
use App\Models\Hub;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HubReportController extends Controller
{
    private function getAuthScope()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $franchise = Franchise::where('user_id', $user->id)->first();
        $isHubManager = Hub::where('manager_id', $user->id)->exists();
        
        $isAuthorized = $user->isAdmin()
            || in_array($user->role, ['super_admin', 'admin', 'franchise'])
            || $franchise
            || $isHubManager;

        if (!$isAuthorized) {
            abort(403, 'Unauthorized');
        }

        return [
            'user' => $user,
            'franchise' => $franchise,
            'hub' => Hub::where('manager_id', $user->id)->first()
        ];
    }

    private function getScopedQuery()
    {
        $scope = $this->getAuthScope();
        $franchise = $scope['franchise'];
        $hub = $scope['hub'];
        $query = Shipment::query();

        if ($franchise) {
            if ($franchise->isState()) {
                $districtIds = $franchise->districtFranchises()->pluck('id')->toArray();
                $query->where(function($q) use ($franchise, $districtIds) {
                    $q->where('state_franchise_id', $franchise->id)
                      ->orWhere('franchise_id', $franchise->id)
                      ->orWhere('pickup_franchise_id', $franchise->id)
                      ->orWhere('delivery_state', $franchise->state)
                      ->orWhere('user_id', Auth::id());
                    if (!empty($districtIds)) {
                        $q->orWhereIn('franchise_id', $districtIds)
                          ->orWhereIn('pickup_franchise_id', $districtIds);
                    }
                });
            } else {
                $pincodes = is_array($franchise->serviceable_pincodes) ? $franchise->serviceable_pincodes : (json_decode($franchise->serviceable_pincodes, true) ?? []);
                if (!is_array($pincodes)) { $pincodes = $pincodes ? [$pincodes] : []; }

                $query->where(function($q) use ($franchise, $pincodes) {
                    $q->where('franchise_id', $franchise->id)
                      ->orWhere('pickup_franchise_id', $franchise->id)
                      ->orWhere('user_id', Auth::id());
                    if (!empty($pincodes)) {
                        $q->orWhereIn('pickup_pincode', $pincodes)
                          ->orWhereIn('delivery_pincode', $pincodes);
                    }
                });
            }
        } elseif ($hub) {
            $query->where(function($q) use ($hub) {
                $q->where('pickup_city', $hub->city)
                  ->orWhere('delivery_city', $hub->city);
            });
        }

        return $query;
    }

    private function resolveQuickDate(Request $request): void
    {
        if ($request->filled('quick_date') && (!$request->filled('date_from') || !$request->filled('date_to'))) {
            $today = now()->toDateString();
            if ($request->quick_date === 'today') {
                $request->merge(['date_from' => $today, 'date_to' => $today]);
            } elseif ($request->quick_date === 'yesterday') {
                $yesterday = now()->subDay()->toDateString();
                $request->merge(['date_from' => $yesterday, 'date_to' => $yesterday]);
            } elseif ($request->quick_date === '7days') {
                $request->merge(['date_from' => now()->subDays(6)->toDateString(), 'date_to' => $today]);
            } elseif ($request->quick_date === '30days') {
                $request->merge(['date_from' => now()->subDays(29)->toDateString(), 'date_to' => $today]);
            } elseif ($request->quick_date === 'this_month') {
                $request->merge(['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => $today]);
            }
        }
    }

    public function index(Request $request)
    {
        $this->resolveQuickDate($request);
        $query = $this->getScopedQuery();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Summary Aggregates
        $totalBookings = (clone $query)->count();
        $delivered     = (clone $query)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->count();
        $inTransit     = (clone $query)->whereIn('status', ['In Transit', 'in_transit', 'transit', 'Transit', 'In-Transit', 'Out for Delivery', 'out_for_delivery', 'Manifested', 'manifested', 'Received', 'Pickup Scheduled', 'pickup_scheduled'])->count();
        $rto           = (clone $query)->where(function($q) {
            $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%');
        })->count();
        $ndr           = (clone $query)->whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])->count();
        
        // Pickups vs Deliveries logic
        $scope = $this->getAuthScope();
        $franchise = $scope['franchise'];
        $hub = $scope['hub'];
        $districtIds = [];
        $pincodes = [];

        if ($franchise) {
            if ($franchise->isState()) {
                $districtIds = $franchise->districtFranchises()->pluck('id')->toArray();
                $myPickups = (clone $query)->where(function($q) use ($franchise, $districtIds) {
                    $q->where('pickup_franchise_id', $franchise->id);
                    if (!empty($districtIds)) {
                        $q->orWhereIn('pickup_franchise_id', $districtIds);
                    }
                })->count();

                $myDeliveries = (clone $query)->where(function($q) use ($franchise, $districtIds) {
                    $q->where('franchise_id', $franchise->id);
                    if (!empty($districtIds)) {
                        $q->orWhereIn('franchise_id', $districtIds);
                    }
                })->count();
            } else {
                $p = $franchise->serviceable_pincodes;
                $pincodes = is_array($p) ? $p : (json_decode($p, true) ?? []);
                if (!is_array($pincodes)) { $pincodes = $pincodes ? [$pincodes] : []; }

                $myPickups = (clone $query)->where(function($q) use ($franchise, $pincodes) {
                    $q->where('pickup_franchise_id', $franchise->id);
                    if (!empty($pincodes)) { $q->orWhereIn('pickup_pincode', $pincodes); }
                })->count();

                $myDeliveries = (clone $query)->where(function($q) use ($franchise, $pincodes) {
                    $q->where('franchise_id', $franchise->id);
                    if (!empty($pincodes)) { $q->orWhereIn('delivery_pincode', $pincodes); }
                })->count();
            }
        } else {
            $myPickups = (clone $query)->where('pickup_city', $hub?->city)->count();
            $myDeliveries = (clone $query)->where('delivery_city', $hub?->city)->count();
        }

        // Pending COD calculated using merchandise COD value
        $pendingCod = (clone $query)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])
            ->where(function($q) { $q->where('payment_type', 'COD')->orWhere('is_cod', 1); })
            ->where(function($q) { $q->where('cod_remitted', 0)->orWhereNull('cod_remitted'); })
            ->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)')) ?? 0;

        // Filterable Table Query
        $tableQuery = clone $query;

        if ($request->filled('status')) {
            $st = strtolower(trim($request->status));
            if ($st === 'rto' || str_starts_with($st, 'rto')) {
                $tableQuery->where(function($q) {
                    $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%');
                });
            } else {
                $tableQuery->where('status', $request->status);
            }
        }

        if ($request->filled('payment_mode')) {
            if ($request->payment_mode === 'COD') {
                $tableQuery->where(function($q) { $q->where('is_cod', 1)->orWhere('payment_type', 'COD'); });
            } elseif ($request->payment_mode === 'Prepaid') {
                $tableQuery->where(function($q) { $q->where('is_cod', 0)->where('payment_type', '!=', 'COD'); });
            }
        }

        if ($request->filled('flow')) {
            if ($request->flow === 'pickup') {
                if ($franchise) {
                    if ($franchise->isState()) {
                        $tableQuery->where(function($q) use ($franchise, $districtIds) {
                            $q->where('pickup_franchise_id', $franchise->id);
                            if (!empty($districtIds)) { $q->orWhereIn('pickup_franchise_id', $districtIds); }
                        });
                    } else {
                        $tableQuery->where(function($q) use ($franchise, $pincodes) {
                            $q->where('pickup_franchise_id', $franchise->id);
                            if (!empty($pincodes)) { $q->orWhereIn('pickup_pincode', $pincodes); }
                        });
                    }
                } elseif ($hub) {
                    $tableQuery->where('pickup_city', $hub->city);
                }
            } elseif ($request->flow === 'delivery') {
                if ($franchise) {
                    if ($franchise->isState()) {
                        $tableQuery->where(function($q) use ($franchise, $districtIds) {
                            $q->where('franchise_id', $franchise->id);
                            if (!empty($districtIds)) { $q->orWhereIn('franchise_id', $districtIds); }
                        });
                    } else {
                        $tableQuery->where(function($q) use ($franchise, $pincodes) {
                            $q->where('franchise_id', $franchise->id);
                            if (!empty($pincodes)) { $q->orWhereIn('delivery_pincode', $pincodes); }
                        });
                    }
                } elseif ($hub) {
                    $tableQuery->where('delivery_city', $hub->city);
                }
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $tableQuery->where(function($q) use ($search) {
                $q->where('awb_number', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_city', 'like', "%{$search}%")
                  ->orWhere('delivery_pincode', 'like', "%{$search}%");
            });
        }

        $shipments = $tableQuery->with(['user', 'assignedRider'])->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('hub.reports.index', compact(
            'totalBookings',
            'delivered',
            'inTransit',
            'rto',
            'ndr',
            'myPickups',
            'myDeliveries',
            'pendingCod',
            'shipments',
            'franchise',
            'hub',
            'pincodes'
        ));
    }

    public function exportCsv(Request $request)
    {
        $this->resolveQuickDate($request);
        $query = $this->getScopedQuery();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            if ($request->status === 'RTO') {
                $query->where(function($q) {
                    $q->where('status', 'RTO')->orWhere('status', 'LIKE', 'RTO%');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('payment_mode')) {
            if ($request->payment_mode === 'COD') {
                $query->where(function($q) { $q->where('is_cod', 1)->orWhere('payment_type', 'COD'); });
            } elseif ($request->payment_mode === 'Prepaid') {
                $query->where(function($q) { $q->where('is_cod', 0)->where('payment_type', '!=', 'COD'); });
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('awb_number', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_city', 'like', "%{$search}%")
                  ->orWhere('delivery_pincode', 'like', "%{$search}%");
            });
        }

        $fileName = 'hub_shipments_report_' . date('Y-m-d_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            fputcsv($file, [
                'ID',
                'AWB Number',
                'Order ID',
                'Sender / Store Name',
                'Receiver Name',
                'Receiver Phone',
                'Delivery Address',
                'Destination City',
                'Pincode',
                'Weight (KG)',
                'Payment Mode',
                'Invoice Value (INR)',
                'COD Amount (INR)',
                'Freight Charge (INR)',
                'Current Status',
                'Booking Date'
            ]);

            $query->with('user')->orderBy('created_at', 'desc')->chunk(300, function($shipments) use ($file) {
                foreach ($shipments as $s) {
                    $isCod = ($s->payment_type === 'COD' || $s->is_cod);
                    $codAmount = $isCod ? ($s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value) : 0.00;
                    $freight = $s->shipping_charge ?? $s->total_amount ?? 0.00;

                    fputcsv($file, [
                        $s->id,
                        $s->awb_number,
                        $s->order_id ?? 'N/A',
                        optional($s->user)->company_name ?: optional($s->user)->name ?: 'Client Store',
                        $s->receiver_name,
                        $s->receiver_phone,
                        $s->delivery_address,
                        $s->delivery_city,
                        $s->delivery_pincode,
                        $s->weight_kg,
                        $isCod ? 'COD' : 'Prepaid',
                        number_format((float) ($s->invoice_value ?? 0), 2, '.', ''),
                        number_format((float) $codAmount, 2, '.', ''),
                        number_format((float) $freight, 2, '.', ''),
                        $s->status,
                        $s->created_at ? $s->created_at->format('Y-m-d H:i') : ''
                    ]);
                }
            });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
