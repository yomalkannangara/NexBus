PASSENGER TABLE SCHEMA CHANGE: full_name → first_name + last_name
================================================================

DATE: 2025-01-28
CHANGE SUMMARY: Align passengers table with users table structure

================================================================================
1. SQL MIGRATION SCRIPT
================================================================================

Location: database/migrations/2025_01_28_change_passengers_fullname_to_firstname_lastname.sql

-- Step 1: Add new columns (first_name, last_name)
ALTER TABLE `passengers` 
ADD COLUMN `first_name` VARCHAR(255) NULL DEFAULT NULL AFTER `passenger_id`,
ADD COLUMN `last_name` VARCHAR(255) NULL DEFAULT NULL AFTER `first_name`;

-- Step 2: Backfill data from full_name (split at space)
UPDATE `passengers` 
SET `first_name` = SUBSTRING_INDEX(`full_name`, ' ', 1),
    `last_name` = IF(LOCATE(' ', `full_name`) > 0, SUBSTRING(`full_name`, LOCATE(' ', `full_name`) + 1), '');

-- Step 3: Drop old full_name column
ALTER TABLE `passengers` 
DROP COLUMN `full_name`;

-- Step 4: Create index on first_name
ALTER TABLE `passengers` 
ADD INDEX `idx_first_name` (`first_name`);

================================================================================
2. PHP CODE CHANGES - LOCATIONS UPDATED
================================================================================

A. models/Passenger/ProfileModel.php
-----------------------------------
Location 1 - updateProfile() method (Line ~72)
BEFORE: UPDATE passengers SET full_name=?, email=?, phone=?
AFTER:  UPDATE passengers SET first_name=?, last_name=?, email=?, phone=?
ACTION: Changed to update both first_name and last_name columns

Location 2 - softDelete() method (Line ~144)
BEFORE: SET full_name='Deleted User', email=?, phone=NULL
AFTER:  SET first_name='Deleted', last_name='User', email=?, phone=NULL
ACTION: Split 'Deleted User' into separate first_name and last_name

Location 3 - hardDelete() method (Line ~172)
BEFORE: SET full_name='Deleted User', email=?, phone=NULL
AFTER:  SET first_name='Deleted', last_name='User', email=?, phone=NULL
ACTION: Split 'Deleted User' into separate first_name and last_name

✅ MODIFIED: models/Passenger/ProfileModel.php

---

B. models/UserModel.php
-----------------------
Location - createPassenger() method (Line ~56-62)
BEFORE: 
  INSERT INTO passengers (user_id, full_name, email, phone, password_hash)
  VALUES (?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)

AFTER:
  INSERT INTO passengers (user_id, first_name, last_name, email, phone, password_hash)
  VALUES (?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name)

ACTION: Added code to split full_name input into first_name and last_name:
  $first = explode(' ', trim($name))[0];
  $last = implode(' ', array_slice(explode(' ', trim($name)), 1));

✅ MODIFIED: models/UserModel.php

---

C. models/bus_owner/FeedbackModel.php
--------------------------------------
Location - getAll() method (Line ~34)
BEFORE: p.full_name AS passenger,
AFTER:  CONCAT(p.first_name, ' ', p.last_name) AS passenger,
ACTION: Changed SELECT to concatenate first_name and last_name

✅ MODIFIED: models/bus_owner/FeedbackModel.php

---

D. models/ntc_admin/UserModel.php
----------------------------------
⚠️  ALREADY CORRECT - NO CHANGES NEEDED
This file already uses first_name and last_name in passengers table inserts:
- Line 131: INSERT INTO passengers (user_id, first_name, last_name, ...)
- Line 218: INSERT INTO passengers (user_id, first_name, last_name, ...)

✅ VERIFIED: models/ntc_admin/UserModel.php (correct usage found)

================================================================================
3. FILES THAT DO NOT REFERENCE PASSENGERS TABLE
================================================================================

The following files reference full_name but NOT from passengers table:
- views/Layouts/*.php - uses users table full_name
- views/bus_owner/drivers.php - uses driver/conductor tables full_name
- views/depot_officer/*.php - uses staff/driver tables full_name
- controllers/*.php - handle users table, not passengers directly
- Other models with full_name - refer to users or other tables

No changes needed to these files.

================================================================================
4. SUMMARY OF ALL PASSENGER TABLE USES IN CODEBASE
================================================================================

Tables that reference passengers.passenger_id:
- complaints (complaint_id to passenger_id JOIN)
- passenger_favourites (passenger_id FK)

Columns stored in passengers table (NOW):
  passenger_id       INT PRIMARY KEY
  user_id            INT FK → users.user_id
  first_name         VARCHAR(255)       ← NEW
  last_name          VARCHAR(255)       ← NEW
  email              VARCHAR(255)
  phone              VARCHAR(20)
  password_hash      VARCHAR(255)

================================================================================
5. DEPLOYMENT STEPS
================================================================================

1. Run SQL migration:
   mysql -u root -p nexbus < database/migrations/2025_01_28_change_passengers_fullname_to_firstname_lastname.sql

2. Clear any application caches

3. Test:
   - Passenger registration
   - Passenger profile updates
   - Feedback submission (checks passenger name display)
   - Favorites management

================================================================================
6. ROLLBACK PROCEDURE (if needed)
================================================================================

If you need to revert:

ALTER TABLE `passengers`
ADD COLUMN `full_name` VARCHAR(255);

UPDATE `passengers`
SET `full_name` = CONCAT(`first_name`, ' ', `last_name`);

ALTER TABLE `passengers`
DROP COLUMN `first_name`,
DROP COLUMN `last_name`;

Then revert the PHP code changes from the files listed above.

================================================================================
