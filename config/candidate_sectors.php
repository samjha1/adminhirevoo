<?php

/**
 * Marketing-facing candidate segments mapped to job_roles.sector keys.
 * Keywords are matched against profile text, resume summaries, and job role titles.
 */
return [
    'technology' => [
        'label' => 'Technology & IT',
        'short' => 'Tech',
        'role_sectors' => ['technology'],
        'keywords' => [
            'software developer', 'software engineer', 'full stack', 'full-stack', 'fullstack',
            'backend developer', 'frontend developer', 'web developer', 'mobile developer',
            'data analyst', 'data scientist', 'data engineer', 'machine learning', 'ml engineer',
            'devops', 'cloud engineer', 'qa engineer', 'test engineer', 'automation engineer',
            'python', 'java', 'javascript', 'typescript', 'react', 'node.js', 'nodejs', 'angular',
            'flask', 'django', 'laravel', 'php developer', '.net', 'c++', 'golang', 'kotlin',
            'sql', 'computer science', 'information technology', 'it graduate', 'programmer',
            'coding', 'software', 'cybersecurity', 'network engineer', 'system administrator',
            'database administrator', 'dba', 'ui developer', 'ux designer', 'product engineer',
            'sre', 'platform engineer', 'android developer', 'ios developer', 'flutter',
        ],
    ],
    'finance' => [
        'label' => 'Banking & Finance',
        'short' => 'Bank',
        'role_sectors' => ['finance'],
        'keywords' => [
            'finance', 'banking', 'bank', 'accountant', 'chartered accountant', 'ca ',
            'financial analyst', 'investment', 'credit analyst', 'loan officer', 'treasury',
            'audit', 'taxation', 'gst', 'payroll', 'billing', 'collections', 'insurance',
            'wealth management', 'compliance officer', 'kyc', 'trade finance', 'equity research',
            'accounts executive', 'accounts payable', 'accounts receivable', 'fp&a',
        ],
    ],
    'hr_admin' => [
        'label' => 'HR & Recruitment',
        'short' => 'HR',
        'role_sectors' => ['hr_admin'],
        'keywords' => [
            'human resources', 'hr executive', 'hr manager', 'recruiter', 'recruitment',
            'talent acquisition', 'staffing', 'bench sales', 'it recruiter', 'technical recruiter',
            'hiring', 'onboarding', 'payroll executive', 'hr coordinator', 'hr business partner',
            'employee relations', 'compensation', 'training coordinator', 'office administrator',
            'executive assistant', 'receptionist', 'front office',
        ],
    ],
    'sales_marketing' => [
        'label' => 'Sales & Marketing',
        'short' => 'Sales',
        'role_sectors' => ['sales_marketing'],
        'keywords' => [
            'sales executive', 'business development', 'bdr', 'sdr', 'account manager',
            'key account', 'inside sales', 'field sales', 'telesales', 'pre sales',
            'digital marketing', 'seo', 'sem', 'social media marketing', 'content marketing',
            'brand manager', 'marketing manager', 'growth marketing', 'performance marketing',
            'copywriter', 'content writer', 'crm specialist', 'channel partner',
        ],
    ],
    'healthcare' => [
        'label' => 'Healthcare',
        'short' => 'Healthcare',
        'role_sectors' => ['healthcare'],
        'keywords' => [
            'nurse', 'nursing', 'pharmacist', 'medical', 'healthcare', 'hospital',
            'lab technician', 'radiology', 'physiotherapist', 'dental', 'clinical',
            'medical representative', 'patient care', 'icu', 'ot technician',
        ],
    ],
    'operations' => [
        'label' => 'Operations',
        'short' => 'Operations',
        'role_sectors' => ['operations'],
        'keywords' => [
            'operations executive', 'operations manager', 'supply chain', 'logistics',
            'warehouse', 'inventory', 'procurement', 'vendor management', 'dispatch',
            'import export', 'production planner', 'quality inspector', 'facility manager',
        ],
    ],
    'education' => [
        'label' => 'Education',
        'short' => 'Education',
        'role_sectors' => ['education'],
        'keywords' => [
            'teacher', 'teaching', 'professor', 'lecturer', 'tutor', 'academic',
            'curriculum', 'instructional designer', 'corporate trainer', 'edtech',
            'admission counselor', 'librarian', 'education',
        ],
    ],
    'retail' => [
        'label' => 'Retail & E-commerce',
        'short' => 'Retail',
        'role_sectors' => ['retail'],
        'keywords' => [
            'retail', 'store manager', 'cashier', 'merchandiser', 'e-commerce', 'ecommerce',
            'category manager', 'visual merchandising', 'customer experience',
        ],
    ],
    'manufacturing' => [
        'label' => 'Manufacturing',
        'short' => 'Manufacturing',
        'role_sectors' => ['manufacturing'],
        'keywords' => [
            'manufacturing', 'production engineer', 'mechanical engineer', 'electrical engineer',
            'cnc', 'assembly line', 'maintenance engineer', 'process engineer', 'quality engineer',
            'plant supervisor', 'industrial engineer',
        ],
    ],
    'other' => [
        'label' => 'Other sectors',
        'short' => 'Other',
        'role_sectors' => [
            'hospitality',
            'creative',
            'legal',
            'real_estate',
            'government',
            'agriculture',
        ],
        'keywords' => [
            'hospitality', 'hotel', 'chef', 'restaurant', 'graphic designer', 'video editor',
            'legal', 'paralegal', 'real estate', 'property', 'civil engineer', 'architect',
            'government', 'public sector', 'agriculture', 'agronomist',
        ],
    ],
];
