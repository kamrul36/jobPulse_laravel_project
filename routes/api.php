<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobseekerController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Protected routes (require JWT token)
Route::middleware(['jwt.auth'])->group(function () {
    // Auth routes
    Route::prefix('v1')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // User routes
    Route::prefix('v1')->group(function () {
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::post('/deactivate', [UserController::class, 'deactivate']);
    });

    // Role management routes (Admin & Super Admin)
    Route::prefix('v1')->group(function () {
        Route::get('/', action: [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'create']);
        Route::get('/users', [RoleController::class, 'getUsers']);
        Route::put('/users/{userId}', [RoleController::class, 'updateUserRole']);
    });
});



Route::group(['prefix' => 'v1'], function () {


    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/request-otp', [AuthController::class, 'requestOTP']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/reactivate', [UserController::class, 'reactivate']);


    Route::get('/', [HomeController::class, 'index']);

    Route::get('jobs', [JobController::class, 'jobs']);

    Route::get('employers', [EmployerController::class, 'employers']);

    Route::get('categories', [CategoryController::class, 'index']);

    Route::get('job/{id}/{count_views?}', [JobController::class, 'sh    ow']);

    Route::get('featured-jobs', [JobController::class, 'featuredJobs']);

    // Route::get('recent-jobs', [JobController::class, 'recentJobs']);


    Route::get('jobseekers', [JobseekerController::class, 'jobseekers']);



    Route::get('page/{slug}', [PageController::class, 'show']);

    Route::get('query-jobs', [JobController::class, 'queryJobs']);


    Route::group(['prefix' => 'admin'], function () {

        //admin routes
        Route::post('login', [AdminController::class, 'login']);

        // Route::group(['middleware' => ['auth:admin', 'scope:admin', 'cors']], function () {
        //only authenticated admin only

        // Route::post('logout', [AuthController::class, 'logout']);

        Route::get('dash-info', [AdminController::class, 'dashInfo']);

        Route::get('applications', [AdminController::class, 'applications']);

        Route::post('cancel-application', [AdminController::class, 'cancelApplication']);


        Route::post('create-job', [AdminController::class, 'createJob']);

        Route::post('update-job', [AdminController::class, 'updateJob']);

        Route::post('close-job', [AdminController::class, 'closeJob']);

        Route::post('active-job', [AdminController::class, 'activeJob']);

        Route::post('delete-job', [AdminController::class, 'destroyJob']);

        Route::get('jobseeker/{id}', [AdminController::class, 'showJobseeker']);

        Route::post('create-jobseeker', [AdminController::class, 'createJobseeker']);

        Route::post('update-jobseeker', [AdminController::class, 'updateJobseeker']);

        Route::post('delete-jobseeker', [AdminController::class, 'destroyJobseeker']);

        Route::get('employer/{id}', [AdminController::class, 'showEmployer']);

        Route::post('create-employer', [AdminController::class, 'createEmployer']);

        Route::post('update-employer', [AdminController::class, 'updateEmployer']);

        Route::post('delete-employer', [AdminController::class, 'destroyEmployer']);

        //only authenticated admin only
        // });

    });



    Route::group(['prefix' => 'jobseeker'], function () {

        // Route::post('register', [JobseekerController::class, 'register']);

        // Route::post('login', [JobseekerController::class, 'login']);

        //unauthenticated routes for jobseeker here

        // Route::group(['middleware' => ['auth:jobseeker', 'scope:jobseeker', 'cors']], function () {
        // authenticated jobseeker routes here
        Route::post('info', [JobseekerController::class, 'info']);

        Route::post('updateinfo', [JobseekerController::class, 'updateinfo']);

        Route::post('apply-job', [ApplicationController::class, 'applyJob']);

        Route::get('dash-info', [JobseekerController::class, 'dashInfo']);

        Route::get('resume', [JobseekerController::class, 'resume']);

        Route::post('update-resume', [JobseekerController::class, 'updateResume']);

        Route::post('update-profile-image', [JobseekerController::class, 'updateProfileImage']);

        Route::get('messages', [JobseekerController::class, 'allMessages']);

        Route::get('applied-jobs', [JobseekerController::class, 'appliedJobs']);

        Route::get('matched-jobs', [JobseekerController::class, 'matchedJob']);

        Route::post('cancel-application', [ApplicationController::class, 'cancelApplication']);

        // Route::post('logout', [AuthController::class, 'logout']);
        // });
    });

    Route::group(['prefix' => 'employer'], function () {

        // Route::post('register', [EmployerController::class, 'register']);

        // Route::post('login', [EmployerController::class, 'login']);

        //unauthenticated routes for employer here

        // Route::group(['middleware' => ['auth:employer', 'scope:employer']], function () {
        // authenticated Employer routes here
        // authenticated jobseeker routes here



        Route::post('info', [EmployerController::class, 'info']);

        Route::post('updateinfo', [EmployerController::class, 'updateinfo']);

        Route::get('jobs', [EmployerController::class, 'jobs']);

        Route::post('create-job', [JobController::class, 'create']);

        Route::post('update-job', [JobController::class, 'update']);

        Route::post('close-job', [JobController::class, 'closeJob']);

        Route::get('dash-info', [EmployerController::class, 'dashInfo']);

        Route::post('update-profile-image', [EmployerController::class, 'updateProfileImage']);

        Route::get('messages', [EmployerController::class, 'allMessages']);

        Route::post('active-job', [JobController::class, 'activeJob']);

        Route::post('delete-job', [JobController::class, 'destroyJob']);

        // Route::post('logout', [AuthController::class, 'logout']);

        Route::get('jobs-applications', [EmployerController::class, 'jobsApplications']);

        Route::post('accept-application', [ApplicationController::class, 'acceptApplication']);

        Route::post('reject-application', [ApplicationController::class, 'rejectApplication']);

        Route::post('interview-call', [ApplicationController::class, 'interviewCall']);

        // });
    });

});
