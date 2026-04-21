-- Practice migration placeholder.
-- The previous custom field example was removed.
-- Add a new study-only SQL example here when needed.

The full MVC pattern for adding a new field

I’ll teach it using an example field called:

assigned_on

Let’s assume examiner says:

“Add a new field called assigned_on to the Depot Officer Assignment page, save it in the database, show it in the table, and allow edit/update.”

That means you must touch:

Database
Model
View
Sometimes controller
JS edit modal if needed
1. Database change
Table used

Your assignment page uses:

sltb_assignments

You can see that in:

models/depot_officer/AssignmentModel.php:157
models/depot_officer/AssignmentModel.php:260
models/depot_officer/AssignmentModel.php:645
models/depot_officer/AssignmentModel.php:800
Add column to DB

If assigned_on is a datetime:

ALTER TABLE sltb_assignments
ADD COLUMN assigned_on DATETIME NULL AFTER assigned_date;

If you want today/current time automatically:

ALTER TABLE sltb_assignments
ADD COLUMN assigned_on DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER assigned_date;
If you maintain SQL file too

In your dump file:

database/nexbus (1).sql:8537-8548

Current structure is:

CREATE TABLE `sltb_assignments` (
  `assignment_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `shift` varchar(8) NOT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `sltb_driver_id` int(11) NOT NULL,
  `sltb_conductor_id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `override_remark` text DEFAULT NULL,
  `overridden_by` int(11) DEFAULT NULL,
  `override_at` datetime DEFAULT NULL
)

You would add:

`assigned_on` datetime DEFAULT NULL,

right after:

`assigned_date` date NOT NULL,

So it becomes:

CREATE TABLE `sltb_assignments` (
  `assignment_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `assigned_on` datetime DEFAULT NULL,
  `shift` varchar(8) NOT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `sltb_driver_id` int(11) NOT NULL,
  `sltb_conductor_id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `override_remark` text DEFAULT NULL,
  `overridden_by` int(11) DEFAULT NULL,
  `override_at` datetime DEFAULT NULL
)
2. Model changes

This is the main file:

models/depot_officer/AssignmentModel.php

This is where create, update, fetch, and list logic lives.

A. Show field in list page
Current code

At models/depot_officer/AssignmentModel.php:242-259

It selects:

$sql = "SELECT 
            a.assignment_id,
            a.assigned_date,
            a.shift,
            ...
Add this line
            a.assigned_on,

So it becomes:

$sql = "SELECT 
            a.assignment_id,
            a.assigned_date,
            a.assigned_on,
            a.shift,
            ...

This is needed so the view can display the new field.

B. Show field in single row fetch for edit
Current code

At models/depot_officer/AssignmentModel.php:816-823

It selects:

"SELECT a.assignment_id,
        a.assigned_date,
        a.shift,
Add:
        a.assigned_on,

So:

"SELECT a.assignment_id,
        a.assigned_date,
        a.assigned_on,
        a.shift,

This is needed so edit form can load existing value.

C. Save field in create()
Current code

At models/depot_officer/AssignmentModel.php:545-556

It reads:

$assigned_date = trim((string)($d['assigned_date'] ?? date('Y-m-d')));
$timetableId   = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
$shift = $this->resolveShiftValue((string)($d['shift'] ?? ''), $timetableId);
Add:
$assigned_on = trim((string)($d['assigned_on'] ?? ''));
$assigned_on = $assigned_on !== '' ? $assigned_on : date('Y-m-d H:i:s');

Put it after $assigned_date.

So:

$assigned_date = trim((string)($d['assigned_date'] ?? date('Y-m-d')));
$assigned_on = trim((string)($d['assigned_on'] ?? ''));
$assigned_on = $assigned_on !== '' ? $assigned_on : date('Y-m-d H:i:s');
$timetableId   = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
D. Add it to insert columns
Current code

At models/depot_officer/AssignmentModel.php:601-602

$baseCols = ['assigned_date','shift','bus_reg_no','sltb_driver_id','sltb_conductor_id','sltb_depot_id'];
$values = [$assigned_date, $shift, $bus, $driver, $conductor, $depotId];
Change to:
$baseCols = ['assigned_date','assigned_on','shift','bus_reg_no','sltb_driver_id','sltb_conductor_id','sltb_depot_id'];
$values = [$assigned_date, $assigned_on, $shift, $bus, $driver, $conductor, $depotId];
E. Add it to update-when-existing path inside create()
Current code

At models/depot_officer/AssignmentModel.php:617-618

$setParts = ['assigned_date = ?', 'shift = ?', 'bus_reg_no = ?', 'sltb_driver_id = ?', 'sltb_conductor_id = ?'];
$updateValues = [$assigned_date, $shift, $bus, $driver, $conductor];
Change to:
$setParts = ['assigned_date = ?', 'assigned_on = ?', 'shift = ?', 'bus_reg_no = ?', 'sltb_driver_id = ?', 'sltb_conductor_id = ?'];
$updateValues = [$assigned_date, $assigned_on, $shift, $bus, $driver, $conductor];
F. Add it to update()
Current code

At models/depot_officer/AssignmentModel.php:746-760

