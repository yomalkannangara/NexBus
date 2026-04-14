-- Add assignments for 2026-04-15 (Wednesday) mirroring the April 14 batch
-- Depot 1 (Colombo), Depot 2, Depot 3
INSERT IGNORE INTO `sltb_assignments`
    (`assigned_date`, `shift`, `bus_reg_no`, `sltb_driver_id`, `sltb_conductor_id`, `sltb_depot_id`)
VALUES
    ('2026-04-15', 'Morning', 'NB-3101', 1001, 11, 1),
    ('2026-04-15', 'Morning', 'NB-3102', 8,    2,  1),
    ('2026-04-15', 'Morning', 'NB-1001', 1,    1,  1),
    ('2026-04-15', 'Morning', 'NB-1002', 1002, 2001, 1),
    ('2026-04-15', 'Morning', 'NB-2001', 3,    3,  2),
    ('2026-04-15', 'Morning', 'NB-2002', 4,    4,  2),
    ('2026-04-15', 'Morning', 'NB-3001', 5,    5,  3),
    ('2026-04-15', 'Morning', 'NB-3002', 6,    6,  3);
