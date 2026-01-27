<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::all();
        return ResponseHelper::Out('v1', 'Get all categories', 'GET', $category,200);
        // return response()->json(['data' => $category]);
    }
}
