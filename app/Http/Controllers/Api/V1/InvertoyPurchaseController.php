<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Models\Inventroy_purchase;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;

class InvertoyPurchaseController extends Controller
{


    public function index()
    {
        $purchases = Inventroy_purchase::all();
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // 1️⃣ Purchase insert
            $purchase = Inventroy_purchase::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'product_no' => $request->product_no,
                'product_name' => $request->product_name,
                'quantity' => $request->quantity,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);

            // 2️⃣ Check stock exists or not
            $stock = Stock::where('product_no', $request->product_no)->first();

            if ($stock) {
                // Stock exists → ADD quantity
                $stock->update([
                    'total_qty' => $stock->total_qty + $request->quantity
                ]);
            } else {
                // New product → Create stock
                Stock::create([
                    'product_no' => $request->product_no,
                    'product_name' => $request->product_name,
                    'total_qty' => $request->quantity,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase & Stock updated successfully'
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
        $purchase = Inventroy_purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $purchase
        ]);
    }





    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $purchase = Inventroy_purchase::find($id);

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase not found'
                ], 404);
            }

            //  Step 1: old stock reverse
            $oldStock = Stock::where('product_no', $purchase->product_no)->first();
            if ($oldStock) {
                $oldStock->update([
                    'total_qty' => $oldStock->total_qty - $purchase->quantity
                ]);
            }

            //  Step 2: update purchase
            $purchase->update([
                'date' => $request->date,
                'product_no' => $request->product_no,
                'product_name' => $request->product_name,
                'quantity' => $request->quantity,
                'amount' => $request->amount,
                'remarks' => $request->remarks,
            ]);

            //  Step 3: add new stock
            $newStock = Stock::where('product_no', $request->product_no)->first();

            if ($newStock) {
                $newStock->update([
                    'total_qty' => $newStock->total_qty + $request->quantity
                ]);
            } else {
                Stock::create([
                    'product_no' => $request->product_no,
                    'product_name' => $request->product_name,
                    'total_qty' => $request->quantity
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase & stock updated successfully'
            ]);
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

            $purchase = Inventroy_purchase::find($id);

            if (!$purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase not found'
                ], 404);
            }

            //  Reverse stock
            $stock = Stock::where('product_no', $purchase->product_no)->first();
            if ($stock) {
                $stock->update([
                    'total_qty' => $stock->total_qty - $purchase->quantity
                ]);
            }

            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted & stock adjusted'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
