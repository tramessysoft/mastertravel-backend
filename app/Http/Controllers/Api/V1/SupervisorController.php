<?php

namespace App\Http\Controllers\api\v1;

use App\Models\Supervisor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SupervisorController extends Controller
{
     public function index()
    {
        $Supervisors = Supervisor::all();
        return response()->json([
            'success' => true,
            'data' => $Supervisors
        ]);
    }

    // save all data
    public function store(Request $request)
    {

        $Supervisor = Supervisor::create($request->all() + ['user_id' => Auth::id()]);


        return response()->json([
            'success' => true,
            'message' => 'Supervisor created successfully',
            'data' => $Supervisor
        ], 201);
    }

    // single data read
    public function show($id)
    {
        $Supervisor = Supervisor::find($id);
        if (!$Supervisor) {
            return response()->json(['success' => false, 'message' => 'Supervisor not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Supervisor]);
    }


    // data update
    public function update(Request $request, $id)
    {
        $Supervisor = Supervisor::find($id);
        if (!$Supervisor) {
            return response()->json(['success' => false, 'message' => 'Supervisor not found'], 404);
        }

        $Supervisor->update($request->all());
        return response()->json(['success' => true, 'message' => 'Supervisor updated successfully', 'data' => $Supervisor]);
    }


    // delete record
    public function destroy($id)
    {
        $Supervisor = Supervisor::find($id);
        if (!$Supervisor) {
            return response()->json(['success' => false, 'message' => 'Supervisor not found'], 404);
        }

        $Supervisor->delete();
        return response()->json(['success' => true, 'message' => 'Supervisor deleted successfully']);
    }
}
