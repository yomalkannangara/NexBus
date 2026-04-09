-- Add profile fields for private owner fleet buses
-- Scope: private owner module only (private_buses table)

ALTER TABLE private_buses
  ADD COLUMN manufactured_date DATE NULL AFTER chassis_no,
  ADD COLUMN manufactured_year YEAR NULL AFTER manufactured_date,
  ADD COLUMN model VARCHAR(100) NULL AFTER manufactured_year,
  ADD COLUMN bus_class ENUM('AC','Semi-Luxury','Normal') NOT NULL DEFAULT 'Normal' AFTER model;
