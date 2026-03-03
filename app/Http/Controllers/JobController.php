<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Models\Category;
use App\Models\Job;
use App\Services\JWTService;
use Illuminate\Http\Request;
use App\Http\Resources\Job as JobResource;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    public function jobs()
    {
        $jobsQuery = Job::where('status', true)->with(relations: 'category');

        $jobs = $jobsQuery->paginate(20);
        return ResponseHelper::respond(
            'v1',
            'Get Jobs',
            'GET',
            200,
            $jobs->items(),
            [
                'current_page' => $jobs->currentPage(),
                'count' => $jobs->perPage(),
                'total_count' => $jobs->total(),
                'has_more_pages' => $jobs->hasMorePages(),
                // 'previous_page' => $jobs->lastPage(),
            ]
        );
    }

    public function featuredJobs()
    {
        $featuredQuery = Job::where('status', true)->where('isFeatured', true)->with('employer')->where('status', true);
        $featured = $featuredQuery->paginate((20));

        return ResponseHelper::respond(
            'v1',
            'Featured Jobs',
            'GET',
            200,
            $featured->items(),
            [
                'current_page' => $featured->currentPage(),
                'count' => $featured->perPage(),
                'total_count' => $featured->total(),
                'has_more_pages' => $featured->hasMorePages(),
            ]

        );
    }

    /**
     * Create a new job (Employer only)
     */
    public function create(Request $request)
    {
        try {
            // Get authenticated user data from CheckJobPermission middleware
            $userId = $request->auth_user_id;

            // Validate request
            $validator = Validator::make($request->all(), [
                'title' => 'required|max:255',
                'category_id' => 'required|exists:categories,id',
                'description' => 'nullable|string',
                'skills' => 'required|string',
                'salary' => 'nullable|string',
                'deadline' => 'nullable|date',
                'open_position' => 'nullable|string',
                'location' => 'required|string',
                'type' => 'required|in:full_time,remote,part_time,project_basis,freelance',
                'experience' => 'nullable|string',
                'isFeatured' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Create job with authenticated user's ID as employer
            $job = Job::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'skills' => $validated['skills'],
                'salary' => $validated['salary'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'open_position' => $validated['open_position'] ?? null,
                'location' => $validated['location'],
                'experience' => $validated['experience'] ?? null,
                'type' => $validated['type'],
                'category_id' => $validated['category_id'],
                'created_by' => $userId, // From JWT token
                'isFeatured' => $validated['isFeatured'] ?? false,
                'status' => 0, // Inactive by default (needs to be published)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Job created successfully. Please publish it to make it visible.',
                'data' => $job
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing job (Employer only - can only update their own jobs)
     */
    public function update(Request $request, $id)
    {
        try {
            // Get authenticated user from CheckJobPermission middleware
            $userId = $request->auth_user_id;
            $userRole = $request->auth_user_role;

            // Find job with role-based filter
            $job = Job::where('id', $id)
                ->when($userRole === 'employer', function ($query) use ($userId) {
                    return $query->where('created_by', $userId); // ✅ Changed from employer_id
                })
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            // Check if the job belongs to the authenticated employer
            if ($job->employer_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own jobs'
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|max:255',
                'category_id' => 'sometimes|required|exists:categories,id',
                'description' => 'nullable|string',
                'skills' => 'sometimes|required|string',
                'salary' => 'nullable|string',
                'deadline' => 'nullable|date',
                'open_position' => 'nullable|string',
                // 'type' => 'sometimes|required|in:full_time,remote,part_time,project_basis,freelance',
                // 'location' => 'sometimes|required|string',
                'type' => 'sometimes|nullable|in:full_time,remote,part_time,project_basis,freelance',
                'location' => 'sometimes|nullable|string',
                'experience' => 'nullable|string',
                'isFeatured' => 'nullable|boolean',
                'status' => 'nullable|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $validated['updated_by'] = $userId;

            // Update job
            $job->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Job updated successfully.',
                'data' => $job
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a job (activate job)
     */
    public function publishJob(Request $request, $id)
    {
        try {

            // dd($request->all());

            // Get authenticated user from CheckJobPermission middleware
            $userId = $request->auth_user_id;
            $userRole = $request->auth_user_role;

            $job = Job::where('id', $id)
                ->when($userRole === 'employer', fn($q) => $q->where('employer_id', $userId))
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or you do not have permission to update this job'
                ], 404);
            }

            $job->update(['status' => 1]);

            return ResponseHelper::respond(
                'v1',
                'Job published successfully',
                'POST',
                200,
                new JobResource($job),
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish job',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteJob(Request $request, $id)
    {
        try {
            // Get authenticated user from CheckJobPermission middleware
            $userId = $request->auth_user_id;
            $userRole = $request->auth_user_role;

            $job = Job::where('id', $id)
                ->when($userRole === 'employer', fn($q) => $q->where('employer_id', $userId))
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or unauthorized'
                ], 404);
            }

            $job->delete(); // Soft delete

            return response()->json([
                'success' => true,
                'message' => 'Job moved to trash successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function unpublishJob(Request $request, $id)
    {
        try {
            // Get authenticated user from CheckJobPermission middleware
            $userId = $request->auth_user_id;
            $userRole = $request->auth_user_role;

            $job = Job::where('id', $id)
                ->when($userRole === 'employer', fn($q) => $q->where('employer_id', $userId))
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found or unauthorized'
                ], 404);
            }

            $job->update(['status' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Job unpublished successfully',
                'data' => $job
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all jobs by authenticated employer
     */
    public function getMyJobs()
    {
        try {
            // Get authenticated user from JWT token
            $jwtService = new JWTService();
            $token = $jwtService->getTokenFromRequest();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided'
                ], 401);
            }

            $user = $jwtService->getUserFromToken($token);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Check if user has employer role
            if (!$user->hasRole('employer')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only employers can view their jobs'
                ], 403);
            }

            // Get all jobs for this employer
            $jobs = Job::where('employer_id', $user->id)
                ->with('category') // If you have category relationship
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $jobs
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function show($id, $count_views = "no")
    {
        $job = Job::findOrFail($id);
        if ($count_views == "yes") {
            $count_views = $job->value('views');

            $job->views = $count_views + 1;

            $job->save();
            if (!$job) {
                return response()->json(['message' => 'Failed loading jobs'], 500);
            }

        }

        return response()->json(['data' => $job->with('employer')->where('id', $id)->get()]);
    }


    public function searchJobs(Request $request)
    {
        $request->validate([
            'search_query' => 'required|string|max:255',
        ]);

        $search = trim($request->search_query);
        $keywords = explode(' ', $search);
        $jobsQuery = Job::query()
            ->with('employer', 'category')
            ->where('status', true);

        $jobsQuery->where(function ($query) use ($keywords) {
            foreach ($keywords as $word) {
                $query->where(function ($q) use ($word) {
                    $q->where('title', 'like', "%{$word}%")
                        ->orWhere('location', 'like', "%{$word}%")
                        ->orWhere('skills', 'like', "%{$word}%")
                        ->orWhere('experience', 'like', "%{$word}%")
                        ->orWhereHas('category', function ($cat) use ($word) {
                            $cat->where('slug', 'like', "%{$word}%");
                        });
                });
            }
        });

        $jobs = $jobsQuery->latest()->paginate(20);

        return ResponseHelper::respond(
            'v1',
            'Get search result',
            'POST',
            200,
            $jobs->items(),
            [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
                'has_more_pages' => $jobs->hasMorePages(),
            ]
        );
    }

    // public function queryJobs(Request $request)
    // {
    //     $jobcategory_slug = array();
    //     $jobtype = array();
    //     $jobexperience = array();
    //     $job_location = "";
    //     $job_isFeatured = "";
    //     $search_key = "";

    //     if ($request->category) {
    //         $jobcategory_slug = explode(',', $request->category);
    //     }
    //     if ($request->search) {
    //         $search_key = $request->search;
    //     }

    //     if ($request->jobtype) {
    //         $jobtype = explode(',', $request->jobtype);

    //     }
    //     if ($request->experience) {
    //         $jobexperience = explode(',', $request->experience);
    //     }
    //     if ($request->location) {
    //         //future function later project we will pass here array so prototype for that
    //         $job_location = $request->location;
    //     }

    //     $job_category_id = array();

    //     foreach ($jobcategory_slug as $slug) {

    //         $job_category_id[] = Category::where('slug', $slug)->value('id');
    //     }

    //     $jobsSearch = Job::where(function ($q) use ($job_category_id) {
    //         foreach ($job_category_id as $value) {
    //             $q->orWhere('category_id', $value);
    //         }
    //     })->where(function ($q) use ($jobtype) {
    //         foreach ($jobtype as $value) {
    //             $q->orWhere('type', $value);
    //         }
    //     })
    //         ->where(function ($q) use ($jobexperience) {
    //             foreach ($jobexperience as $value) {
    //                 $q->orWhere('experience', $value);
    //             }
    //         })
    //         ->where('location', 'like', '%' . $job_location . '%')

    //         ->where('title', 'like', '%' . $search_key . '%')

    //         // ->where('skill', 'like', '%' . $search_key . '%')

    //         ->with('employer')->where('status', true);

    //     if ($request->isFeatured) {
    //         //future function later project we will pass here array so prototype for that
    //         $job_isFeatured = '1';
    //         $jobsSearch = $jobsSearch->where('isFeatured', true);
    //     }

    //     $jobs = $jobsSearch->paginate(20);

    //     return ResponseHelper::respond(
    //         'v1',
    //         'Get search result',
    //         'GET',
    //         200,
    //         $jobs->items(),
    //         [
    //             'current_page' => $jobs->currentPage(),
    //             'count' => $jobs->perPage(),
    //             'total_count' => $jobs->total(),
    //             'has_more_pages' => $jobs->hasMorePages(),
    //         ]
    //     );
    // }

}