Add after $assignedDate:

$assignedOn = trim((string)($d['assigned_on'] ?? ''));
$assignedOn = $assignedOn !== '' ? $assignedOn : date('Y-m-d H:i:s');

So:

$assignmentId = (int)($d['assignment_id'] ?? 0);
$assignedDate = trim((string)($d['assigned_date'] ?? ''));
$assignedOn = trim((string)($d['assigned_on'] ?? ''));
$assignedOn = $assignedOn !== '' ? $assignedOn : date('Y-m-d H:i:s');
$current = $this->findById($depotId, $assignmentId);
G. Add it to update SQL
Current code

At models/depot_officer/AssignmentModel.php:794-795

$setCols = ['assigned_date=?', 'shift=?', 'bus_reg_no=?', 'sltb_driver_id=?', 'sltb_conductor_id=?'];
$setVals = [$assignedDate, $shift, $bus, $driverId, $conductorId];
Change to:
$setCols = ['assigned_date=?', 'assigned_on=?', 'shift=?', 'bus_reg_no=?', 'sltb_driver_id=?', 'sltb_conductor_id=?'];
$setVals = [$assignedDate, $assignedOn, $shift, $bus, $driverId, $conductorId];
3. View changes
File:

views/depot_officer/assignments.php

This file handles:

table
add modal
edit modal
row dataset
JS modal population
A. Add row dataset field
Current row dataset

At views/depot_officer/assignments.php:262-278

Add:

data-assigned-on="<?= htmlspecialchars($r['assigned_on'] ?? '') ?>"

Right after data-assigned-date.

So:

<tr
  data-assignment-id="<?= (int)$r['assignment_id'] ?>"
  data-assigned-date="<?= htmlspecialchars($r['assigned_date'] ?? date('Y-m-d')) ?>"
  data-assigned-on="<?= htmlspecialchars($r['assigned_on'] ?? '') ?>"
  data-shift="<?= htmlspecialchars($r['shift'] ?? '') ?>"

This is needed so the edit button can load the value into modal.

B. Show field in table

Right now table header already says Assigned On at:

views/depot_officer/assignments.php:254

But it is actually showing assigned_date at:

views/depot_officer/assignments.php:293-298
Current display
$aDate   = $r['assigned_date'] ?? '';
$isToday = ($aDate === date('Y-m-d'));
$sinceClass = $isToday ? 'since-today' : 'since-past';
$sinceLabel = $aDate ? date('M j', strtotime($aDate)) : '—';
If you want to show new assigned_on datetime

Replace with:

$assignedOn = $r['assigned_on'] ?? '';
$sinceLabel = $assignedOn ? date('M j, H:i', strtotime($assignedOn)) : '—';
$isToday = $assignedOn ? (date('Y-m-d', strtotime($assignedOn)) === date('Y-m-d')) : false;
$sinceClass = $isToday ? 'since-today' : 'since-past';

That makes the visible badge use the new DB field.

C. Add field to Add modal form
Current add form starts

At views/depot_officer/assignments.php:330-335

Add a visible field, for example after hidden assigned_date:

<div class="asgn-form-row">
  <label>Assigned On</label>
  <input type="datetime-local" name="assigned_on" id="add-assigned-on" value="<?= date('Y-m-d\TH:i') ?>">
</div>

Best place: after line 358 or before “Effective Period”.

D. Add field to Edit modal form
Current edit form date

At views/depot_officer/assignments.php:448-455

Add below date:

<div class="asgn-form-row">
  <label>Assigned On</label>
  <input type="datetime-local" name="assigned_on" id="edit-assigned-on">
</div>
4. JavaScript changes in the same view

Because edit modal is filled using JS.

A. Populate edit modal with value
Current code

At views/depot_officer/assignments.php:869-873

It sets:

document.getElementById('edit-assignment-id').value = tr.dataset.assignmentId || '';
document.getElementById('edit-timetable-id').value = editInitialTimetableId;
document.getElementById('edit-assigned-date').value = tr.dataset.assignedDate || '';
document.getElementById('edit-shift').value         = tr.dataset.shift || '';
editBusEl.value                                      = tr.dataset.busReg || '';
Add:
document.getElementById('edit-assigned-on').value = tr.dataset.assignedOn || '';

So it becomes:

document.getElementById('edit-assignment-id').value = tr.dataset.assignmentId || '';
document.getElementById('edit-timetable-id').value = editInitialTimetableId;
document.getElementById('edit-assigned-date').value = tr.dataset.assignedDate || '';
document.getElementById('edit-assigned-on').value = tr.dataset.assignedOn || '';
document.getElementById('edit-shift').value         = tr.dataset.shift || '';
editBusEl.value                                      = tr.dataset.busReg || '';
B. Datetime-local format note

HTML datetime-local needs format like:

2026-04-21T14:30

But MySQL usually stores:

2026-04-21 14:30:00

So if the raw DB value does not fill correctly, convert it in PHP before printing:

data-assigned-on="<?= !empty($r['assigned_on']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($r['assigned_on']))) : '' ?>"

Use that version in the dataset.

Same for edit form default value if needed.