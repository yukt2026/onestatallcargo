<?php

namespace App\Http\Controllers;

use App\Models\ApiDoc;
use Illuminate\Http\Request;

class AdminApiDocController extends Controller
{
    public function index()
    {
        $docs = ApiDoc::orderBy('sort_order')->get();
        return view('admin.api_docs.index', compact('docs'));
    }

    public function create()
    {
        return view('admin.api_docs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint_key' => 'required|string|unique:api_docs,endpoint_key',
            'title' => 'required|string|max:255',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'path' => 'required|string',
            'description' => 'required|string',
            'curl_example' => 'nullable|string',
            'json_response' => 'nullable|string',
            'sort_order' => 'integer|nullable',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ApiDoc::create($validated);

        return redirect()->route('admin.api_docs.index')->with('success', 'API Documentation created successfully.');
    }

    public function edit(ApiDoc $apiDoc)
    {
        return view('admin.api_docs.edit', compact('apiDoc'));
    }

    public function update(Request $request, ApiDoc $apiDoc)
    {
        $validated = $request->validate([
            'endpoint_key' => 'required|string|unique:api_docs,endpoint_key,' . $apiDoc->id,
            'title' => 'required|string|max:255',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'path' => 'required|string',
            'description' => 'required|string',
            'curl_example' => 'nullable|string',
            'json_response' => 'nullable|string',
            'sort_order' => 'integer|nullable',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $apiDoc->update($validated);

        return redirect()->route('admin.api_docs.index')->with('success', 'API Documentation updated successfully.');
    }

    public function destroy(ApiDoc $apiDoc)
    {
        $apiDoc->delete();
        return redirect()->route('admin.api_docs.index')->with('success', 'API Documentation deleted successfully.');
    }

    public function show(ApiDoc $apiDoc)
    {
        return redirect()->route('admin.api_docs.edit', $apiDoc->id);
    }
}
