<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceablePincode;
use App\Models\Franchise;

class AdminServiceabilityController extends Controller
{
    public function index(Request $request)
    {
        $totalActive = ServiceablePincode::where('is_active', true)->count();
        $onestallCovered = ServiceablePincode::where('is_active', true)->whereNotNull('franchise_id')->count();
        $externalOnly = ServiceablePincode::where('is_active', true)->whereNull('franchise_id')->count();
        
        $query = ServiceablePincode::with('franchise');
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('pincode', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%");
            });
        }
        
        $pincodes = $query->paginate(20)->withQueryString();
        $franchises = Franchise::where('status', 'approved')->get();
        
        return view('admin.serviceability.index', compact('pincodes', 'totalActive', 'onestallCovered', 'externalOnly', 'franchises'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pincode' => 'required|string|unique:serviceable_pincodes,pincode',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'franchise_id' => 'nullable|integer'
        ]);

        ServiceablePincode::create($request->all());

        return back()->with('success', 'Pincode added successfully');
    }

    public function update(Request $request, $id)
    {
        $pincode = ServiceablePincode::findOrFail($id);
        
        $request->validate([
            'pincode' => 'required|string|unique:serviceable_pincodes,pincode,'.$id,
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'franchise_id' => 'nullable|integer',
            'is_active' => 'required|boolean'
        ]);

        $pincode->update($request->all());

        return back()->with('success', 'Pincode updated successfully');
    }

    public function destroy($id)
    {
        ServiceablePincode::destroy($id);
        return back()->with('success', 'Pincode deleted successfully');
    }

    public function create()
    {
        return redirect()->route('admin.serviceability.index');
    }

    public function show($id)
    {
        return redirect()->route('admin.serviceability.index');
    }

    public function edit($id)
    {
        return redirect()->route('admin.serviceability.index');
    }
}
