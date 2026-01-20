<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{


    public function index()
    {
        $Drivers = Driver::all();
        return response()->json([
            'success' => true,
            'data' => $Drivers
        ]);
    }

    // save all data
    public function store(Request $request)
    {

        $Driver = Driver::create($request->all() + ['user_id' => Auth::id()]);


        return response()->json([
            'success' => true,
            'message' => 'Driver created successfully',
            'data' => $Driver
        ], 201);
    }

    // single data read
    public function show($id)
    {
        $Driver = Driver::find($id);
        if (!$Driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Driver]);
    }


    // data update
    public function update(Request $request, $id)
    {
        $Driver = Driver::find($id);
        if (!$Driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
        }

        $Driver->update($request->all());
        return response()->json(['success' => true, 'message' => 'Driver updated successfully', 'data' => $Driver]);
    }


    // delete record
    public function destroy($id)
    {
        $Driver = Driver::find($id);
        if (!$Driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
        }

        $Driver->delete();
        return response()->json(['success' => true, 'message' => 'Driver deleted successfully']);
    }
}
