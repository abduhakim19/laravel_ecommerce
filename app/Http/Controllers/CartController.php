<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $product = Product::with('product_images')
            ->find($request->id);

        if ($product == null) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'

            ]);
        }

        if (Cart::count() > 0) {
            // echo "Product already in cart";
            // $status = false;
            // $message = "Salah";

            $cartContent = Cart::content();
            $productAlreadyExist = false;

            foreach ($cartContent as $item) {
                if ($item->id == $product->id) {
                    $productAlreadyExist = true;
                }
            }

            if ($productAlreadyExist == false) {
                Cart::add(
                    $product->id,
                    $product->title,
                    1,
                    $product->price,
                    ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']
                );

                $status = true;
                $message = '<strong>' . $product->title . '</strong> added in cart successfully';
                session()->flash('success', $message);
            } else {
                $status = false;
                $message = $product->title . ' already added in cart';
            }
        } else {
            Cart::add(
                $product->id,
                $product->title,
                1,
                $product->price,
                ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']
            );
            $status = true;
            $message = '<strong>' . $product->title . '</strong> added in cart successfully';

            session()->flash('success', $message);
        }
        return response()->json([
            'status' => $status,
            'message' => $message

        ]);
        //Cart::add('294ads', 'Product 1', 1, 9.99,);
    }

    public function cart()
    {
        $cartContent = Cart::content();
        $data['cartContent'] = $cartContent;
        return view('front.cart', $data);
    }

    public function updateCart(Request $request)
    {
        $rowId = $request->rowId;
        $qty = $request->qty;

        $itemInfo = Cart::get($rowId);

        $product = Product::find($itemInfo->id);
        // check qty available
        if ($product->track_qty == 'Yes') {

            if ($qty <= $product->qty) {
                Cart::update($rowId, $qty);
                $message = 'Cart updated successfully';
                $status = true;
                $header = "success";
            } else {
                $message = 'Request qty(' . $qty . ') not available in stock.';
                $status = false;
                $header = "error";
            }
        } else {
            Cart::update($rowId, $qty);
            $message = 'Cart updated successfully';
            $status = true;
            $header = "success";
        }

        session()->flash($header, $message);

        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function deleteItem(Request $request)
    {
        $itemInfo = Cart::get($request->rowId);

        if ($itemInfo == null) {
            $errorMessage = "Item not found in cart";
            session()->flash('error', $errorMessage);
            return response()->json([
                'status' => false,
                'message' => $errorMessage
            ]);
        }

        Cart::remove($request->rowId);

        $message = "Item removed from cart successfully";
        session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function checkout()
    {
        if (Cart::count() == 0) {
            return redirect()->route('front.cart');
        }

        if (Auth::check() == false) {

            if (!session()->has('url.intended')) {
                session(['url.intended' => url()->current()]);
            }

            return redirect()->route('account.login');
        }

        $customerAddress = CustomerAddress::where('user_id', Auth::user()->id)->first();

        session()->forget('url.intended');

        $countries = Country::orderBy('name', 'ASC')->get();

        if ($customerAddress != '') {
            $userCountry = $customerAddress->country_id;
            $shippingInfo = Shipping::where('country_id', $userCountry)->first();

            $totalQty = 0;
            $totalShippingCharge = 0;
            $grandTotal = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            $totalShippingCharge = $totalQty * $shippingInfo->amount;

            $grandTotal = Cart::subtotal(2, '.', '') + $totalShippingCharge;
        } else {
            $grandTotal = Cart::subtotal(2, '.', '');
            $totalShippingCharge = 0;
        }

        return view('front.checkout', [
            'countries' => $countries,
            'customerAddress' => $customerAddress,
            'totalShippingCharge' => $totalShippingCharge,
            'grandTotal' => $grandTotal
        ]);
    }

    public function processCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:5',
            'last_name' => 'required',
            'email' => 'required',
            'country' => 'required',
            'address' => 'required|min:30',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'mobile' => 'required',
            'state' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the errors',
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        //$customerAddress = CustomerAddress::find();

        $user = Auth::user();

        CustomerAddress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'country_id' => $request->country,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
            ]
        );



        if ($request->payment_method == 'cod') {

            $shipping = 0;
            $discount = 0;
            $subTotal = Cart::subtotal(2, '.', '');
            $grandTotal = $subTotal + $shipping;

            $shippingInfo = Shipping::where('country_id', $request->country)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if ($shippingInfo != null) {
                $shipping = $totalQty * $shippingInfo->amount;
                $grandTotal = $subTotal + $shipping;
            } else {
                $shippingInfo = Shipping::where('country_id', '243')->first();

                $shipping = $totalQty * $shippingInfo->amount;
                $grandTotal = $subTotal + $shipping;
            }

            $order = new Order;
            $order->subtotal = $subTotal;
            $order->shipping = $shipping;
            $order->grand_total = $grandTotal;
            $order->user_id = $user->id;

            $order->first_name = $request->first_name;
            $order->last_name = $request->last_name;
            $order->email = $request->email;
            $order->mobile = $request->mobile;
            $order->address = $request->address;
            $order->apartment = $request->apartment;
            $order->state = $request->state;
            $order->city = $request->city;
            $order->zip = $request->zip;
            $order->notes = $request->order_notes;
            $order->country_id = $request->country;
            $order->save();

            foreach (Cart::content() as $item) {
                $orderItem = new OrderItem;
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->name = $item->name;
                $orderItem->qty = $item->qty;
                $orderItem->price = $item->price;
                $orderItem->total = $item->price * $item->qty;
                $orderItem->save();
            }

            session()->flash('success', 'You have successfully placed your order.');

            Cart::destroy();

            return response()->json([
                'message' => 'Order saved successfully',
                'orderId' => $order->id,
                'status' => true,
            ]);
        } else {
        }
    }

    public function thankyou($id)
    {
        return view('front.thanks', [
            'id' => $id,
        ]);
    }

    public function getOrderSummary(Request $request)
    {
        $subTotal = Cart::subtotal(2, '.', '');
        if ($request->country_id > 0) {

            $shippingInfo = Shipping::where('country_id', $request->country_id)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if ($shippingInfo != null) {
                $shippingCharge = $totalQty * $shippingInfo->amount;

                $grandTotal = $subTotal + $shippingCharge;

                return response()->json([
                    'status' => true,
                    'grandTotal' => number_format($grandTotal, 2),
                    'shippingCharge' => number_format($shippingCharge, 2)
                ]);
            } else {
                $shippingInfo = Shipping::where('country_id', '243')->first();

                $shippingCharge = $totalQty * $shippingInfo->amount;
                $grandTotal = $subTotal + $shippingCharge;

                return response()->json([
                    'status' => true,
                    'grandTotal' => number_format($grandTotal, 2),
                    'shippingCharge' => number_format($shippingCharge, 2)
                ]);
            }
        } else {
            return response()->json([
                'status' => true,
                'grandTotal' => number_format($subTotal, 2),
                'shippingCharge' => number_format(0, 2)
            ]);
        }
    }
}
