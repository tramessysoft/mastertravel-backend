<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    public function index()
    {
        $Vehicles = Vehicle::all();
        return response()->json([
            'success' => true,
            'data' => $Vehicles
        ]);
    }

    // save all data
    public function store(Request $request)
    {

        $Vehicle = Vehicle::create($request->all() + ['user_id' => Auth::id()]);


        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => $Vehicle
        ], 201);
    }

    // single data read
    public function show($id)
    {
        $Vehicle = Vehicle::find($id);
        if (!$Vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Vehicle]);
    }


    // data update
    public function update(Request $request, $id)
    {
        $Vehicle = Vehicle::find($id);
        if (!$Vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $Vehicle->update($request->all());
        return response()->json(['success' => true, 'message' => 'Vehicle updated successfully', 'data' => $Vehicle]);
    }


    // delete record
    public function destroy($id)
    {
        $Vehicle = Vehicle::find($id);
        if (!$Vehicle) {
            return response()->json(['success' => false, 'message' => 'Vehicle not found'], 404);
        }

        $Vehicle->delete();
        return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully']);
    }
}
