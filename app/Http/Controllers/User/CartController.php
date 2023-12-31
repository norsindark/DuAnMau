<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Products;
use App\Models\cartItem;
use App\Models\ProductImage;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Termwind\Components\Dd;

class CartController extends Controller
{

    public function index()
    {

        $product_images = ProductImage::all();

        $products = Products::all();

        $userId = auth()->id();
        // check user
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn phải đăng nhập để sử dụng chức năng giỏ hàng.');
        }


        // check cart of user
        $cart = Cart::where('user_id', $userId)->first();

        // if empty create new cart
        if (!$cart) {
            $cart = Cart::create(['user_id' => $userId]);
        }

        $cartItems = $cart->items;

        // total price
        $totalPrice = 0;

        $totalPriceFirst = 0;

        $priceSale = 0;


        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            $discountedPrice = $product->Price;

            if ($product->Sale > 0) {
                $discountedPrice = $product->Price - ($product->Sale / 100) * $product->Price;
            }

            $totalPrice += $cartItem->quantity * $discountedPrice;

            $priceSale += ($product->Price * $cartItem->quantity) - $totalPrice;

            $totalPriceFirst += $product->Price * $cartItem->quantity;
        }

        // $totalPrice = 0;
        // foreach ($cartItems as $cartItem) {
        //     $totalPrice += $cartItem->quantity * $cartItem->price;
        //     // $totalPrice += $cartItem->quantity * (($cartItem->$product->Sale / 100) * $cartItem->$cartItem->price );
        // }

        return view('frontend.cart.cart', compact('cartItems', 'products', 'totalPrice', 'product_images' , 'totalPriceFirst', 'priceSale'));
    }


    // public function addToCart(Request $request, $ProductId)
    // {

    //     // check User
    //     $userId = auth()->id();
    //     if (!$userId) {
    //         return back()->with('error', 'Bạn phải đăng nhập để sử dụng chức năng này.');
    //     }

    //     //check cart
    //     $cart = Cart::where('user_id', $userId)->first();
    //     if (!$cart) {
    //         $cart = Cart::create(['user_id' => $userId]);
    //     }

    //     //check product
    //     $product = Products::find($ProductId);
    //     if (!$product) {
    //         return back()->with('error', 'Sản phẩm không tồn tại.');
    //     }
    //     //Get CartItem 
    //     $cartItem = CartItem::where('cart_id', $cart->id)->where('product_id', $ProductId)->first();

    //     $quantity = $request->input('quantity');

    //     // validate dât
    //     $request->validate([
    //         'quantity' => 'required|integer|min:1',
    //     ]);

    //     // upđate or Create
    //     if ($cartItem) {
    //         $cartItem->update([
    //             'quantity' => $cartItem->quantity + $quantity,
    //         ]);
    //     } else {
    //         CartItem::create([
    //             'cart_id' => $cart->id,
    //             'product_id' => $ProductId,
    //             'quantity' => $quantity,
    //             'price' => $product->Price,
    //         ]);
    //     }

    //     return back()->with('success', 'Sản phẩm đã được thêm vào giỏ hàng.');
    // }

    public function addToCart(Request $request, $productId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return back()->with('error', 'Bạn phải đăng nhập để sử dụng chức năng này.');
        }

        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart) {
            $cart = Cart::create(['user_id' => $userId]);
        }

        $product = Products::find($productId);
        if (!$product) {
            return back()->with('error', 'Sản phẩm không tồn tại.');
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('product_id', $productId)->first();

        $quantity = $request->input('quantity');


        $quantityInWarehouse = 0;

        foreach ($product->warehouses as $warehouse) {
            $quantityInWarehouse += $warehouse->quantity;
        }

        if ($quantityInWarehouse <= 0) {
            return back()->with('error', "Sản phẩm đã hết hàng!");
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($newQuantity <= $quantityInWarehouse) {
                $cartItem->update(['quantity' => $newQuantity]);
            } else {
                return back()->with('error', 'Không đủ sản phẩm trong kho.');
            }
        } else {
            if ($quantity <= $quantityInWarehouse) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->Price,
                ]);
            } else {
                return back()->with('error', 'Không đủ sản phẩm trong kho.');
            }
        }

        return back()->with('success', 'Sản phẩm đã được thêm vào giỏ hàng.');
    }


    public function removeCartItem($id)
    {

        $cartItem = cartItem::findOrFail($id);

        $cartItem->delete();
        return redirect()->route('gio-hang')->with('success', 'Category deleted successfully.');
    }
}
