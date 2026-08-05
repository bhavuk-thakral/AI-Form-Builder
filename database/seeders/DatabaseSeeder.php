<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\ActivityLog;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default User
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Internship Application Form
        $internshipSchema = [
            'title' => 'Internship Application Form',
            'description' => 'Apply for our summer engineering internship program.',
            'fields' => [
                [
                    'key' => 'full_name',
                    'type' => 'text',
                    'label' => 'Full Name',
                    'placeholder' => 'Jane Doe',
                    'help_text' => 'Enter your full name as it appears on your ID.',
                    'required' => true,
                    'default_value' => '',
                    'validations' => ['string', 'max:255']
                ],
                [
                    'key' => 'email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'placeholder' => 'jane@example.com',
                    'help_text' => 'We will send all application updates to this email.',
                    'required' => true,
                    'default_value' => '',
                    'validations' => ['email']
                ],
                [
                    'key' => 'skills',
                    'type' => 'textarea',
                    'label' => 'Core Skills',
                    'placeholder' => 'React, PHP, Laravel, Python, etc.',
                    'help_text' => 'Briefly list your technical skills.',
                    'required' => false,
                    'default_value' => '',
                    'validations' => ['string']
                ],
                [
                    'key' => 'resume_upload',
                    'type' => 'file',
                    'label' => 'Resume Upload',
                    'placeholder' => '',
                    'help_text' => 'Upload your resume in PDF format (Max 2MB).',
                    'required' => true,
                    'default_value' => '',
                    'validations' => ['file', 'mimes:pdf', 'max:2048']
                ]
            ]
        ];

        $form1 = Form::create([
            'user_id' => $user->id,
            'title' => 'Internship Application Form',
            'description' => 'Apply for our summer engineering internship program.',
            'status' => 'active',
            'schema' => $internshipSchema,
            'views_count' => 45,
            'share_token' => Str::random(32),
        ]);

        $version1 = FormVersion::create([
            'form_id' => $form1->id,
            'version_number' => 1,
            'schema' => $internshipSchema,
            'description' => 'Initial version',
            'created_by' => $user->id,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'form_id' => $form1->id,
            'action' => 'created',
            'description' => 'Form created manually.',
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'form_id' => $form1->id,
            'action' => 'version_published',
            'description' => 'Published Version 1.',
            'ip_address' => '127.0.0.1',
        ]);

        // Mock 3 Submissions for Internship Form
        $submission1 = Submission::create([
            'form_id' => $form1->id,
            'form_version_id' => $version1->id,
            'ip_address' => '192.168.1.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0.0.0',
            'duration_seconds' => 120,
        ]);

        SubmissionAnswer::create([
            'submission_id' => $submission1->id,
            'field_key' => 'full_name',
            'answer_value' => 'Alice Johnson',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission1->id,
            'field_key' => 'email',
            'answer_value' => 'alice@example.com',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission1->id,
            'field_key' => 'skills',
            'answer_value' => 'HTML, CSS, JavaScript, React',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission1->id,
            'field_key' => 'resume_upload',
            'answer_value' => 'resumes/alice_resume.pdf',
        ]);

        $submission2 = Submission::create([
            'form_id' => $form1->id,
            'form_version_id' => $version1->id,
            'ip_address' => '192.168.1.11',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/17.5',
            'duration_seconds' => 95,
        ]);

        SubmissionAnswer::create([
            'submission_id' => $submission2->id,
            'field_key' => 'full_name',
            'answer_value' => 'Bob Smith',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission2->id,
            'field_key' => 'email',
            'answer_value' => 'bob@example.com',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission2->id,
            'field_key' => 'skills',
            'answer_value' => 'PHP, Laravel, MySQL',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission2->id,
            'field_key' => 'resume_upload',
            'answer_value' => 'resumes/bob_resume.pdf',
        ]);

        // 3. Create Customer Feedback Survey
        $feedbackSchema = [
            'title' => 'Customer Feedback Survey',
            'description' => 'Let us know how we can improve our SaaS platform.',
            'fields' => [
                [
                    'key' => 'overall_rating',
                    'type' => 'rating',
                    'label' => 'Overall Rating',
                    'placeholder' => '',
                    'help_text' => 'Rate your experience from 1 to 5 stars.',
                    'required' => true,
                    'default_value' => '5',
                    'validations' => ['numeric', 'min:1', 'max:5']
                ],
                [
                    'key' => 'feedback_comments',
                    'type' => 'textarea',
                    'label' => 'Feedback & Comments',
                    'placeholder' => 'Tell us what you like or how we can improve...',
                    'help_text' => 'Your responses will be kept confidential.',
                    'required' => false,
                    'default_value' => '',
                    'validations' => ['string']
                ]
            ]
        ];

        $form2 = Form::create([
            'user_id' => $user->id,
            'title' => 'Customer Feedback Survey',
            'description' => 'Let us know how we can improve our SaaS platform.',
            'status' => 'active',
            'schema' => $feedbackSchema,
            'views_count' => 10,
            'share_token' => Str::random(32),
        ]);

        $version2 = FormVersion::create([
            'form_id' => $form2->id,
            'version_number' => 1,
            'schema' => $feedbackSchema,
            'description' => 'Initial version',
            'created_by' => $user->id,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'form_id' => $form2->id,
            'action' => 'created',
            'description' => 'Form created manually.',
            'ip_address' => '127.0.0.1',
        ]);

        // Mock 1 Submission for Feedback Survey
        $submission3 = Submission::create([
            'form_id' => $form2->id,
            'form_version_id' => $version2->id,
            'ip_address' => '203.0.113.5',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 Chrome/124.0.0.0',
            'duration_seconds' => 45,
        ]);

        SubmissionAnswer::create([
            'submission_id' => $submission3->id,
            'field_key' => 'overall_rating',
            'answer_value' => '4',
        ]);
        SubmissionAnswer::create([
            'submission_id' => $submission3->id,
            'field_key' => 'feedback_comments',
            'answer_value' => 'Really like the dashboard UI layout. Keep it up!',
        ]);
    }
}
