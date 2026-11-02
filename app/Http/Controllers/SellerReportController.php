<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerReportController extends Controller
{
    /**
     * Display the monthly summary report and shipment list.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $month = $this->resolveMonth($request->query('month'));

        $startOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfDay();
        $endOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->endOfMonth()->endOfDay();

        $baseQuery = Shipment::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        // Aggregate statistics for the selected month
        $stats = [
            'total_shipments'    => (clone $baseQuery)->count(),
            'delivered_count'    => (clone $baseQuery)->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])->count(),
            'in_transit_count'   => (clone $baseQuery)->whereIn('status', ['In Transit', 'in_transit', 'transit', 'Transit', 'In-Transit', 'Out for Delivery', 'out_for_delivery', 'Manifested', 'manifested', 'Received', 'Pickup Scheduled', 'pickup_scheduled'])->count(),
            'ndr_count'          => (clone $baseQuery)->whereIn('status', ['NDR', 'ndr', 'Action Required', 'Undelivered'])->count(),
            'rto_count'          => (clone $baseQuery)->where(function($q) { $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%'); })->count(),
            'total_weight_kg'    => (float) ((clone $baseQuery)->sum('weight_kg')),
            'total_freight'      => (float) ((clone $baseQuery)->sum('shipping_charge')),
            'cod_orders_count'   => (clone $baseQuery)->where('is_cod', 1)->count(),
            'prepaid_count'      => (clone $baseQuery)->where('is_cod', 0)->count(),
            'cod_value'          => (float) ((clone $baseQuery)->where('is_cod', 1)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'))),
            'cod_remitted'       => (float) ((clone $baseQuery)->where('is_cod', 1)->where('cod_remitted', 1)->sum(DB::raw('COALESCE(cod_amount, invoice_value, 0)'))),
        ];

        $stats['cod_pending'] = max(0, $stats['cod_value'] - $stats['cod_remitted']);
        $stats['taxable_freight'] = round($stats['total_freight'] / 1.18, 2);
        $stats['gst_amount'] = round($stats['total_freight'] - $stats['taxable_freight'], 2);

        // Filterable table query
        $tableQuery = clone $baseQuery;

        if ($request->filled('status')) {
            $st = strtolower(trim($request->query('status')));
            if ($st === 'rto' || str_starts_with($st, 'rto')) {
                $tableQuery->where(function($q) { $q->where('status', 'LIKE', 'RTO%')->orWhere('status', 'LIKE', 'rto%'); });
            } else {
                $tableQuery->where('status', $request->query('status'));
            }
        }

        if ($request->filled('payment_mode')) {
            if ($request->query('payment_mode') === 'COD') {
                $tableQuery->where('is_cod', 1);
            } elseif ($request->query('payment_mode') === 'Prepaid') {
                $tableQuery->where('is_cod', 0);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $tableQuery->where(function ($q) use ($search) {
                $q->where('awb_number', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  ->orWhere('delivery_city', 'like', "%{$search}%");
            });
        }

        $shipments = $tableQuery->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        // Available months list (past 12 months)
        $availableMonths = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $val = $cursor->format('Y-m');
            $availableMonths[] = [
                'value' => $val,
                'label' => $cursor->format('F Y'),
                'is_current' => $val === $month,
            ];
            $cursor->subMonth();
        }

        return view('seller.reports.index', compact(
            'shipments',
            'stats',
            'month',
            'startOfMonth',
            'endOfMonth',
            'availableMonths'
        ));
    }

    /**
     * Stream CSV export of all shipments for the selected month.
     */
    public function exportMonthlyCsv(Request $request)
    {
        $user = Auth::user();
        $month = $this->resolveMonth($request->query('month'));

        $startOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfDay();
        $endOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->endOfMonth()->endOfDay();

        $filename = "Onestatall_Monthly_Report_{$month}_{$user->id}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($user, $startOfMonth, $endOfMonth) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header Row
            fputcsv($file, [
                'AWB Number',
                'Order ID',
                'Booking Date',
                'Courier Partner',
                'Receiver Name',
                'Receiver Phone',
                'Destination City',
                'Destination State',
                'Pincode',
                'Weight (KG)',
                'Payment Type',
                'Invoice Value (INR)',
                'COD Collectible (INR)',
                'Total Freight Charge (INR)',
                'Taxable Freight (INR)',
                'GST 18% (INR)',
                'Current Status',
                'COD Remittance Status',
            ]);

            Shipment::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->orderBy('created_at', 'asc')
                ->chunk(200, function ($shipments) use ($file) {
                    foreach ($shipments as $s) {
                        $freight = (float) $s->shipping_charge;
                        $taxable = round($freight / 1.18, 2);
                        $gst = round($freight - $taxable, 2);
                        $codAmount = $s->is_cod ? ($s->cod_amount > 0 ? $s->cod_amount : $s->invoice_value) : 0;

                        fputcsv($file, [
                            $s->awb_number,
                            $s->order_id ?? 'N/A',
                            $s->created_at ? $s->created_at->format('d-m-Y H:i') : '',
                            $s->courier_partner ?? 'OneStall Direct',
                            $s->receiver_name,
                            $s->receiver_phone,
                            $s->delivery_city,
                            $s->delivery_state ?? '',
                            $s->delivery_pincode,
                            $s->weight_kg,
                            $s->is_cod ? 'COD' : 'Prepaid',
                            number_format((float) ($s->invoice_value ?? 0), 2, '.', ''),
                            number_format((float) $codAmount, 2, '.', ''),
                            number_format($freight, 2, '.', ''),
                            number_format($taxable, 2, '.', ''),
                            number_format($gst, 2, '.', ''),
                            $s->status,
                            $s->is_cod ? ($s->cod_remitted ? 'Remitted' : ($s->status === 'Delivered' ? 'Pending Remittance' : 'Uncollected')) : 'N/A',
                        ]);
                    }
                });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Render the official monthly GST Tax Invoice for the seller.
     */
    public function gstInvoice(Request $request)
    {
        $user = Auth::user();
        $month = $this->resolveMonth($request->query('month'));

        $startOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfDay();
        $endOfMonth = Carbon::createFromFormat('Y-m-d', "{$month}-01")->endOfMonth()->endOfDay();

        $shipments = Shipment::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalConsignments = $shipments->count();
        $totalWeight = (float) $shipments->sum('weight_kg');
        $totalFreightBilled = (float) $shipments->sum('shipping_charge');

        // Tax calculation: freight charge is inclusive of 18% GST
        $taxableValue = round($totalFreightBilled / 1.18, 2);
        $totalGst = round($totalFreightBilled - $taxableValue, 2);

        // Platform / Company Details
        $platform = [
            'name'       => 'OneStatall Cargo Pvt Ltd',
            'legal_name' => 'OneStatall Cargo Logistics Private Limited',
            'gstin'      => '07AAAAO0000A1Z5',
            'pan'        => 'AAAAO0000A',
            'sac_code'   => '996511',
            'sac_desc'   => 'Road Transport & Logistics Cargo Freight Services',
            'address'    => 'Plot No. 42, Logistics Park, Cargo Terminal, Mahipalpur',
            'city'       => 'New Delhi',
            'state'      => 'Delhi',
            'state_code' => '07',
            'pincode'    => '110037',
            'email'      => 'billing@onestatallcargo.com',
            'phone'      => '+91 11 4000 9000',
        ];

        // Seller Details
        $sellerState = trim($user->company_state ?? 'Delhi');
        $sellerGstin = trim($user->gstin ?? '');
        $sellerStateCode = $this->resolveStateCode($sellerGstin, $sellerState);

        $seller = [
            'name'         => $user->company_name ?: $user->name,
            'brand'        => $user->brand_name ?: $user->company_name ?: $user->name,
            'gstin'        => $sellerGstin ?: 'URP (Unregistered Person)',
            'pan'          => $user->pan_number ?: 'N/A',
            'address'      => $user->company_address ?: ($user->company_city ? "{$user->company_city}, {$sellerState}" : 'Registered Business Address'),
            'city'         => $user->company_city ?: '',
            'state'        => $sellerState,
            'state_code'   => $sellerStateCode,
            'pincode'      => $user->company_pincode ?: '',
            'phone'        => $user->phone ?: 'N/A',
            'email'        => $user->email,
        ];

        // Intra-state vs Inter-state determination
        // Platform state code is 07 (Delhi)
        $isIntraState = ($sellerStateCode === '07' || strcasecmp($sellerState, 'Delhi') === 0);

        if ($isIntraState) {
            $cgstRate = 9.0;
            $sgstRate = 9.0;
            $igstRate = 0.0;
            $cgstAmount = round($totalGst / 2, 2);
            $sgstAmount = round($totalGst - $cgstAmount, 2);
            $igstAmount = 0.00;
        } else {
            $cgstRate = 0.0;
            $sgstRate = 0.0;
            $igstRate = 18.0;
            $cgstAmount = 0.00;
            $sgstAmount = 0.00;
            $igstAmount = $totalGst;
        }

        $invoiceNumber = 'OST/TAX/' . $startOfMonth->format('ym') . '/' . str_pad($user->id, 5, '0', STR_PAD_LEFT);
        $invoiceDate = $endOfMonth->format('d/m/Y');
        $billingPeriod = $startOfMonth->format('d M Y') . ' to ' . $endOfMonth->format('d M Y');
        $amountInWords = $this->numberToIndianWords(round($totalFreightBilled));

        return view('seller.reports.gst_invoice', compact(
            'platform',
            'seller',
            'month',
            'invoiceNumber',
            'invoiceDate',
            'billingPeriod',
            'totalConsignments',
            'totalWeight',
            'totalFreightBilled',
            'taxableValue',
            'totalGst',
            'isIntraState',
            'cgstRate',
            'sgstRate',
            'igstRate',
            'cgstAmount',
            'sgstAmount',
            'igstAmount',
            'amountInWords'
        ));
    }

    /**
     * Resolve and sanitize month string in YYYY-MM format.
     */
    private function resolveMonth(?string $month): string
    {
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }
        return now()->format('Y-m');
    }

    /**
     * Resolve 2-digit Indian GST State Code from GSTIN or state name.
     */
    private function resolveStateCode(?string $gstin, ?string $state): string
    {
        if ($gstin && strlen($gstin) >= 2 && ctype_digit(substr($gstin, 0, 2))) {
            return substr($gstin, 0, 2);
        }

        $stateMap = [
            'jammu and kashmir' => '01',
            'himachal pradesh'  => '02',
            'punjab'            => '03',
            'chandigarh'        => '04',
            'uttarakhand'       => '05',
            'haryana'           => '06',
            'delhi'             => '07',
            'rajasthan'         => '08',
            'uttar pradesh'     => '09',
            'bihar'             => '10',
            'sikkim'            => '11',
            'arunachal pradesh' => '12',
            'nagaland'          => '13',
            'manipur'           => '14',
            'mizoram'           => '15',
            'tripura'           => '16',
            'meghalaya'         => '17',
            'assam'             => '18',
            'west bengal'       => '19',
            'jharkhand'         => '20',
            'odisha'            => '21',
            'chhattisgarh'      => '22',
            'madhya pradesh'    => '23',
            'gujarat'           => '24',
            'maharashtra'       => '27',
            'andhra pradesh'    => '28',
            'karnataka'         => '29',
            'goa'               => '30',
            'kerala'            => '32',
            'tamil nadu'        => '33',
            'puducherry'        => '34',
            'telangana'         => '36',
        ];

        $key = strtolower(trim((string) $state));
        return $stateMap[$key] ?? '07';
    }

    /**
     * Convert integer amount to Indian Currency words (e.g., Rupees Five Thousand Four Hundred).
     */
    private function numberToIndianWords(float|int $number): string
    {
        $number = (int) round($number);
        if ($number <= 0) {
            return 'Rupees Zero Only';
        }

        $units = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $words = [];

        if ($crores = (int) ($number / 10000000)) {
            $words[] = $this->convertThreeDigits($crores, $units, $tens) . ' Crore';
            $number %= 10000000;
        }

        if ($lakhs = (int) ($number / 100000)) {
            $words[] = $this->convertThreeDigits($lakhs, $units, $tens) . ' Lakh';
            $number %= 100000;
        }

        if ($thousands = (int) ($number / 1000)) {
            $words[] = $this->convertThreeDigits($thousands, $units, $tens) . ' Thousand';
            $number %= 1000;
        }

        if ($hundreds = (int) ($number / 100)) {
            $words[] = $units[$hundreds] . ' Hundred';
            $number %= 100;
        }

        if ($number > 0) {
            if ($number < 20) {
                $words[] = $units[$number];
            } else {
                $words[] = $tens[(int) ($number / 10)] . (($number % 10 > 0) ? ' ' . $units[$number % 10] : '');
            }
        }

        return 'Rupees ' . implode(' ', array_filter($words)) . ' Only';
    }

    private function convertThreeDigits(int $num, array $units, array $tens): string
    {
        $res = [];
        if ($num >= 100) {
            $res[] = $units[(int) ($num / 100)] . ' Hundred';
            $num %= 100;
        }
        if ($num > 0) {
            if ($num < 20) {
                $res[] = $units[$num];
            } else {
                $res[] = $tens[(int) ($num / 10)] . (($num % 10 > 0) ? ' ' . $units[$num % 10] : '');
            }
        }
        return implode(' ', array_filter($res));
    }
}
