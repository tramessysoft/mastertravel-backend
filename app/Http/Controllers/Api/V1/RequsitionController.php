<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\AdvancedRequestion;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RequsitionController extends Controller
{


    // get all Data by userid
    public function index()
    {
        $AdvancedRequestions = AdvancedRequestion::all();
        return response()->json([
            'success' => true,
            'data' => $AdvancedRequestions
        ]);
    }

    // save all data
    public function store(Request $request)
    {


        $AdvancedRequestion = AdvancedRequestion::create($request->all() + ['user_id' => Auth::id()]);

        return response()->json([
            'success' => true,
            'message' => 'AdvancedRequestion created successfully',
            'data' => $AdvancedRequestion
        ], 201);
    }

    // single data read
    public function show($id)
    {
        $AdvancedRequestion = AdvancedRequestion::find($id);
        if (!$AdvancedRequestion) {
            return response()->json(['success' => false, 'message' => 'AdvancedRequestion not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $AdvancedRequestion]);
    }


    // data update
    public function update(Request $request, $id)
    {
        $AdvancedRequestion = AdvancedRequestion::find($id);
        if (!$AdvancedRequestion) {
            return response()->json(['success' => false, 'message' => 'AdvancedRequestion not found'], 404);
        }

        $AdvancedRequestion->update($request->all());
        return response()->json(['success' => true, 'message' => 'AdvancedRequestion updated successfully', 'data' => $AdvancedRequestion]);
    }


    // delete record
    public function destroy($id)
    {
        $AdvancedRequestion = AdvancedRequestion::find($id);
        if (!$AdvancedRequestion) {
            return response()->json(['success' => false, 'message' => 'AdvancedRequestion not found'], 404);
        }

        $AdvancedRequestion->delete();
        return response()->json(['success' => true, 'message' => 'AdvancedRequestion deleted successfully']);
    }
}
