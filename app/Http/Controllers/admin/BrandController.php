<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::latest();

        if (!empty($request->get('keyword'))) {
            $brands = $brands->where("name", "like", "%" . $request->get('keyword') . "%");
        }
        $brands = $brands->paginate(10);

        return view('admin.brand.list', compact('brands'));
    }

    public function create(Request $request)
    {
        return view('admin.brand.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required:brands',
            'status' => 'required'
        ]);

        if ($validator->passes()) {

            $brand = new Brand();
            $brand->name = $request->name;
            $brand->slug = $request->slug;
            $brand->status = $request->status;
            $brand->save();

            session()->flash('success', 'Brand created successfully');

            return response([
                'status' => true,
                'message' => 'Brand created successfully'
            ]);
        } else {
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($id, Request $request)
    {
        $brand = Brand::find($id);

        if (empty($brand)) {
            session()->flash('error', 'Record not found');
            return redirect()->route('brands.index');
        }

        $data['brand'] = $brand;
        return view('admin.brand.edit', $data);
    }

    public function update($id, Request $request)
    {
        $brand = Brand::find($id);

        if (empty($brand)) {
            session()->flash('error', 'Record not found');
            return response([
                'status' => false,
                'notFound' => true,
            ]);
        }

        $validator =  Validator::make($request->all(), [
            'name' => 'required',
            //'slug' => 'required',
            'slug' => 'required|unique:brands,slug,' . $brand->id . ',id',
            'status' => 'required'
        ]);

        if ($validator->passes()) {

            $brand->name = $request->name;
            $brand->slug = $request->slug;
            $brand->status = $request->status;
            $brand->save();

            session()->flash('success', 'Brand updated successfully');

            return response([
                'status' => true,
                'message' => 'Brand updated successfully'
            ]);
        } else {
            return response([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function destroy($id, Request $request)
    {
        $brand = Brand::find($id);

        if (empty($brand)) {
            session()->flash('error', 'Brand not found');
            return response()->json([
                'status' => false,
                'notFound' => true,
            ]);
        }

        $brand->delete();

        session()->flash('success', 'Brand deleted successfully');
        return response()->json([
            'status' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }
}
