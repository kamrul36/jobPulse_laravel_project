<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index()
    {

        $category = Category::with(['createdBy', 'updatedBy', 'deletedBy'])
            ->paginate(20);

        return ResponseHelper::respond(
            'v1',
            'Get Categories',
            'GET',
            200,
            CategoryResource::collection($category),
            [
                'current_page' => $category->currentPage(),
                'count' => $category->perPage(),
                'total_count' => $category->total(),
                'has_more_pages' => $category->hasMorePages(),
            ]
        );
    }

    public function create(Request $request)
    {
        try {

            // Get authenticated user data from CheckJobPermission middleware
            $userId = $request->auth_user_id;

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'icon' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Create job with authenticated user's ID as employer
            $job = Category::create([
                'name' => $validated['name'],
                'icon' => $validated['description'] ?? null,
                'status' => 1,
                'created_by' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'A category created successfully.',
                'data' => $job
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
