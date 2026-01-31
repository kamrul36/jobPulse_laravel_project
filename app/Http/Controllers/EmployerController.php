<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
class EmployerController extends Controller
{

    public function employers()
    {
        $employers = Employer::paginate(20);
        return ResponseHelper::respond(
            'v1',
            'Get Jobs',
            'GET',
            200,
            $employers->items(),
            [
                'current_page' => $employers->currentPage(),
                'count' => $employers->perPage(),
                'total_count' => $employers->total(),
                'previous_page' => $employers->lastPage(),
                // 'has_more_pages' => $jobs->hasMorePages(),
            ]
        );
    }

    public function employerPublicProfile($emp_id, $count_views = "no")
    {

        $employer = Employer::findOrFail($emp_id);
        if ($count_views == "yes") {
            $count_views = $employer->value('views');

            $employer->views = $count_views + 1;

            $employer->save();
            if (!$employer) {
                return response()->json(['message' => 'Failed loading employer'], 500);
            }

        }

        return response()->json(['data' => $employer]);

    }

    public function info(Request $request)
    {
        $employer = $request->user();
        // the full object of the employer as containted in the able would
        // be available now
        return response()->json(['data' => $employer]);
    }

    public function jobs(Request $request)
    {
        // $employer_id = $request->user()->id;
        // $jobs = Job::where('employer_id', $employer_id)->with('employer')->where('status', '!=', 'deleted')->get();
        // return response()->json(['data' => $jobs]);

        try {
            $employerId = $request->user()->id;

            $jobs = Job::where('employer_id', $employerId)
                ->where('status', '!=', 'deleted')
                ->with('employer')
                ->get();

            return response()->json([
                'success' => true,
                'message' => $jobs->isEmpty() ? 'No jobs found' : 'Jobs fetched successfully',
                'data' => $jobs,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function jobsApplications(Request $request)
    {
        $logged_employer = $request->user();

        // $applications = Application::where('empl', $logged_jobseeker->id)->with('job.employer')->get();

        //we get all jobs of employer
        $jobs_applications = Job::where('employer_id', $logged_employer->id)
            ->where('status', 'active')->with(['applications.job', 'applications.jobseeker'])->get()->toArray();
        // $jobs_applications=  $logged_employer->jobs->first()->applications;

        $jobs_applications = array_column($jobs_applications, 'applications');

        $array_one_direction_application_only = $this->array_2d_to_1d($jobs_applications);

        return response()->json(['data' => $array_one_direction_application_only]);
    }

    public function array_2d_to_1d($input_array)
    {
        $output_array = array();

        for ($i = 0; $i < count($input_array); $i++) {
            for ($j = 0; $j < count($input_array[$i]); $j++) {
                $output_array[] = $input_array[$i][$j];
            }
        }

        return $output_array;
    }

    public function updateinfo(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'website' => 'required|string',
            'description' => 'required|string',
            'slogan' => 'required|string',
            'technologies_using' => 'required|string',
            'facebook' => 'required|string',
            'twitter' => 'required|string',
            'github' => 'required|string',
            'youtube' => 'required|string',
            'company_type' => 'required',
        ]);
        if (!isset($request->user()->id)) {
            return response()->json(['message' => 'Some Error Occured. Contact administrator']);
        }
        $logged_employer_id = $request->user()->id;
        $employer = Employer::findOrFail($logged_employer_id);
        $employer->name = $request->name;
        // $employer->email = $request->email;
        $employer->address = $request->address;
        $employer->phone = $request->phone;
        $employer->website = $request->website;
        $employer->description = $request->description;
        $employer->slogan = $request->slogan;

        $employer->facebook = $request->facebook;
        $employer->twitter = $request->twitter;
        $employer->youtube = $request->youtube;
        $employer->github = $request->github;

        $employer->technologies_using = $request->technologies_using;
        $employer->company_type = $request->company_type;

        if ($employer->save()) {
            return response()->json(['message' => 'Successful updated data', 'status' => 'success']);
        } else {
            return response()->json(['message' => 'Failed to update data'], 500);
        }
    }

    public function dashInfo(Request $request)
    {
        $logged_employer = $request->user();
        //getting jobseeker skills on array
        //application
        $application = 0;

        //we get all jobs of employer
        $jobs_applications = Job::where('employer_id', $logged_employer->id)
            ->where('status', 'active')->with(['applications.job', 'applications.jobseeker'])->get()->toArray();

        $jobs_applications = array_column($jobs_applications, 'applications');

        $array_one_direction_application_only = $this->array_2d_to_1d($jobs_applications);
        //couting applications
        $application = count($array_one_direction_application_only);

        //jobs
        $jobs = 0;
        $jobs = Job::where('employer_id', $logged_employer->id)->get()->count();

        //views
        $views = 0;
        $views = $logged_employer->views;
        //inbox

        $messages = $request->user()->notifications;
        if ($messages != null) {
            $messages = $messages->count();
        } else {
            $messages = 0;
        }

        return response()->json(
            [
                'jobs' => $jobs,
                'applications' => $application,
                'views' => $views,
                'messages' => $messages,

            ]
            ,
            200
        );

    }

    public function updateProfileImage(Request $request)
    {
        $validated = $request->validate([
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $baseurl = URL::to('/');
        $employer = Employer::findOrFail($request->user()->id);

        if ($request->hasFile('profile_image')) {

            $file_name = $request->file('profile_image')->getClientOriginalName();
            $file_ext = $request->file('profile_image')->getClientOriginalExtension();

            $name = md5(uniqid() . $file_name) . '.' . $file_ext;
            $request->profile_image->move(public_path() . '/employer/img/', $name);
            $employer->profile_image = $baseurl . '/employer/img/' . $name;
        }

        if ($employer->save()) {
            return response()->json(['data' => $employer->profile_image, 'message' => 'You have succesfully uploaded Image']);

        }
        return response()->json(['message' => "Some error occured while uploading Image."], 500);
    }
}
