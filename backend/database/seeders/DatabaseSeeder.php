<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo users and published jobs for local development.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@aihr.test'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        $hr = User::query()->updateOrCreate(
            ['email' => 'hr@aihr.test'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Hr,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'candidate@aihr.test'],
            [
                'name' => 'Demo Candidate',
                'password' => Hash::make('password'),
                'role' => UserRole::Candidate,
                'email_verified_at' => now(),
            ],
        );

        $jobs = [
            [
                'title' => 'Senior Frontend Engineer',
                'department' => 'Engineering',
                'location' => 'Remote',
                'employment_type' => EmploymentType::FullTime,
                'experience_min' => 4,
                'experience_max' => 8,
                'salary_min' => 90000,
                'salary_max' => 130000,
                'description' => "We are hiring a Senior Frontend Engineer to build polished hiring workflows for candidates and recruiters.\n\nYou will own React interfaces, collaborate with backend engineers, and ship accessible UI for job browsing and applications.",
                'requirements' => "4+ years with React\nStrong TypeScript or modern JavaScript\nExperience with REST APIs and responsive UI\nComfortable reviewing PRs and mentoring juniors",
                'responsibilities' => "Ship candidate and recruiter UI features\nImprove performance and accessibility\nPartner with HR and product on hiring flows",
            ],
            [
                'title' => 'Backend Engineer (Laravel)',
                'department' => 'Engineering',
                'location' => 'Bengaluru',
                'employment_type' => EmploymentType::FullTime,
                'experience_min' => 3,
                'experience_max' => 6,
                'salary_min' => 80000,
                'salary_max' => 115000,
                'description' => "Join our platform team and build reliable APIs for jobs, applications, and AI-assisted screening.\n\nYou will work in Laravel, queues, and MySQL with a focus on clear domain boundaries.",
                'requirements' => "3+ years PHP / Laravel\nSolid MySQL and API design\nFamiliarity with queues and background jobs\nGood testing habits",
                'responsibilities' => "Design and maintain recruitment APIs\nKeep authorization and audit trails correct\nSupport resume processing pipelines",
            ],
            [
                'title' => 'People Operations Specialist',
                'department' => 'People',
                'location' => 'Hyderabad',
                'employment_type' => EmploymentType::FullTime,
                'experience_min' => 2,
                'experience_max' => 5,
                'salary_min' => 45000,
                'salary_max' => 70000,
                'description' => "Support end-to-end hiring operations: posting roles, coordinating interviews, and keeping candidates informed.",
                'requirements' => "2+ years in HR / recruiting ops\nStrong written communication\nComfortable with ATS or hiring tools\nOrganized and candidate-friendly",
                'responsibilities' => "Manage job postings with hiring managers\nTrack applications through screening stages\nSchedule interviews and follow up with candidates",
            ],
            [
                'title' => 'Product Designer',
                'department' => 'Product',
                'location' => 'Remote',
                'employment_type' => EmploymentType::Contract,
                'experience_min' => 3,
                'experience_max' => 7,
                'salary_min' => 70000,
                'salary_max' => 110000,
                'description' => "Design clear flows for job discovery, applying, and application tracking across desktop and mobile.",
                'requirements' => "Portfolio showing product / SaaS work\nFigma proficiency\nExperience with design systems\nAbility to validate designs with users",
                'responsibilities' => "Map candidate and HR journeys\nProduce high-fidelity UI and prototypes\nPartner with engineers during implementation",
            ],
            [
                'title' => 'Talent Acquisition Intern',
                'department' => 'People',
                'location' => 'Pune',
                'employment_type' => EmploymentType::Internship,
                'experience_min' => 0,
                'experience_max' => 1,
                'salary_min' => 15000,
                'salary_max' => 25000,
                'description' => "A hands-on internship supporting sourcing, screening coordination, and candidate communication.",
                'requirements' => "Interest in recruiting or HR\nClear written English\nWillingness to learn our hiring tools\nReliable and curious",
                'responsibilities' => "Help screen inbound applications\nUpdate candidate status notes for HR\nAssist with interview logistics",
            ],
            [
                'title' => 'Sales Development Representative',
                'department' => 'Sales',
                'location' => 'Mumbai',
                'employment_type' => EmploymentType::FullTime,
                'experience_min' => 1,
                'experience_max' => 3,
                'salary_min' => 40000,
                'salary_max' => 65000,
                'description' => "Own outbound prospecting for our AI HR platform and book qualified demos for account executives.",
                'requirements' => "1+ year SDR / BDR experience preferred\nComfortable with cold outreach\nCRM basics\nClear spoken English",
                'responsibilities' => "Build and work outbound sequences\nQualify inbound interest\nHand off booked meetings cleanly",
            ],
        ];

        foreach ($jobs as $jobData) {
            Job::query()->updateOrCreate(
                [
                    'created_by' => $hr->id,
                    'title' => $jobData['title'],
                ],
                [
                    ...$jobData,
                    'slug' => Job::uniqueSlug($jobData['title']),
                    'status' => JobStatus::Published,
                    'published_at' => now()->subDays(rand(1, 14)),
                    'closing_at' => now()->addDays(30),
                ],
            );
        }

        // Keep references so unused-variable linters stay quiet in some IDEs.
        unset($admin);
    }
}
