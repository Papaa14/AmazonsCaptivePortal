<?php

namespace App\Http\Controllers\Api\Captive;

use App\Models\HotspotPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class HotspotPackageController extends Controller
{
    /**
     * Display a listing of all packages.
     */
    public function index()
    {
        // Returns all packages, you can also paginate here
        return HotspotPackage::all();
    }

    /**
     * Store a newly created package in the database.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:hotspot_packages',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:0',
            'device_limit' => 'required|integer|min:1',
            'is_unlimited' => 'required|boolean',
            'data_limit_mb' => 'nullable|integer|min:0',
            'bonus_data_mb' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_free' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $package = HotspotPackage::create($validator->validated());

        return response()->json($package, 201); // 201 Created
    }

    /**
     * Display a specific package.
     */
    public function show(HotspotPackage $hotspotPackage)
    {
        // Route-model binding automatically finds the package or returns a 404
        return $hotspotPackage;
    }

    /**
     * Update the specified package in the database.
     */
    public function update(Request $request, HotspotPackage $hotspotPackage)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:hotspot_packages,name,' . $hotspotPackage->id,
            'price' => 'sometimes|required|numeric|min:0',
            'duration_minutes' => 'sometimes|required|integer|min:0',
            'device_limit' => 'sometimes|required|integer|min:1',
            'is_unlimited' => 'sometimes|required|boolean',
            'data_limit_mb' => 'nullable|integer|min:0',
            'bonus_data_mb' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_free' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotspotPackage->update($validator->validated());

        return response()->json($hotspotPackage);
    }

    /**
     * Remove the specified package from the database.
     */
    public function destroy(HotspotPackage $hotspotPackage)
    {
        $hotspotPackage->delete();

        // Returns a 204 No Content response on successful deletion
        return response()->noContent();
    }
}