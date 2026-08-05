<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Mock stats data for testing layout features
        $stats = [
            'total_forms' => 2,
            'total_submissions' => 18,
            'ai_generated' => 1,
            'conversion_rate' => 85,
        ];

        // Mock forms data
        $forms = [
            [
                'id' => 1,
                'title' => 'Internship Application Form',
                'fields_count' => 12,
                'submissions_count' => 15,
                'public_url' => url('/forms/internship-application'),
                'updated_at' => '2 hours ago',
            ],
            [
                'id' => 2,
                'title' => 'Customer Feedback Survey',
                'fields_count' => 5,
                'submissions_count' => 3,
                'public_url' => url('/forms/customer-feedback'),
                'updated_at' => '1 day ago',
            ],
        ];

        return view('dashboard', compact('stats', 'forms'));
    }
}
