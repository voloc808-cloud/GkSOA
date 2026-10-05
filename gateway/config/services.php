<?php
return [
    'sinhvien' => env('STUDENT_SERVICE_URL', 'http://127.0.0.1:8002'),
    'detai' => env('TOPIC_SERVICE_URL', 'http://127.0.0.1:8003'),
    'dangky' => env('REGISTRATION_SERVICE_URL', 'http://127.0.0.1:8004'),
    'auth' => env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001'),
    'catalog' => env('CATALOG_SERVICE_URL', 'http://127.0.0.1:8002'),
    'supplier' => env('SUPPLIER_SERVICE_URL', 'http://127.0.0.1:8003'),
    'inventory' => env('INVENTORY_SERVICE_URL', 'http://127.0.0.1:8004'),
    'prescription' => env('PRESCRIPTION_SERVICE_URL', 'http://127.0.0.1:8005'),
    'approval' => env('APPROVAL_SERVICE_URL', 'http://127.0.0.1:8006'),
    'report' => env('REPORT_SERVICE_URL', 'http://127.0.0.1:8007'),
    'notify' => env('NOTIFY_SERVICE_URL', 'http://127.0.0.1:8008'),
    'audit-log' => env('AUDIT_LOG_SERVICE_URL', 'http://127.0.0.1:8009'),
];
