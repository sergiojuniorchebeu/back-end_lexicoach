<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ApiDocumentationController extends Controller
{
    public function __invoke(): View
    {
        $documentation = [
            'title' => 'API Documentation',
            'version' => '1.0',
            'base_url' => url('/api'),
            'authentication_type' => 'Bearer Token with Laravel Sanctum and roles',
            'important_headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer {access_token}',
            ],
            'roles' => $this->roles(),
            'frontend_steps' => $this->frontendSteps(),
            'feature_guides' => $this->featureGuides(),
            'real_cases' => $this->realCases(),
            'modules' => $this->modules(),
            'flutter_examples' => $this->flutterExamples(),
        ];

        return view('docs.api', [
            'documentation' => $documentation,
            'markdownDocumentation' => $this->toMarkdown($documentation),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roles(): array
    {
        return [
            [
                'name' => 'visitor',
                'stored_in_database' => false,
                'description' => 'Guest user. Visitors can view public pages but cannot call protected API routes.',
                'permissions' => [
                    'View public pages.',
                    'Create an account.',
                    'Sign in.',
                ],
            ],
            [
                'name' => 'learner',
                'stored_in_database' => true,
                'description' => 'Signed-in learner. This is the default role after registration.',
                'permissions' => [
                    'Access their profile.',
                    'Practice reading, writing, smart abstract, and word splitting.',
                    'Generate a tutor association code.',
                    'View their progress.',
                ],
            ],
            [
                'name' => 'tutor',
                'stored_in_database' => true,
                'description' => 'Signed-in tutor. Tutors can link learners with a code and follow their progress.',
                'permissions' => [
                    'Access their profile.',
                    'Link learners with an association code.',
                    'View learner progress and attempts.',
                    'Detach a learner when needed.',
                ],
            ],
            [
                'name' => 'admin',
                'stored_in_database' => true,
                'description' => 'Signed-in administrator. Admins manage users, roles, global statistics, and exercise generation.',
                'permissions' => [
                    'Access the admin dashboard.',
                    'List and filter users.',
                    'Update user roles and statuses.',
                    'Generate exercises with AI.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function frontendSteps(): array
    {
        return [
            'Read data.user.role after login to choose the correct mobile area.',
            'Store the Sanctum token securely and send it with protected requests.',
            'Load learning modes from GET /api/learning-modes instead of hard-coding them.',
            'For Reading Practice, send only the transcript to the backend for evaluation.',
            'For Smart Abstract, upload or paste document text and send document_text to the backend.',
            'Never call Gemini directly from Flutter. Laravel keeps the API key.',
            'Tutor linking uses a learner-generated code and POST /api/tutor/learners/link.',
            'The admin web app starts at /admin/login and redirects to /admin/dashboard after login.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function featureGuides(): array
    {
        return [
            [
                'title' => 'Scenario 1 - Registration',
                'goal' => 'Create a learner or tutor account from the mobile app.',
                'user_story' => 'The user enters their name, email, password, role, and optional tutor association code.',
                'frontend_tasks' => [
                    'Show learner and tutor registration tabs.',
                    'Require the association code only for tutor registration.',
                    'Save the returned token after success.',
                ],
                'api_flow' => [
                    'POST /api/auth/register',
                    'Read data.user and data.token.access_token.',
                ],
                'display_rules' => [
                    'Show validation errors under the matching fields.',
                    'Open the learner or tutor area based on data.user.role.',
                ],
            ],
            [
                'title' => 'Scenario 2 - Login',
                'goal' => 'Authenticate an existing user and open the correct area.',
                'user_story' => 'The user enters email and password, then signs in.',
                'frontend_tasks' => [
                    'Call POST /api/auth/login.',
                    'Save the token securely.',
                    'Route learners to the learner home and tutors to the tutor home.',
                ],
                'api_flow' => [
                    'POST /api/auth/login',
                    'GET /api/auth/me when the app restarts.',
                ],
                'display_rules' => [
                    'Show a clear message for invalid credentials.',
                    'Do not open the app until the token is saved.',
                ],
            ],
            [
                'title' => 'Scenario 7 - Reading Evaluation',
                'goal' => 'Compare the learner transcript with the official reading text.',
                'user_story' => 'The learner listens to the sentence, reads aloud, and receives a score.',
                'frontend_tasks' => [
                    'Load exercises with GET /api/learning-modes/reading/exercises.',
                    'Capture the learner voice and display the transcript.',
                    'Send the transcript to POST /api/reading-exercises/{readingExercise}/evaluate.',
                ],
                'api_flow' => [
                    'GET /api/reading-exercises/{readingExercise}',
                    'POST /api/reading-exercises/{readingExercise}/evaluate',
                    'GET /api/me/reading-progress',
                ],
                'display_rules' => [
                    'Highlight correct, missing, incorrect, and extra words.',
                    'Show feedback.title and feedback.message.',
                ],
            ],
            [
                'title' => 'Scenario 11 - Tutor Reviews Learner Progress',
                'goal' => 'Let a tutor view progress and attempts for a linked learner.',
                'user_story' => 'The tutor opens a learner detail page and reviews reading, writing, and smart abstract history.',
                'frontend_tasks' => [
                    'Call GET /api/tutor/learners/{learner}/progress.',
                    'Offer a module filter for reading, writing, and smart abstract.',
                    'Load history with the matching attempts route.',
                ],
                'api_flow' => [
                    'GET /api/tutor/learners/{learner}/progress',
                    'GET /api/tutor/learners/{learner}/reading-attempts',
                    'GET /api/tutor/learners/{learner}/writing-attempts',
                    'GET /api/tutor/learners/{learner}/smart-abstract-attempts',
                ],
                'display_rules' => [
                    'Show an empty state when there are no attempts.',
                    'Never show learners that are not linked to the tutor.',
                ],
            ],
            [
                'title' => 'Scenario 14 - Admin Uses Separate Login and Dashboard',
                'goal' => 'Give the administrator a dedicated web area.',
                'user_story' => 'The admin signs in at /admin/login and lands on /admin/dashboard.',
                'frontend_tasks' => [
                    'Store the admin token in localStorage.',
                    'Load dashboard metrics with GET /api/admin/dashboard.',
                    'Load and filter users with GET /api/admin/users.',
                    'Update roles and statuses from the user cards.',
                ],
                'api_flow' => [
                    'POST /api/auth/login',
                    'GET /api/auth/me',
                    'GET /api/admin/dashboard',
                    'GET /api/admin/users',
                    'PATCH /api/admin/users/{user}/role',
                ],
                'display_rules' => [
                    'Redirect to /admin/login when the token is missing or invalid.',
                    'Block self-demotion and self-suspension.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function realCases(): array
    {
        return [
            [
                'title' => 'Learner Starts Reading Practice',
                'actor' => 'learner',
                'situation' => 'The learner chooses Reading, listens to the sentence, reads aloud, and receives feedback.',
                'frontend_actions' => [
                    'Load reading exercises.',
                    'Display the official text.',
                    'Send the transcript for evaluation.',
                    'Show score and word-level feedback.',
                ],
                'routes' => [
                    'GET /api/learning-modes/reading/exercises',
                    'POST /api/reading-exercises/{readingExercise}/evaluate',
                ],
                'backend_guarantees' => [
                    'The official text stays on the server.',
                    'Each attempt is saved.',
                    'Progress updates automatically.',
                ],
            ],
            [
                'title' => 'Tutor Links a Learner',
                'actor' => 'tutor',
                'situation' => 'The learner generates a code and gives it to the tutor.',
                'frontend_actions' => [
                    'Show the learner code in the learner app.',
                    'Let the tutor submit the code.',
                    'Refresh the tutor learners list after success.',
                ],
                'routes' => [
                    'POST /api/me/association-code',
                    'POST /api/tutor/learners/link',
                    'GET /api/tutor/learners',
                ],
                'backend_guarantees' => [
                    'Codes expire after 24 hours.',
                    'Used or canceled codes cannot be reused.',
                    'The tutor/learner link is stored server-side.',
                ],
            ],
            [
                'title' => 'Admin Monitors the Platform',
                'actor' => 'admin',
                'situation' => 'The admin opens the web dashboard to manage users and global learning activity.',
                'frontend_actions' => [
                    'Verify the admin role before loading the dashboard.',
                    'Show user and learning metrics.',
                    'Filter users and update statuses.',
                ],
                'routes' => [
                    'GET /api/admin/dashboard',
                    'GET /api/admin/users',
                    'PATCH /api/admin/users/{user}/status',
                ],
                'backend_guarantees' => [
                    'Admin routes require the admin role.',
                    'Admins cannot lock their own account.',
                    'Metrics include reading, writing, and smart abstract.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            $this->module('Authentication', 'authentication', 'Account registration, login, profile, and logout.', [
                $this->endpoint('Register', 'POST', '/api/auth/register', false, 'Creates a user account and returns a Sanctum token.', [
                    'full_name' => 'Marie Dupont',
                    'email' => 'marie@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'device_name' => 'flutter-app',
                    'role' => 'learner',
                ], [
                    $this->response(201, 'Success', [
                        'success' => true,
                        'message' => 'Registration successful.',
                        'data' => [
                            'user' => ['id' => 1, 'full_name' => 'Marie Dupont', 'role' => 'learner'],
                            'token' => ['type' => 'Bearer', 'access_token' => '1|sampleSanctumToken'],
                        ],
                    ]),
                ]),
                $this->endpoint('Login', 'POST', '/api/auth/login', false, 'Authenticates a user and returns a Sanctum token.', [
                    'email' => 'marie@example.com',
                    'password' => 'password123',
                    'device_name' => 'flutter-app',
                ]),
                $this->endpoint('Current user', 'GET', '/api/auth/me', true, 'Returns the signed-in user profile.'),
                $this->endpoint('Logout', 'POST', '/api/auth/logout', true, 'Revokes the current token.'),
            ]),
            $this->module('Reading Exercises', 'reading-exercises', 'Reading practice, evaluation, attempts, and progress.', [
                $this->endpoint('List reading exercises', 'GET', '/api/reading-exercises', true, 'Returns active reading exercises.'),
                $this->endpoint('Show reading exercise', 'GET', '/api/reading-exercises/{readingExercise}', true, 'Returns one reading exercise.'),
                $this->endpoint('Evaluate reading', 'POST', '/api/reading-exercises/{readingExercise}/evaluate', true, 'Evaluates a learner transcript and saves the attempt.', [
                    'transcript' => 'The little boy is playing in the garden.',
                ]),
                $this->endpoint('Reading attempts', 'GET', '/api/me/reading-attempts', true, 'Returns learner reading attempts.'),
                $this->endpoint('Reading progress', 'GET', '/api/me/reading-progress', true, 'Returns learner reading progress.'),
            ]),
            $this->module('Learning Modes', 'learning-modes', 'Dynamic learning mode list and mode exercises.', [
                $this->endpoint('List modes', 'GET', '/api/learning-modes', true, 'Returns active learning modes.'),
                $this->endpoint('Mode exercises', 'GET', '/api/learning-modes/{slug}/exercises', true, 'Returns exercises for one mode.'),
                $this->endpoint('Reading mode exercises', 'GET', '/api/learning-modes/reading/exercises', true, 'Returns reading mode exercises.'),
                $this->endpoint('Global learner progress', 'GET', '/api/me/progress', true, 'Returns progress grouped by learning mode.'),
            ]),
            $this->module('Writing and Smart Abstract', 'ai-exercises', 'AI-assisted writing correction and document summaries.', [
                $this->endpoint('Writing exercises', 'GET', '/api/writing-exercises', true, 'Returns writing exercises.'),
                $this->endpoint('Evaluate writing', 'POST', '/api/writing-exercises/{writingExercise}/evaluate', true, 'Evaluates the learner answer with AI.', [
                    'answer' => 'This is my answer.',
                ]),
                $this->endpoint('Smart abstract exercises', 'GET', '/api/smart-abstract-exercises', true, 'Returns smart abstract exercises.'),
                $this->endpoint('Evaluate smart abstract', 'POST', '/api/smart-abstract-exercises/{smartAbstractExercise}/evaluate', true, 'Summarizes document_text with AI.', [
                    'document_text' => 'Document text to summarize.',
                ]),
            ]),
            $this->module('Tutor', 'tutor', 'Tutor/learner association and tutor progress views.', [
                $this->endpoint('Current learner code', 'GET', '/api/me/association-code', true, 'Returns the learner active association code.'),
                $this->endpoint('Generate learner code', 'POST', '/api/me/association-code', true, 'Generates a learner association code.'),
                $this->endpoint('Regenerate learner code', 'POST', '/api/me/association-code/regenerate', true, 'Cancels the active code and creates a new one.'),
                $this->endpoint('Cancel learner code', 'DELETE', '/api/me/association-code', true, 'Cancels the active code.'),
                $this->endpoint('Tutor dashboard', 'GET', '/api/tutor/dashboard', true, 'Returns tutor summary metrics.'),
                $this->endpoint('Tutor learners', 'GET', '/api/tutor/learners', true, 'Returns learners linked to the tutor.'),
                $this->endpoint('Link learner', 'POST', '/api/tutor/learners/link', true, 'Links a learner with an association code.', [
                    'code' => 'LC-123456',
                ]),
                $this->endpoint('Learner progress', 'GET', '/api/tutor/learners/{learner}/progress', true, 'Returns one linked learner progress.'),
                $this->endpoint('Detach learner', 'DELETE', '/api/tutor/learners/{learner}', true, 'Removes the tutor/learner link.'),
            ]),
            $this->module('Admin', 'admin', 'Admin dashboard, users, roles, statuses, and exercise generation.', [
                $this->endpoint('Admin dashboard', 'GET', '/api/admin/dashboard', true, 'Returns global admin metrics.'),
                $this->endpoint('Admin users', 'GET', '/api/admin/users', true, 'Returns users with optional role and search filters.'),
                $this->endpoint('Show admin user', 'GET', '/api/admin/users/{user}', true, 'Returns one user detail.'),
                $this->endpoint('Update user role', 'PATCH', '/api/admin/users/{user}/role', true, 'Updates a user role.', [
                    'role' => 'tutor',
                ]),
                $this->endpoint('Update user status', 'PATCH', '/api/admin/users/{user}/status', true, 'Updates a user status.', [
                    'status' => 'suspended',
                ]),
            ]),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function flutterExamples(): array
    {
        return [
            [
                'title' => 'AuthApiService',
                'language' => 'dart',
                'code' => <<<'CODE'
const String baseUrl = 'https://lexicoach.mrsergio.dev/api';

class AuthApiService {
  Future<void> login(String email, String password) async {
    // POST $baseUrl/auth/login
  }
}
CODE,
            ],
            [
                'title' => 'Reading evaluation',
                'language' => 'dart',
                'code' => <<<'CODE'
final response = await http.post(
  Uri.parse('$baseUrl/reading-exercises/$exerciseId/evaluate'),
  headers: {'Authorization': 'Bearer $token'},
  body: jsonEncode({'transcript': transcript}),
);
CODE,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $endpoints
     * @return array<string, mixed>
     */
    private function module(string $name, string $slug, string $description, array $endpoints): array
    {
        return compact('name', 'slug', 'description', 'endpoints');
    }

    /**
     * @param  array<string, mixed>|null  $requestBody
     * @param  array<int, array<string, mixed>>|null  $responses
     * @return array<string, mixed>
     */
    private function endpoint(
        string $name,
        string $method,
        string $path,
        bool $protected,
        string $description,
        ?array $requestBody = null,
        ?array $responses = null,
    ): array {
        return [
            'name' => $name,
            'method' => $method,
            'path' => $path,
            'protected' => $protected,
            'description' => $description,
            'headers' => $protected
                ? ['Accept' => 'application/json', 'Authorization' => 'Bearer {access_token}']
                : ['Accept' => 'application/json'],
            'request_body' => $requestBody,
            'responses' => $responses ?? [
                $this->response(200, 'Success', [
                    'success' => true,
                    'message' => 'Request successful.',
                    'data' => [],
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function response(int $status, string $title, array $body): array
    {
        return compact('status', 'title', 'body');
    }

    /**
     * @param  array<string, mixed>  $documentation
     */
    private function toMarkdown(array $documentation): string
    {
        $lines = [
            '# '.$documentation['title'],
            '',
            '- Version: '.$documentation['version'],
            '- Base URL: `'.$documentation['base_url'].'`',
            '- Authentication: '.$documentation['authentication_type'],
            '',
            '## Important Headers',
            '',
            $this->codeBlock('json', $documentation['important_headers']),
            '',
            '## Frontend Steps',
            '',
        ];

        foreach ($documentation['frontend_steps'] as $index => $step) {
            $lines[] = ($index + 1).'. '.$step;
        }

        $lines[] = '';
        $lines[] = '## User Roles';
        $lines[] = '';
        $lines[] = 'The backend manages learner, tutor, and admin roles.';
        $lines[] = '';

        foreach ($documentation['roles'] as $role) {
            $lines[] = '### '.$role['name'];
            $lines[] = '';
            $lines[] = $role['description'];
            $lines[] = '';
            $lines[] = '- Stored in database: '.($role['stored_in_database'] ? 'yes' : 'no');
            $lines[] = '';
            $lines[] = 'Main permissions:';
            foreach ($role['permissions'] as $permission) {
                $lines[] = '- '.$permission;
            }
            $lines[] = '';
        }

        $lines[] = '## Scenarios and Frontend Role';
        $lines[] = '';

        foreach ($documentation['feature_guides'] as $guide) {
            $lines[] = '### '.$guide['title'];
            $lines[] = '';
            $lines[] = '**Goal:** '.$guide['goal'];
            $lines[] = '';
            $lines[] = '**User scenario:** '.$guide['user_story'];
            $lines[] = '';
            $lines[] = '#### Frontend tasks';
            foreach ($guide['frontend_tasks'] as $task) {
                $lines[] = '- '.$task;
            }
            $lines[] = '';
            $lines[] = '#### API flow';
            foreach ($guide['api_flow'] as $step) {
                $lines[] = '- '.$step;
            }
            $lines[] = '';
            $lines[] = '#### Display rules';
            foreach ($guide['display_rules'] as $rule) {
                $lines[] = '- '.$rule;
            }
            $lines[] = '';
        }

        $lines[] = '## Important Real Cases';
        $lines[] = '';
        foreach ($documentation['real_cases'] as $case) {
            $lines[] = '### '.$case['title'];
            $lines[] = '';
            $lines[] = '- Actor: `'.$case['actor'].'`';
            $lines[] = '- Situation: '.$case['situation'];
            $lines[] = '';
        }

        foreach ($documentation['modules'] as $module) {
            $lines[] = '## '.$module['name'];
            $lines[] = '';
            $lines[] = $module['description'];
            $lines[] = '';

            foreach ($module['endpoints'] as $endpoint) {
                $lines[] = '### '.$endpoint['name'];
                $lines[] = '';
                $lines[] = '- Method: `'.$endpoint['method'].'`';
                $lines[] = '- Path: `'.$endpoint['path'].'`';
                $lines[] = '- Protected: '.($endpoint['protected'] ? 'yes, token required' : 'no, public route');
                $lines[] = '';
                $lines[] = $endpoint['description'];
                $lines[] = '';
                $lines[] = '#### Headers';
                $lines[] = $this->codeBlock('json', $endpoint['headers']);
                $lines[] = '';
                $lines[] = '#### Request Body';
                $lines[] = $endpoint['request_body'] === null
                    ? 'No body'
                    : $this->codeBlock('json', $endpoint['request_body']);
                $lines[] = '';

                foreach ($endpoint['responses'] as $response) {
                    $lines[] = '#### Response '.$response['status'].' - '.$response['title'];
                    $lines[] = $this->codeBlock('json', $response['body']);
                    $lines[] = '';
                }
            }
        }

        $lines[] = '## Flutter Examples';
        $lines[] = '';
        foreach ($documentation['flutter_examples'] as $example) {
            $lines[] = '### '.$example['title'];
            $lines[] = $this->codeBlock($example['language'], $example['code']);
            $lines[] = '';
        }

        return trim(implode("\n", $lines))."\n";
    }

    /**
     * @param  array<string, mixed>|string|null  $content
     */
    private function codeBlock(string $language, mixed $content): string
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return "```{$language}\n{$content}\n```";
    }
}
