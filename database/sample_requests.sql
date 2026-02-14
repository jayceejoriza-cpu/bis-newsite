-- ============================================
-- Sample Certificate Requests Data
-- ============================================
-- This file contains sample data for testing the requests page
-- Make sure you have existing residents and certificates before running this
-- ============================================

USE `bmis`;

-- Insert sample certificate requests
-- Note: Adjust resident_id and certificate_id values based on your existing data

INSERT INTO `certificate_requests` (
    `reference_no`,
    `resident_id`,
    `certificate_id`,
    `payment_status`,
    `certificate_fee`,
    `status`,
    `purpose`,
    `date_requested`,
    `remarks`
) VALUES
-- Request 1
(
    'CesgoQfYv1_kAzO7jk',
    1,
    1,
    'Waived',
    0.00,
    'Approved',
    'For employment purposes',
    '2026-01-19 10:30:00',
    'Senior citizen - fee waived'
),

-- Request 2
(
    'NmtBEaKSQJalcnIS',
    2,
    2,
    'Unpaid',
    255.00,
    'Pending',
    'For business permit application',
    '2025-11-03 14:15:00',
    NULL
),

-- Request 3
(
    'VuNkEswFmPmzQxYk',
    3,
    1,
    'Unpaid',
    50.00,
    'Pending',
    'For school requirements',
    '2025-11-03 09:45:00',
    NULL
),

-- Request 4
(
    'REF-2026-001',
    1,
    3,
    'Paid',
    100.00,
    'Completed',
    'For travel abroad',
    '2026-01-15 11:20:00',
    'Completed and released'
),

-- Request 5
(
    'REF-2026-002',
    2,
    1,
    'Waived',
    0.00,
    'Approved',
    'For medical assistance',
    '2026-01-16 13:30:00',
    'Indigent - fee waived'
),

-- Request 6
(
    'REF-2026-003',
    3,
    2,
    'Unpaid',
    150.00,
    'Pending',
    'For loan application',
    '2026-01-17 10:00:00',
    NULL
),

-- Request 7
(
    'REF-2026-004',
    1,
    1,
    'Paid',
    50.00,
    'Approved',
    'For employment',
    '2026-01-18 15:45:00',
    NULL
),

-- Request 8
(
    'REF-2026-005',
    2,
    3,
    'Unpaid',
    200.00,
    'Pending',
    'For visa application',
    '2026-01-19 09:15:00',
    NULL
);

-- ============================================
-- Verify the inserted data
-- ============================================
SELECT 
    cr.reference_no,
    CONCAT(r.first_name, ' ', r.last_name) as resident_name,
    c.title as certificate,
    cr.payment_status,
    cr.certificate_fee,
    cr.status,
    cr.date_requested
FROM certificate_requests cr
INNER JOIN residents r ON cr.resident_id = r.id
INNER JOIN certificates c ON cr.certificate_id = c.id
ORDER BY cr.date_requested DESC;

-- ============================================
-- End of Sample Data
-- ============================================
