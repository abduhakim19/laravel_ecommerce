<?php

namespace App\Http\Controllers;

use App\Http\Models;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function update(Request $request) {
        $productImage = new PorductImage();
        $productImage->product_id = $product->id;
        $productImage->image = 'NULL';
        $productImage->save();

        
    }
}
