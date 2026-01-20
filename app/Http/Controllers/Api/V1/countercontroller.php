<?php

namespace App\Http\Controllers\api\v1;

use App\Models\Counter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class countercontroller extends Controller
{
    public function index()
    {
        $Counters = Counter::all();
        return response()->json([
            'success' => true,
            'data' => $Counters
        ]);
    }

    // save all data
    public function store(Request $request)
    {

        $Counter = Counter::create($request->all() + ['user_id' => Auth::id()]);


        return response()->json([
            'success' => true,
            'message' => 'Counter created successfully',
            'data' => $Counter
        ], 201);
    }

    // single data read
    public function show($id)
    {
        $Counter = Counter::find($id);
        if (!$Counter) {
            return response()->json(['success' => false, 'message' => 'Counter not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Counter]);
    }


    // data update
    public function update(Request $request, $id)
    {
        $Counter = Counter::find($id);
        if (!$Counter) {
            return response()->json(['success' => false, 'message' => 'Counter not found'], 404);
        }

        $Counter->update($request->all());
        return response()->json(['success' => true, 'message' => 'Counter updated successfully', 'data' => $Counter]);
    }


    // delete record
    public function destroy($id)
    {
        $Counter = Counter::find($id);
        if (!$Counter) {
            return response()->json(['success' => false, 'message' => 'Counter not found'], 404);
        }

        $Counter->delete();
        return response()->json(['success' => true, 'message' => 'Counter deleted successfully']);
    }
}
