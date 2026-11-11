<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminRoleController extends Controller {

    
    public function index(Request $request) {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        return view('admin.roles.index', compact('users'));
    }

    public function create() {
        return view('admin.roles.create');
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,pending,rejected',
            'role' => 'required|string|in:admin,operations,seller,aggregator,b2b_customer,b2c_customer,customer,user,franchise,pickup_rider,delivery_rider,courier_partner,corporate',
            'company_name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'company_name' => $validated['company_name'],
            'wallet_balance' => 0,
            'permissions' => $validated['permissions'] ?? []
        ]);

        // Auto-create Rider profile if applicable
        if (in_array($validated['role'], ['rider', 'pickup_rider', 'delivery_rider'])) {
            \App\Models\Rider::create([
                'user_id' => $user->id,
                'status' => 'active',
                'is_active' => true
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'User created and role assigned successfully.');
    }

    public function edit($id) {
        $user = User::findOrFail($id);
        return view('admin.roles.edit', compact('user'));
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'phone' => 'nullable|string|max:20|unique:users,phone,'.$id,
            'password' => 'nullable|string|min:6',
            'status' => 'required|in:active,pending,rejected',
            'role' => 'required|string|in:admin,operations,seller,aggregator,b2b_customer,b2c_customer,customer,user,franchise,pickup_rider,delivery_rider,courier_partner,corporate',
            'company_name' => 'nullable|string|max:255',
            'wallet_balance' => 'required|numeric|min:0',
            'permissions' => 'nullable|array'
        ]);

        $user = User::findOrFail($id);
        
        // Prevent Super Admin from locking themselves out
        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own Super Admin privileges!');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->role = $validated['role'];
        $user->company_name = $validated['company_name'];
        $oldBalance = (float)$user->wallet_balance;
        $newBalance = (float)$validated['wallet_balance'];
        if ($oldBalance !== $newBalance) {
            $diff = $newBalance - $oldBalance;
            \App\Models\WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => abs($diff),
                'type' => $diff > 0 ? 'credit' : 'debit',
                'balance_after' => $newBalance,
                'reference_id' => 'ADMIN_OVERRIDE_' . time(),
                'description' => 'Wallet balance override by Superadmin in Role Management',
                'status' => 'success'
            ]);
        }
        $user->wallet_balance = $newBalance;
        $user->permissions = $validated['permissions'] ?? [];
        $user->save();

        if (in_array($validated['role'], ['rider', 'pickup_rider', 'delivery_rider'])) {
            \App\Models\Rider::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'active', 'is_active' => true]
            );
        }

        return redirect()->route('admin.roles.index')->with('success', 'User details & permissions updated successfully.');
    }
}




