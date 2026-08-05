<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $userId = Auth::id();

        // Retrieve user forms
        $formsCollection = Form::where('user_id', $userId)->latest()->get();

        // Calculate statistics
        $totalForms = $formsCollection->count();
        
        $formIds = $formsCollection->pluck('id');
        $totalSubmissions = Submission::whereIn('form_id', $formIds)->count();
        
        $aiGenerated = ActivityLog::where('user_id', $userId)
            ->where('action', 'ai_generated')
            ->count();
            
        $totalViews = $formsCollection->sum('views_count');
        $conversionRate = $totalViews > 0 ? round(($totalSubmissions / $totalViews) * 100) : 0;

        $stats = [
            'total_forms' => $totalForms,
            'total_submissions' => $totalSubmissions,
            'ai_generated' => $aiGenerated,
            'conversion_rate' => $conversionRate,
        ];

        // Format forms list for view
        $forms = $formsCollection->map(function ($form) {
            return [
                'id' => $form->id,
                'title' => $form->title,
                'status' => $form->status,
                'fields_count' => is_array($form->schema) && isset($form->schema['fields']) 
                    ? count($form->schema['fields']) 
                    : 0,
                'submissions_count' => $form->submissions()->count(),
                'public_url' => route('forms.public.show', $form->share_token),
                'updated_at' => $form->updated_at->diffForHumans(),
                'share_token' => $form->share_token,
            ];
        });

        return view('dashboard', compact('stats', 'forms'));
    }
}
