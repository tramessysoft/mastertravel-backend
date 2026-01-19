<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Str;
use App\Models\OfficeLedger;
use App\Models\VendorLedger;
use Illuminate\Http\Request;
use App\Models\VendorPayment;
use Intervention\Image\Image;
use Illuminate\Support\Facades\DB;
// Intervention Image (without Facade)
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;



class VendorPaymentController extends Controller
{
    public function index()
    {
        $data = VendorPayment::all();

        return response()->json([
            'status' => 'Success',
            'data' => $data
        ], 200);
    }






    public function store(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240', 
           
        ]);

        DB::beginTransaction();

        try {
            $image_name = null;

            /* ================= IMAGE UPLOAD & OPTIMIZATION ================= */
            if ($request->hasFile('image')) {

                $uploadPath = public_path('uploads/payment');

                // 🔹 Ensure folder exists
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                // 🔹 Intervention Image v3
                $manager = new ImageManager(new Driver());
                $img = $manager->read($request->file('image')->getPathname());

                // 🔹 Resize max width 1024px (maintain aspect ratio)
                $img->resize(1024, 1024, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // 🔹 Encode JPEG with quality 70%
                $encoder = new JpegEncoder(70);
                $img->encode($encoder);

                // 🔹 Generate unique filename
                $image_name = time() . '_' . Str::random(6) . '.jpg';

                // 🔹 Save optimized image
                $img->save($uploadPath . '/' . $image_name);
            }

            /* ================= INSERT INTO DATABASE ================= */
            $dailyExp = VendorPayment::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'vendor_name' => $request->vendor_name,
                'branch_name' => $request->branch_name,
                'bill_ref' => $request->bill_ref,
                'amount' => $request->amount,
                'note' => $request->note,
                'cash_type' => $request->cash_type,
                'image' => $image_name,
                'status' => $request->status,
            ]);

            OfficeLedger::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'payment_id' => $dailyExp->id,
                'cash_out' => $request->amount,
                'branch_name' => $request->branch_name,
                'remarks' => $request->note,
            ]);

            VendorLedger::create([
                'user_id' => Auth::id(),
                'date' => $request->date,
                'bill_id' => $dailyExp->id,
                'pay_amount' => $request->amount,
                'vendor_name' => $request->vendor_name,
                'branch_name' => $request->branch_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendor payment created successfully',
                'data' => $dailyExp,
                'image_url' => $image_name ? url('uploads/payment/' . $image_name) : null,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }





    public function show($id)
    {
        $data = VendorPayment::findOrFail($id);
        return response()->json([
            'status' => 'Success',
            'data' => $data
        ], 200);
    }




    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8120', // 5MB
        ]);

        DB::beginTransaction();

        try {
            $dailyExp = VendorPayment::findOrFail($id);

            /* ================= IMAGE UPDATE (OPTIONAL) ================= */
            $image_name = $dailyExp->image; // default old image

            if ($request->hasFile('image')) {

                // 🔹 delete old image if exists
                if ($dailyExp->image && File::exists(public_path('uploads/payment/' . $dailyExp->image))) {
                    File::delete(public_path('uploads/payment/' . $dailyExp->image));
                }

                // 🔹 Intervention Image v3
                $manager = new ImageManager('gd');
                $image = $manager->read($request->file('image'));

                $image_name = time() . '.jpg';
                $path = public_path('uploads/payment/' . $image_name);

                $image->resize(1024, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->toJpeg(70)->save($path);
            }

            /* ================= UPDATE PAYMENT ================= */
            $dailyExp->update([
                'date'         => $request->date,
                'vendor_name'  => $request->vendor_name,
                'branch_name'  => $request->branch_name,
                'bill_ref'     => $request->bill_ref,
                'amount'       => $request->amount,
                'note'         => $request->note,
                'cash_type'    => $request->cash_type,
                'image'        => $image_name,
                'status'       => $request->status,
            ]);

            /* ================= UPDATE OFFICE LEDGER ================= */
            OfficeLedger::where('payment_id', $dailyExp->id)->update([
                'date'        => $request->date,
                'cash_out'    => $request->amount,
                'branch_name' => $request->branch_name,
                'remarks'     => $request->note,
            ]);

            /* ================= UPDATE VENDOR LEDGER ================= */
            VendorLedger::where('bill_id', $dailyExp->id)->update([
                'date'        => $request->date,
                'pay_amount'  => $request->amount,
                'vendor_name' => $request->vendor_name,
                'branch_name' => $request->branch_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
                'data'    => $dailyExp
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


    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Find the VendorPayment record for the logged-in user
            $payment = VendorPayment::find($id);

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            // Delete related entries
            OfficeLedger::where('payment_id', $payment->id)->delete();
            VendorLedger::where('bill_id', $payment->id)->delete();

            // Delete the main VendorPayment record
            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendor payment and related records deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
