<?php

return [
    'batch_size' => 500,

    'max_file_kb' => 51200,

    'column_aliases' => [
        'name' => ['name', 'candidate_name', 'full_name', 'lead_name'],
        'phone' => ['phone', 'mobile', 'mobile_no', 'mobile_number', 'contact', 'contact_number', 'cell'],
        'phone_alt' => ['alternate_telephone', 'alternate_phone', 'telephone', 'landline'],
        'email' => ['email', 'email_id', 'email_address', 'e_mail'],
        'city' => ['city', 'current_location', 'location', 'preferred_location', 'current_city'],
        'state' => ['state', 'region'],
        'company' => ['company', 'current_employer', 'employer', 'organization'],
        'job_title' => [
            'job_title', 'designation', 'current_role', 'role', 'resume_title',
            'current_role_designation', 'title', 'position',
        ],
        'source' => ['source', 'lead_source'],
        'candidate_id' => ['candidate_id', 'id', 'lead_id'],
    ],
];
