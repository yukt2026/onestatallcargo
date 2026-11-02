<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['customer', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

                $customers = $query->withCount([
            'shipments as total_orders',
            'shipments as delivered_orders' => function($q) {
                $q->where('status', 'Delivered');
            }
        ])->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        return view('admin.customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        return back()->with('success', 'New customer account created successfully.');
    }

    public function update(Request $request, $id)
    {
        $customer = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id,
        ]);

        $customer->fill($request->only(['name', 'phone']));
        $customer->save();

        return back()->with('success', 'Customer details updated successfully.');
    }

    public function destroy($id)
    {
        $customer = User::findOrFail($id);
        if ($customer->role === 'admin') {
            return back()->with('error', 'Cannot delete an admin account from here.');
        }
        $customer->delete();
        return back()->with('success', 'Customer account has been permanently deleted.');
    }

    public function toggleStatus($id)
    {
        $customer = User::findOrFail($id);
        if ($customer->status === 'suspended') {
            $customer->status = 'active';
            $msg = 'Customer account has been re-activated.';
        } else {
            $customer->status = 'suspended';
            $msg = 'Customer account has been suspended.';
        }
        $customer->save();
        return back()->with('success', $msg);
    }

    public function toggle($id)
    {
        return $this->toggleStatus($id);
    }
}