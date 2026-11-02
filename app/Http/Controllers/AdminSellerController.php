<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminSellerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['seller', 'b2b_customer', 'corporate']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        $sellers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        return view('admin.sellers.index', compact('sellers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company_name' => $validated['company_name'],
            'password' => Hash::make($validated['password']),
            'role' => 'seller',
            'wallet_balance' => 0
        ]);

        return back()->with('success', 'New seller account created successfully.');
    }

    public function update(Request $request, $id)
    {
        $seller = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:15',
            'pan_number' => 'nullable|string|max:10',
            'business_type' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:1000',
            'company_city' => 'nullable|string|max:255',
            'company_state' => 'nullable|string|max:255',
            'company_pincode' => 'nullable|string|max:6',
            'early_cod_plan' => 'nullable|in:standard,early_t1,early_t2,early_t3,early_t4',
            'early_cod_fee' => 'nullable|numeric|min:0|max:100',
            'wallet_adjustment' => 'nullable|numeric'
        ]);

        $seller->fill($request->only([
            'name', 'phone', 'company_name', 'brand_name', 'gstin', 'pan_number',
            'business_type', 'company_address', 'company_city', 'company_state', 'company_pincode', 'early_cod_plan', 'early_cod_fee'
        ]));
        
        if (!empty($validated['wallet_adjustment']) && $validated['wallet_adjustment'] != 0) {
            $seller->wallet_balance += $validated['wallet_adjustment'];
        }

        $seller->save();

        return back()->with('success', 'Seller details and company profile updated successfully.');
    }

    public function toggleStatus($id)
    {
        $seller = User::findOrFail($id);
        // Toggle suspended status
        if ($seller->status === 'suspended') {
            $seller->status = 'active';
            $msg = 'Seller account has been re-activated.';
        } else {
            $seller->status = 'suspended';
            $msg = 'Seller account has been suspended.';
        }
        $seller->save();
        return back()->with('success', $msg);
    }

    public function toggle($id)
    {
        return $this->toggleStatus($id);
    }

    public function destroy($id)
    {
        $seller = User::findOrFail($id);
        // Safety: do not delete admins via this endpoint
        if ($seller->role === 'admin') {
            return back()->with('error', 'Cannot delete an admin account from here.');
        }
        $seller->delete();
        return back()->with('success', 'Seller account has been permanently deleted.');
    }
}
