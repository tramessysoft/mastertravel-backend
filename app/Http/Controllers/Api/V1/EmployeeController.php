<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Employee;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use Intervention\Image\Encoders\JpegEncoder;


 
use Illuminate\Support\Facades\File;





class EmployeeController extends Controller
{
    public function index()
    {
        $Employees = Employee::all();
        return response()->json([
            'success' => true,
            'data' => $Employees
        ]);
    }






    public function store(Request $request)
        {
    // ✅ Validation: max 10MB
    $request->validate([
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240', // 10MB
    ]);

    DB::beginTransaction();

    try {
        $image = null;

        /* ================= IMAGE OPTIMIZATION ================= */
        if ($request->hasFile('image')) {

            // 🔹 Ensure upload folder exists
            $uploadPath = public_path('uploads/employee');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            // 🔹 Intervention Image v3
            $manager = new ImageManager(new Driver());
            $img = $manager->read($request->file('image')->getPathname());

            // 🔹 Resize width/height max 1024px, maintain aspect ratio
            $img->resize(1024, 1024, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // 🔹 Encode properly with JPEG quality 70%
            $encoder = new JpegEncoder(70);
            $img->encode($encoder);

            // 🔹 Optimized filename
            $image_name = time() . '_' . Str::random(10) . '.jpg';

            // 🔹 Save optimized image
            $img->save($uploadPath . '/' . $image_name);

            $image = $image_name;
        }

        /* ================= CREATE EMPLOYEE ================= */
        $employee = Employee::create(
            $request->except('image') + [
                'user_id' => Auth::id(),
                'image'   => $image,
            ]
        );

        DB::commit();

        return response()->json([
            'status'    => 'Success',
            'message'   => 'Employee created successfully',
            'data'      => $employee,
            'image_url' => $image ? url('uploads/employee/' . $image) : null,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        // ✅ Full debug info
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'trace'   => $e->getTraceAsString(),
        ], 500);
         }
    }




   public function update(Request $request, $id)
{
    // ✅ Validation: max 10MB
    $request->validate([
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240', // 10MB
    ]);

    DB::beginTransaction();

    try {
        // Find employee
        $employee = Employee::findOrFail($id);

        $image = $employee->image; // preserve old image by default

        /* ================= IMAGE OPTIMIZATION ================= */
        if ($request->hasFile('image')) {

            // 🔹 Ensure upload folder exists
            $uploadPath = public_path('uploads/employee');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            // 🔹 Delete old image if exists
            if ($employee->image && File::exists($uploadPath . '/' . $employee->image)) {
                File::delete($uploadPath . '/' . $employee->image);
            }

            // 🔹 Intervention Image v3
            $manager = new ImageManager(new Driver());
            $img = $manager->read($request->file('image')->getPathname());

            // 🔹 Resize width/height max 1024px, maintain aspect ratio
            $img->resize(1024, 1024, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // 🔹 Encode properly with JPEG quality 70%
            $encoder = new JpegEncoder(70);
            $img->encode($encoder);

            // 🔹 Optimized filename
            $image_name = time() . '_' . Str::random(10) . '.jpg';

            // 🔹 Save optimized image
            $img->save($uploadPath . '/' . $image_name);

            $image = $image_name;
        }

        /* ================= UPDATE EMPLOYEE ================= */
        $employee->update(
            $request->except('image') + [
                'image' => $image,
            ]
        );

        DB::commit();

        return response()->json([
            'status'    => 'Success',
            'message'   => 'Employee updated successfully',
            'data'      => $employee,
            'image_url' => $image ? url('uploads/employee/' . $image) : null,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        // ✅ Full debug info
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'trace'   => $e->getTraceAsString(),
        ], 500);
    }
}


    public function show($id)
    {
        $Employee = Employee::find($id);
        if (!$Employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $Employee]);
    }


    // public function update(Request $request, $id)
    // {
    //     $Employee = Employee::find($id);
    //     if (!$Employee) {
    //         return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
    //     }

    //     $Employee->update($request->all());
    //     return response()->json(['success' => true, 'message' => 'Employee updated successfully', 'data' => $Employee]);
    // }

    public function destroy($id)
    {
        $Employee = Employee::find($id);
        if (!$Employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $Employee->delete();
        return response()->json(['success' => true, 'message' => 'Employee deleted successfully']);
    }
}
