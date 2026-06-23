<?php

return [
    'contact_email' => env('SITE_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'info@carmaxing.bg')),
    'contact_phone' => env('SITE_CONTACT_PHONE', null),
    'website_manager_email' => env('WEBSITE_MANAGER_EMAIL'),
];