-- =====================================================
-- Search Available Residents Query
-- =====================================================
-- This query returns only residents who are NOT already
-- assigned as household heads or household members
-- =====================================================

SELECT 
    r.id,
    r.resident_id,
    CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
    r.first_name,
    r.middle_name,
    r.last_name,
    r.suffix,
    r.date_of_birth,
    r.sex,
    r.mobile_number,
    r.current_address
FROM residents r
LEFT JOIN households h ON r.id = h.household_head_id
LEFT JOIN household_members hm ON r.id = hm.resident_id
WHERE r.activity_status = 'Active'
    AND h.id IS NULL              -- Not a household head
    AND hm.id IS NULL             -- Not a household member
ORDER BY r.last_name, r.first_name
LIMIT 50;

-- =====================================================
-- With Search Filter (Optional)
-- =====================================================
-- Add this WHERE condition when searching:
-- AND (
--     CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) LIKE '%search_term%'
--     OR r.resident_id LIKE '%search_term%'
--     OR r.mobile_number LIKE '%search_term%'
-- )

-- =====================================================
-- Explanation:
-- =====================================================
-- 1. LEFT JOIN households h ON r.id = h.household_head_id
--    - Checks if resident is a household head
--    - Returns NULL if not found
--
-- 2. LEFT JOIN household_members hm ON r.id = hm.resident_id
--    - Checks if resident is a household member
--    - Returns NULL if not found
--
-- 3. WHERE h.id IS NULL AND hm.id IS NULL
--    - Only returns residents where BOTH joins are NULL
--    - Ensures resident is neither head nor member
--    - Prevents duplicate household assignments
-- =====================================================
