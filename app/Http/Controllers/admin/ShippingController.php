<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingController extends Controller
{
    public function index(Request $request)
    {
        $shippingCharges = Shipping::select("shipping_charges.*", "countries.name as countryName")
            ->latest('shipping_charges.id')
            ->leftJoin('countries', 'countries.id', 'shipping_charges.country_id');

        if (!empty($request->get('keyword'))) {
            $shippingCharges = $shippingCharges->where("countries.name", "like", "%" . $request->get('keyword') . "%");
        }

        $shippingCharges = $shippingCharges->paginate(10);

        return view('admin.shipping.list', compact('shippingCharges'));
    }
    public function create(Request $request)
    {
        $shippingCharges = Shipping::select("shipping_charges.*", "countries.name as countryName")
            ->latest('shipping_charges.id')
            ->leftJoin('countries', 'countries.id', 'shipping_charges.country_id');

        if (!empty($request->get('keyword'))) {
            $shippingCharges = $shippingCharges->where("countries.name", "like", "%" . $request->get('keyword') . "%");
        }

        $countries = Country::orderBy('name', 'ASC')->get();

        $shippingCharges = $shippingCharges->paginate(10);

        $data['countries'] = $countries;
        $data['shippingCharges'] = $shippingCharges;

        return view('admin.shipping.create', $data);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required|numeric'
        ]);

        if ($validator->passes()) {

            $shipping = new Shipping();
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success', 'Shipping created successfully');
            return response([
                'status' => true,
                'message' => 'Shipping created successfully'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($id, Request $request)
    {
        $shipping = Shipping::find($id);

        if (empty($shipping)) {
            session()->flash('error', 'Record not found');
            return redirect()->route('shipping.create');
        }

        $countries = Country::orderBy('name', 'ASC')->get();
        $data['countries'] = $countries;
        $data['shipping'] = $shipping;
        return view('admin.shipping.edit', $data);
    }

    public function update($id, Request $request)
    {
        $shipping = Shipping::find($id);

        if (empty($shipping)) {
            session()->flash('error', 'Record not found');
            return response([
                'status' => false,
                'notFound' => true,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'country' => 'required',
            'amount' => 'required'
        ]);

        if ($validator->passes()) {
            $shipping->country_id = $request->country;
            $shipping->amount = $request->amount;
            $shipping->save();

            session()->flash('success', 'Shipping updated successfully');
            return response([
                'status' => true,
                'message' => 'Shipping updated successfully'
            ]);
        }
    }

    public function destroy($id, Request $request)
    {
        $shipping = Shipping::find($id);

        if (empty($shipping)) {
            session()->flash('error', 'Shipping not found');
            return response()->json([
                'status' => false,
                'notFound' => true
            ]);
        }

        $shipping->delete();

        session()->flash('success', 'Shipping deleted successfully');

        return response()->json([
            'status' => true,
            'message' => 'Shipping deleted successfully'
        ]);
    }
}
