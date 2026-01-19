<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PaymentRec;
use Illuminate\Support\Str;
use App\Models\OfficeLedger;
use Illuminate\Http\Request;
use App\Models\CustomerLedger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;

class PaymentRecieveController extends Controller
{


    public function index()
    {
        $data = PaymentRec::all();

        return response()->json([
            'status' => 'Success',
            'data' => $data
        ], 200);
    }


    public function store(Request $request)
    {

        DB::beginTransaction();

        try {


            $image_name = null;

            /* ================= IMAGE UPLOAD & OPTIMIZATION ================= */
            if ($request->hasFile('image')) {

                $uploadPath = public_path('uploads/paymentRc');

                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                $manager = new ImageManager(new Driver());
                $img = $manager->read($request->file('image')->getPathname());

                $img->scaleDown(1024);

                $image_name = time() . '_' . Str::random(6) . '.jpg';

                $img->toJpeg(70)->save($uploadPath . '/' . $image_name);
            }


            // Insert into trips table
            $payment_rec = PaymentRec::create([
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'date'  => $request->date,
                'bill_ref'  => $request->bill_ref,
                'amount' => $request->amount,
                'status' => $request->status,
                'branch_name' => $request->branch_name,
                'remarks' => $request->remarks,
                'cash_type' => $request->cash_type,
                'image' => $image_name,

                'created_by'   => $request->created_by,

            ]);

            // Insert into branch_ledgers
            OfficeLedger::create([
                'user_id' => Auth::id(),
                'date'               => $request->date,
                'payment_rec_id' => $payment_rec->id,
                'customer'           => $request->customer_name,
                'branch_name' => $request->branch_name,
                'cash_in'      => $request->amount,
                'created_by'         => $request->created_by,
            ]);

            CustomerLedger::create([
                'user_id' => Auth::id(),
                'bill_date'  => $request->date,
                'payment_rec_id' => $payment_rec->id,
                'customer_name'  => $request->customer_name,
                'rec_amount' => $request->amount,
                'created_by'  => $request->created_by,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ' created successfully',
                'data'    => $payment_rec,
                'image_url' => $image_name
                    ? asset('uploads/paymentRc/' . $image_name)
                    : null
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $Employee = PaymentRec::find($id);
        if (!$Employee) {
            return response()->json(['success' => false, 'message' => ' not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Employee]);
    }

    // Update PaymentRec and related ledgers
   public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {

        $payment_rec = PaymentRec::findOrFail($id);
        $image_name = $payment_rec->image; // old image থাকবে

        /* ============ IMAGE UPDATE ============ */
        if ($request->hasFile('image')) {

            $uploadPath = public_path('uploads/paymentRc');

            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $manager = new ImageManager(new Driver());
            $img = $manager->read($request->file('image')->getPathname());

            // Intervention v3 safe resize
            $img->scaleDown(1024);

            $image_name = uniqid('pay_', true) . '.jpg';

            $img->toJpeg(70)->save($uploadPath . '/' . $image_name);
        }

        /* ============ UPDATE PAYMENT ============ */
        $payment_rec->update([
            'customer_name' => $request->customer_name,
            'date'          => $request->date,
            'bill_ref'      => $request->bill_ref,
            'amount'        => $request->amount,
            'status'        => $request->status,
            'branch_name'   => $request->branch_name,
            'remarks'       => $request->remarks,
            'cash_type'     => $request->cash_type,
            'image'         => $image_name, // old or new
        ]);

        /* ============ UPDATE LEDGERS ============ */
        OfficeLedger::where('payment_rec_id', $payment_rec->id)->update([
            'date'        => $request->date,
            'customer'    => $request->customer_name,
            'branch_name' => $request->branch_name,
            'cash_in'     => $request->amount,
            
        ]);

        CustomerLedger::where('payment_rec_id', $payment_rec->id)->update([
            'bill_date'     => $request->date,
            'customer_name' => $request->customer_name,
            'rec_amount'    => $request->amount,
            
        ]);

        DB::commit();

        return response()->json([
            'success'   => true,
            'message'   => 'Updated successfully',
            'data'      => $payment_rec,
            'image_url' => $image_name
                ? asset('uploads/paymentRc/' . $image_name)
                : null
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Update failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}



    // Delete PaymentRec and related ledgers
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $payment_rec = PaymentRec::findOrFail($id);

            // Delete related ledgers
            OfficeLedger::where('payment_rec_id', $payment_rec->id)->delete();
            CustomerLedger::where('payment_rec_id', $payment_rec->id)->delete();

            // Delete PaymentRec
            $payment_rec->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
