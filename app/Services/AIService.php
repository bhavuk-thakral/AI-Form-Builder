<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate form JSON schema from a user prompt.
     */
    public function generateFormSchema(string $prompt, int $retryCount = 0): array
    {
        $startTime = microtime(true);

        // If no API key is provided, trigger a high-quality mock generator for testing
        if (empty($this->apiKey) || $this->apiKey === 'placeholder') {
            return $this->getMockFallbackResponse($prompt, $startTime);
        }

        $systemPrompt = "You are an AI Form Builder assistant. Your job is to return a complete, valid form structure based on the user's prompt. 
You must output ONLY a valid JSON object matching this structure:
{
  \"title\": \"A descriptive title of the form\",
  \"description\": \"A helpful description of what this form collects\",
  \"fields\": [
    {
      \"key\": \"unique_snake_case_key\",
      \"type\": \"text|textarea|number|email|phone|date|dropdown|radio|checkbox|file|rating\",
      \"label\": \"Human readable field label\",
      \"placeholder\": \"Optional placeholder text (empty if section or rating)\",
      \"help_text\": \"Optional descriptive help text\",
      \"required\": true|false,
      \"default_value\": \"Optional default value\",
      \"validations\": [\"Optional list of Laravel validation rules e.g., min:3, max:50, mimes:pdf\"],
      \"options\": [\"Optional options list for dropdown, radio, or checkbox. Empty for others\"]
    }
  ]
}
Ensure all keys are unique. Do not wrap JSON in markdown block. Return raw JSON text only.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Generate form for: " . $prompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
            ]);

            $latency = round((microtime(true) - $startTime) * 1000); // ms

            if (!$response->successful()) {
                throw new \Exception("OpenAI API returned status " . $response->status() . ": " . $response->body());
            }

            $result = $response->json();
            $rawJson = $result['choices'][0]['message']['content'] ?? '';

            // Clean json blocks if AI wrapped it in ```json ... ```
            $rawJson = $this->cleanJsonString($rawJson);

            // Parse and validate JSON
            $schema = json_decode($rawJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Repair JSON
                $repairedJson = $this->repairMalformedJson($rawJson);
                $schema = json_decode($repairedJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("JSON parsing failed: " . json_last_error_msg());
                }
            }

            // Validate schema fields
            $this->validateSchemaFormat($schema);

            $usage = $result['usage'] ?? [];
            return [
                'success' => true,
                'schema' => $schema,
                'model' => $this->model,
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
                'latency' => $latency,
            ];

        } catch (\Exception $e) {
            Log::warning("AI Generation attempt failed: " . $e->getMessage());

            if ($retryCount < 2) {
                Log::info("Retrying AI Form Generation... Attempt: " . ($retryCount + 1));
                return $this->generateFormSchema($prompt, $retryCount + 1);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'latency' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Parse and clean JSON string markdown wrappers.
     */
    protected function cleanJsonString(string $json): string
    {
        $json = trim($json);
        if (str_starts_with($json, '```json')) {
            $json = substr($json, 7);
        }
        if (str_starts_with($json, '```')) {
            $json = substr($json, 3);
        }
        if (str_ends_with($json, '```')) {
            $json = substr($json, 0, -3);
        }
        return trim($json);
    }

    /**
     * Clean and close broken braces in malformed JSON syntax.
     */
    protected function repairMalformedJson(string $json): string
    {
        $json = trim($json);
        
        // Remove trailing commas before closing braces
        $json = preg_replace('/,\s*([\]}])/m', '$1', $json);
        
        // Ensure bracket counters match
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        if ($openBraces > $closeBraces) {
            $json .= str_repeat('}', $openBraces - $closeBraces);
        }
        
        $openBracks = substr_count($json, '[');
        $closeBracks = substr_count($json, ']');
        if ($openBracks > $closeBracks) {
            $json .= str_repeat(']', $openBracks - $closeBracks);
        }

        return $json;
    }

    /**
     * Verify schema layout matching form definitions.
     */
    protected function validateSchemaFormat(array &$schema)
    {
        if (empty($schema['title'])) {
            $schema['title'] = 'AI Generated Form';
        }
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            $schema['fields'] = [];
        }

        // Validate each field has minimum properties
        foreach ($schema['fields'] as $index => &$field) {
            if (empty($field['type'])) $field['type'] = 'text';
            if (empty($field['label'])) $field['label'] = 'Field ' . ($index + 1);
            if (empty($field['key'])) $field['key'] = Str::snake($field['label']);
            if (!isset($field['required'])) $field['required'] = false;
            if (!isset($field['options'])) $field['options'] = [];
            if (!isset($field['validations'])) $field['validations'] = [];
        }
    }

    /**
     * Provide mock fallback responses for demo or when API key is unset.
     */
    protected function getMockFallbackResponse(string $prompt, float $startTime): array
    {
        // Simple artificial delay (500ms) to simulate background queue job progress
        usleep(500000);
        $latency = round((microtime(true) - $startTime) * 1000);

        $promptLower = strtolower($prompt);

        if (str_contains($promptLower, 'internship') || str_contains($promptLower, 'job')) {
            $schema = [
                'title' => 'Internship Application Form',
                'description' => 'AI Generated form for collecting student engineering application details.',
                'fields' => [
                    [
                        'key' => 'full_name',
                        'type' => 'text',
                        'label' => 'Full Name',
                        'placeholder' => 'Jane Doe',
                        'help_text' => 'Enter your official first and last name.',
                        'required' => true,
                        'default_value' => '',
                        'validations' => ['string', 'max:255']
                    ],
                    [
                        'key' => 'email',
                        'type' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'jane@university.edu',
                        'help_text' => 'University email address preferred.',
                        'required' => true,
                        'default_value' => '',
                        'validations' => ['email']
                    ],
                    [
                        'key' => 'graduation_date',
                        'type' => 'date',
                        'label' => 'Expected Graduation Date',
                        'placeholder' => '',
                        'help_text' => 'Provide your graduation target month and year.',
                        'required' => true,
                        'default_value' => '',
                        'validations' => ['date']
                    ],
                    [
                        'key' => 'gpa',
                        'type' => 'number',
                        'label' => 'Cumulative GPA',
                        'placeholder' => '3.50',
                        'help_text' => 'On a 4.00 scale.',
                        'required' => false,
                        'default_value' => '',
                        'validations' => ['numeric', 'min:0', 'max:4']
                    ],
                    [
                        'key' => 'programming_languages',
                        'type' => 'checkbox',
                        'label' => 'Programming Languages Known',
                        'placeholder' => '',
                        'help_text' => 'Select all that apply.',
                        'required' => false,
                        'options' => ['Python', 'PHP', 'JavaScript', 'Go/C++'],
                        'validations' => []
                    ],
                    [
                        'key' => 'resume_cv',
                        'type' => 'file',
                        'label' => 'Resume Upload',
                        'placeholder' => '',
                        'help_text' => 'Submit your CV in PDF format (max 2MB).',
                        'required' => true,
                        'validations' => ['file', 'mimes:pdf', 'max:2048']
                    ]
                ]
            ];
        } elseif (str_contains($promptLower, 'feedback') || str_contains($promptLower, 'satisfaction')) {
            $schema = [
                'title' => 'Customer Feedback Survey',
                'description' => 'Tell us about your experience using our application.',
                'fields' => [
                    [
                        'key' => 'overall_rating',
                        'type' => 'rating',
                        'label' => 'Overall Rating',
                        'placeholder' => '',
                        'help_text' => 'Rate us from 1 to 5 stars.',
                        'required' => true,
                        'default_value' => '5',
                        'validations' => ['integer', 'min:1', 'max:5']
                    ],
                    [
                        'key' => 'recommend_friend',
                        'type' => 'radio',
                        'label' => 'Would you recommend us to a colleague?',
                        'placeholder' => '',
                        'help_text' => '',
                        'required' => true,
                        'options' => ['Yes, absolutely', 'Maybe', 'No'],
                        'validations' => []
                    ],
                    [
                        'key' => 'comments',
                        'type' => 'textarea',
                        'label' => 'Comments & Improvements',
                        'placeholder' => 'How can we make our product better for you?',
                        'required' => false,
                        'default_value' => '',
                        'validations' => []
                    ]
                ]
            ];
        } else {
            // Default Generic Form Fallback
            $schema = [
                'title' => 'AI Contact Setup Form',
                'description' => 'Generated details from prompt: "' . $prompt . '"',
                'fields' => [
                    [
                        'key' => 'contact_name',
                        'type' => 'text',
                        'label' => 'Full Name',
                        'placeholder' => 'John Doe',
                        'required' => true,
                        'validations' => ['string', 'max:255']
                    ],
                    [
                        'key' => 'contact_email',
                        'type' => 'email',
                        'label' => 'Email',
                        'placeholder' => 'john@example.com',
                        'required' => true,
                        'validations' => ['email']
                    ],
                    [
                        'key' => 'message_body',
                        'type' => 'textarea',
                        'label' => 'Message Text',
                        'placeholder' => 'Write your query...',
                        'required' => true,
                        'validations' => []
                    ]
                ]
            ];
        }

        return [
            'success' => true,
            'schema' => $schema,
            'model' => 'gpt-4o-mini-mocked',
            'prompt_tokens' => 110,
            'completion_tokens' => 245,
            'total_tokens' => 355,
            'latency' => $latency,
        ];
    }
}
