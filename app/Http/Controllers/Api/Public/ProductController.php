<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        //get products
        $products = Product::query()
            ->with('category', 'ratings.customer')
            ->withAvg('ratings', 'rating')
            ->when(request()->has('search'), function ($query) {
                $query->where('title', 'like', '%' . request()->search . '%');
            })
            ->paginate(8);

        //format avg_rating to one decimal
        $products->getCollection()->transform(function ($product) {
            $product->ratings_avg_rating = number_format($product->ratings_avg_rating, 1); //mengubah menjadi string dengan 1 decimal
            return $product;
        });

        //return response JSON products
        return response()->json([
            'success' => true,
            'message' => 'List Produk',
            'data'    => $products
        ]);
    }

    /**
     * productPopular
     *
     * @return void
     */
    public function productPopular()
    {
        //get popular products
        $products = Product::query()
            ->with('category', 'ratings.customer')
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings' => function ($query) {
                $query->where('rating', '>=', 4); //Count ratings >= 4 only
            }])
            ->when(request()->has('search'), function ($query) {
                $query->where('title', 'like', '%' . request()->search . '%');
            })
            ->orderBy('ratings_count', 'desc') //sort by count of ratings >= 4
            ->take(5)
            ->get();

        //format avg_rating to one decimal place
        $products->transform(function ($product) {
            $product->ratings_avg_rating = number_format($product->ratings_avg_rating, 1); //2m round to keep it as a number
            return $product;
        });

        //return response JSON products
        return response()->json([
            'success' => true,
            'message' => 'List Produk Terpopuler',
            'data'    => $products
        ]);
    }

    /**
     * show
     *
     * @param mixed $slug
     * @return void
     */
    public function show($slug)
    {
        //get product by slug
        $product = Product::query()
            ->with('category', 'ratings.customer')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->where('slug', $slug)
            ->firstOrFail();

        //format ratings_avg_rating to one decimal
        $product->ratings_avg_rating = number_format($product->ratings_avg_rating, 1);

        //return response JSON product
        return response()->json([
            'success' => true,
            'message' => 'Detail Produk',
            'data'    => $product
        ]);
    }
}