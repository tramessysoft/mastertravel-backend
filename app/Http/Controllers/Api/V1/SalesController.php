<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Sales;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{




    public function index()
    {
        $sales = Sales::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ], 200);
    }



    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required',
            'product_no' => 'required',
            'product_name' => 'required',
            'quantity' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();

        try {

            // 1️⃣ Check stock availability
            $stock = Stock::where('product_no', $request->product_no)->lockForUpdate()->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock not found for this product'
                ], 404);
            }

            if ($stock->total_qty < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }

            // 2️⃣ Insert Sale
            $sale = Sales::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'product_no' => $request->product_no,
                'product_name' => $request->product_name,
                'quantity' => $request->quantity,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);

            // 3️⃣ Deduct stock
            $stock->update([
                'total_qty' => $stock->total_qty - $request->quantity
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed & stock updated',
                'data' => $sale
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function show($id)
    {
        $sale = Sales::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale
        ], 200);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required',
            'quantity' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();

        try {
            $sale = Sales::find($id);

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found'
                ], 404);
            }

            $stock = Stock::where('product_no', $sale->product_no)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock not found'
                ], 404);
            }

            // পুরাতন qty ফেরত দাও
            $stock->total_qty += $sale->quantity;

            // নতুন qty চেক
            if ($stock->total_qty < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for update'
                ], 400);
            }

            // Sale update
            $sale->update([
                'date' => $request->date,
                'quantity' => $request->quantity,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);

            // আবার stock deduct
            $stock->total_qty -= $request->quantity;
            $stock->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale updated & stock adjusted',
                'data' => $sale
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sales::find($id);

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found'
                ], 404);
            }

            $stock = Stock::where('product_no', $sale->product_no)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                // stock ফেরত
                $stock->total_qty += $sale->quantity;
                $stock->save();
            }

            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted & stock restored'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function stock()
    {

        $stock = Stock::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $stock
        ], 200);
    }
}
