-- Bandara roles/permissions audit queries
-- Run after applying the normalization migration/seeder.

SELECT r.name AS role_name,
       COUNT(rhp.permission_id) AS permission_count
FROM roles r
LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id
WHERE r.guard_name = 'web'
GROUP BY r.id, r.name
ORDER BY FIELD(r.name, 'Admin','Manager','Support','Accountant','CAAccountant','Stores','Customer'), r.name;

SELECT r.name AS role_name,
       GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS permissions
FROM roles r
LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id
LEFT JOIN permissions p ON p.id = rhp.permission_id
WHERE r.guard_name = 'web'
GROUP BY r.id, r.name
ORDER BY FIELD(r.name, 'Admin','Manager','Support','Accountant','CAAccountant','Stores','Customer'), r.name;

SELECT name AS legacy_role_name_still_present
FROM roles
WHERE name IN ('CA-Accountant', 'CA Accountant', 'Account', 'admin', 'manager', 'support', 'accountant', 'stores', 'customer')
ORDER BY name;

SELECT p.name AS permission_without_role_assignment
FROM permissions p
LEFT JOIN role_has_permissions rhp ON rhp.permission_id = p.id
WHERE p.guard_name = 'web'
  AND rhp.permission_id IS NULL
ORDER BY p.name;
