<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $category = Category::paginate(20);
        return ResponseHelper::respond(
            'v1',
            'Get Jobs',
            'GET',
            200,
            $category->items(),
            [
                'current_page' => $category->currentPage(),
                'count' => $category->perPage(),
                'total_count' => $category->total(),
                'has_more_pages' => $category->hasMorePages(),
                // 'previous_page' => $category->lastPage(),
            ]
        );
    }
}
