<?php

namespace App\Http\Controllers;

use App\Http\Models;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function update(Request $request) {
        $image = $request->image;
        $ext = $image->getClientOriginalExtension();
        $tempImageLocation = $image->getImagePath();

        $productImage = new PorductImage();
        $productImage->product_id = $request->product_id;
        $productImage->image = 'NULL';
        $productImage->save();

        $imageName = $request->product_id.'-'.$productImage->id.'.'.$ext;
        $productImage->image = $imageName;
        $productImage->save();

        // Large Image
        $sourcePath = $tempImageLocation;
        $destPath = public_path() . '/uploads/product/large/' . $imageName;
        $image = Image::make($sourcePath);
        $image->resize(1400, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $image->save($destPath);

        // Small Image
        $destPath = $tempImageLocation;
        $image = Image::make($sourcePath);
        $image->fit(300, 300);
        $image->save($destPath);
        
    }
}
