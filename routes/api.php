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



Route::group(['prefix' => 'v1'], function () {


    // Protected routes (require JWT token)
    Route::middleware(['jwt.auth'])->group(function () {
        // Auth routes

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/refresh', [AuthController::class, 'refresh']);

        // User routes

        Route::get('/current-user', [AuthController::class, 'user']);

        Route::put('/update-profile', [UserController::class, 'updateProfile']);

        Route::post('/user-deactivate', [UserController::class, 'deactivate']);

        // Role management routes (Admin & Super Admin)

        Route::get('/admin-role-management', [RoleController::class, 'index']);

        Route::post('/admin-role-management', [RoleController::class, 'create']);

        Route::put('/admin-role-management/{userId}', [RoleController::class, 'updateUserRole']);

        Route::get('/admin-get-users', [RoleController::class, 'getUsers']);

    });

    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/register', [AuthController::class, 'register']);

    Route::post('/request-otp', [AuthController::class, 'requestOTP']);

    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

    Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);

    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/reactivate', [UserController::class, 'reactivate']);



    Route::get('/', [HomeController::class, 'index']);

    Route::get('jobs', [JobController::class, 'jobs']);

    Route::get('employers', [EmployerController::class, 'employers']);

    Route::get('categories', [CategoryController::class, 'index']);

    Route::get('job/{id}/{count_views?}', [JobController::class, 'show']);

    Route::get('featured-jobs', [JobController::class, 'featuredJobs']);

    // Route::get('recent-jobs', [JobController::class, 'recentJobs']);


    Route::get('jobseekers', [JobseekerController::class, 'jobseekers']);



    Route::get('page/{slug}', [PageController::class, 'show']);

    Route::get('query-jobs', [JobController::class, 'queryJobs']);


    Route::middleware(['jwt.auth'])->group(function () {


        Route::group(['prefix' => 'admin'], function () {

            //only authenticated admin only
            //admin routes

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

        });
    });


    Route::group(['prefix' => 'jobseeker'], function () {

        //unauthenticated routes for jobseeker here
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

    });

    Route::group(['prefix' => 'employer'], function () {

        //unauthenticated routes for employer here

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

        Route::get('jobs-applications', [EmployerController::class, 'jobsApplications']);

        Route::post('accept-application', [ApplicationController::class, 'acceptApplication']);

        Route::post('reject-application', [ApplicationController::class, 'rejectApplication']);

        Route::post('interview-call', [ApplicationController::class, 'interviewCall']);

    });

});
