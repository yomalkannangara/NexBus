# Database Migrations

This folder contains SQL migration files following MVC architecture principles.

## Why Migrations Belong Here

In MVC architecture:
- **Models** handle database queries and business logic
- **Views** handle presentation
- **Controllers** coordinate between Models and Views
- **Database schema changes** are managed separately via migration files

Migrations should **NOT** be placed in:
- `public/` folder (breaks separation of concerns)
- Mixed with application code

## How to Run Migrations

### Method 1: phpMyAdmin (Recommended for Development)
1. Open phpMyAdmin in your browser
2. Select your database (e.g., `nexbus`)
3. Click on the "SQL" tab
4. Copy the contents of the migration file
5. Paste and click "Go"

### Method 2: MySQL Command Line
```bash
mysql -u root -p nexbus < database/migrations/2025_12_16_add_assignment_columns.sql
```

### Method 3: MySQL Workbench
1. Open MySQL Workbench
2. Connect to your database
3. File → Open SQL Script
4. Select the migration file
5. Execute (⚡ icon)

## Migration Files

- `2025_12_16_add_assignment_columns.sql` - Adds driver_id and conductor_id to private_buses table

## After Running Migration

The assignment functionality in the Fleet Management section will work properly:
- Assign drivers to buses
- Assign conductors to buses
- View assignments in the fleet table
