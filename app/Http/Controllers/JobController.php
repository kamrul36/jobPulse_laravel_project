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
        $jobsQuery = Job::where('status', true)->with('employer');

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
                    'message' => 'Only employers can create jobs'
                ], 403);
            }

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

            // Create job with authenticated employer's ID
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
                'employer_id' => $user->id, // Use authenticated user's ID
                'isFeatured' => $validated['isFeatured'] ?? false,
                'status' => 1, // active by default
            ]);

            return response()->json([
                'success' => true,
                'message' => 'You have added a new job successfully.',
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




    public function update(Request $request)
    {
        $employer_id = $request->user()->id;

        $validated = $request->validate([
            'title' => 'required',
            'category_id' => 'required',
            'description' => 'required',
            'skills' => 'required',
            'salary' => 'required',
            'deadline' => 'required',
            'open_position' => 'required',
            'location' => 'required',
            'type' => 'required',
            'job_id' => 'required',
            'experience' => 'required',
        ]);
        $employer_id = $request->user()->id;

        $job = Job::where('id', $request->job_id)->where('employer_id', $employer_id)->first();

        $job->title = $request->title;
        $job->category_id = $request->category_id;
        $job->description = $request->description;
        $job->skills = $request->skills;
        $job->salary = $request->salary;
        $job->deadline = $request->deadline;
        $job->open_position = $request->open_position;
        $job->location = $request->location;

        $job->employer_id = $employer_id;
        $job->experience = $request->experience;
        $job->type = $request->type;

        if ($job->save()) {

            return response()->json(['data' => $job, 'message' => 'You have updated job data.']);
        } else {
            return response()->json(['message' => 'Failed to update job.'], 500);

        }
    }

    public function destroyJob(Request $request)
    {
        $employer_id = $request->user()->id;
        $job = Job::where('id', $request->job_id)->where('employer_id', $employer_id)->first();

        $job->status = 'deleted';
        if ($job->save()) {

            return response()->json(['data' => $job, 'message' => 'You have deleted job.']);
        } else {
            return response()->json(['message' => 'Failed to delete job.'], 500);

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



    public function closeJob(Request $request)
    {
        $employer_id = $request->user()->id;
        $job = Job::where('id', $request->job_id)->where('employer_id', $employer_id)->first();

        $job->status = 'closed';
        if ($job->save()) {

            return response()->json(['data' => $job, 'message' => 'You have updated job.']);
        } else {
            return response()->json(['message' => 'Failed to delete job.'], 500);

        }

    }

    public function activeJob(Request $request)
    {
        $employer_id = $request->user()->id;
        $job = Job::where('id', $request->job_id)->where('employer_id', $employer_id)->first();

        $job->status = 'active';
        if ($job->save()) {

            return response()->json(['data' => $job, 'message' => 'You have updated job.']);
        } else {
            return response()->json(['message' => 'Failed to delete job.'], 500);

        }
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


