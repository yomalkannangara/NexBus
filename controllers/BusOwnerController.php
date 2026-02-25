<?php
namespace App\controllers;

use App\controllers\BaseController;

// Bus Owner models (folders: models/bus_owner/)
use App\models\bus_owner\DashboardModel;
use App\models\bus_owner\BusModel;
use App\models\bus_owner\DriverModel;
use App\models\bus_owner\EarningModel;
use App\models\bus_owner\FeedbackModel;
use App\models\bus_owner\ReportModel;
use App\models\bus_owner\AttendanceModel;

class BusOwnerController extends BaseController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('owner'); // views/layouts/owner.php
        $this->requireLogin(['PrivateBusOwner']);
        // $this->requireLogin(['PrivateBusOwner']);
    }

    /** /O/dashboard */
    public function dashboard()
    {
        $dm = new DashboardModel();
        $bm = new BusModel();
        $stats = $dm->stats();

        $this->view('bus_owner', 'dashboard', [
            'total_buses'       => (int)($stats['total_buses'] ?? 0),
            'active_buses'      => (int)($stats['active_buses'] ?? 0),
            'total_drivers'     => (int)($stats['total_drivers'] ?? 0),
            'total_revenue'     => (float)($stats['total_revenue'] ?? 0),
            'recent_buses'      => $bm->getRecent(5),
            'maintenance_buses' => $bm->getCountByStatus('Maintenance'),
        ]);
    }

    /** /O/fleet */
    public function fleet()
    {
        $m = new BusModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'create') {
                $ok = $m->create($_POST);
                if (!$ok) {
                    return $this->redirect('/B/fleet?msg=duplicate');
                }
                return $this->redirect('/B/fleet?msg=created');
            }
            if ($act === 'update') {
                $reg = (string)($_POST['reg_no'] ?? '');
                $m->update($reg, $_POST);
                return $this->redirect('/B/fleet?msg=updated');
            }
            if ($act === 'delete') {
                $reg = (string)($_POST['reg_no'] ?? '');
                $m->delete($reg);
                return $this->redirect('/B/fleet?msg=deleted');
            }
        }

        if (isset($_GET['delete'])) {
            $m->delete((string)$_GET['delete']);
            return $this->redirect('/B/fleet?msg=deleted');
        }

        $dm = new DriverModel();
        $this->view('bus_owner', 'fleet', [
            'buses'      => $m->all(),
            'drivers'    => $dm->all(),
            'conductors' => $dm->allConductors()
        ]);
    }

    /** /B/fleet/assign - Handle driver/conductor assignment */
    public function fleetAssign()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/B/fleet');
        }

        $regNo      = isset($_POST['reg_no'])     ? trim($_POST['reg_no'])     : '';
        $rawDriver  = isset($_POST['driver_id'])  ? trim($_POST['driver_id'])  : '';
        $rawCond    = isset($_POST['conductor_id']) ? trim($_POST['conductor_id']) : '';

        $driverId    = ($rawDriver !== '' && $rawDriver !== '0')  ? (int)$rawDriver  : null;
        $conductorId = ($rawCond   !== '' && $rawCond   !== '0')  ? (int)$rawCond    : null;

        error_log("[fleetAssign] POST received: reg_no={$regNo}, driver_id={$rawDriver}, conductor_id={$rawCond}");

        if ($regNo === '') {
            error_log("[fleetAssign] Empty reg_no — rejecting");
            return $this->redirect('/B/fleet?msg=error');
        }

        $m = new BusModel();
        $success = $m->assignDriverConductor($regNo, $driverId, $conductorId);

        error_log("[fleetAssign] assign result: " . ($success ? 'success' : 'failed') . " for reg_no={$regNo}");

        if ($success) {
            return $this->redirect('/B/fleet?msg=assigned');
        } else {
            return $this->redirect('/B/fleet?msg=assign_fail');
        }
    }

    /** /O/drivers */


    public function drivers()
    {
        $m = new DriverModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'create') {
                if (!$m->create($_POST)) {
                    return $this->redirect('/B/drivers?msg=error');
                }
                return $this->redirect('/B/drivers?msg=created');
            }

            if ($act === 'update') {
                $id = (int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0);
                if (!$m->update($id, $_POST)) {
                    return $this->redirect('/B/drivers?msg=error');
                }
                return $this->redirect('/B/drivers?msg=updated');
            }

            if ($act === 'delete') {
                $id = (int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0);
                $m->delete($id);
                return $this->redirect('/B/drivers?msg=deleted');
            }

            if ($act === 'create_conductor') {
                if (empty($_POST['private_operator_id'])) {
                    $_POST['private_operator_id'] = $m->getResolvedOperatorId();
                }
                $m->createConductor($_POST);
                return $this->redirect('/B/drivers?msg=conductor_created');
            }

            if ($act === 'update_conductor') {
                $cid = (int)($_POST['private_conductor_id'] ?? $_POST['conductor_id'] ?? 0);
                $m->updateConductor($cid, $_POST);
                return $this->redirect('/B/drivers?msg=conductor_updated');
            }

            if ($act === 'delete_conductor') {
                $cid = (int)($_POST['private_conductor_id'] ?? $_POST['conductor_id'] ?? 0);
                $m->deleteConductor($cid);
                return $this->redirect('/B/drivers?msg=conductor_deleted');
            }
        }

        if (isset($_GET['delete'])) {
            $m->delete((int)$_GET['delete']);
            return $this->redirect('/B/drivers?msg=deleted');
        }

        if (isset($_GET['delete_conductor'])) {
            $m->deleteConductor((int)$_GET['delete_conductor']);
            return $this->redirect('/B/drivers?msg=conductor_deleted');
        }

        // expose operator id so JS can include it for create actions
        $opId = $m->getResolvedOperatorId();

        $this->view('bus_owner', 'drivers', [
            'drivers'     => $m->all(),
            'conductors'  => $m->allConductors(),
            'opId'        => $opId,
        ]);
    }

        public function earnings()
        {
            $m = new EarningModel();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $act = $_POST['action'] ?? '';

                if ($act === 'create') {
                    $success = $m->create($_POST);
                    header('Content-Type: application/json');
                    if ($success) {
                        // Return success for AJAX
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Record created successfully']);
                        exit;
                    } else {
                        // Return error for AJAX
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Failed to create record. Please check the bus ownership.']);
                        exit;
                    }
                }

                if ($act === 'update') {
                    $id = (int)($_POST['earning_id'] ?? 0);
                    $success = $m->update($id, $_POST);
                    header('Content-Type: application/json');
                    if ($success) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
                        exit;
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Failed to update record.']);
                        exit;
                    }
                }

                if ($act === 'delete') {
                    $id = (int)($_POST['earning_id'] ?? 0);
                    $success = $m->delete($id);
                    header('Content-Type: application/json');
                    if ($success) {
                        http_response_code(200);
                        echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
                        exit;
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Failed to delete record.']);
                        exit;
                    }
                }
            }

            // Pass buses for dropdown — use false to include ALL statuses while testing
            $this->view('bus_owner', 'earnings', [
                'earnings' => $m->getAll(),
                'buses'    => $m->getMyBuses(false), // false => include all; true => only Active
            ]);
        }


/** /B/earnings/export - Export earnings data as Excel */
public function exportEarnings()
{
    $m = new EarningModel();
    $earnings = $m->getAll();

    $filename = 'earnings_report_' . date('Y-m-d') . '.xls';

    // Excel XML Spreadsheet 2003 — opens natively in all Excel versions
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Calculate total
    $total = array_sum(array_map(fn($e) => (float)($e['amount'] ?? 0), $earnings));

    // Helper: escape XML special chars
    $x = fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
  <Styles>
    <Style ss:ID="title">
      <Font ss:Bold="1" ss:Size="14" ss:Color="#80143C"/>
    </Style>
    <Style ss:ID="header">
      <Font ss:Bold="1" ss:Color="#FFFFFF"/>
      <Interior ss:Color="#80143C" ss:Pattern="Solid"/>
      <Alignment ss:Horizontal="Center"/>
    </Style>
    <Style ss:ID="total">
      <Font ss:Bold="1"/>
      <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
    </Style>
    <Style ss:ID="currency">
      <NumberFormat ss:Format="#,##0.00"/>
    </Style>
    <Style ss:ID="totalCurrency">
      <Font ss:Bold="1"/>
      <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
      <NumberFormat ss:Format="#,##0.00"/>
    </Style>
    <Style ss:ID="date">
      <NumberFormat ss:Format="YYYY-MM-DD"/>
    </Style>
    <Style ss:ID="even">
      <Interior ss:Color="#F9FAFB" ss:Pattern="Solid"/>
    </Style>
  </Styles>
  <Worksheet ss:Name="Earnings Report">
    <Table>
      <Column ss:Width="100"/>
      <Column ss:Width="130"/>
      <Column ss:Width="140"/>
      <Column ss:Width="120"/>

      <!-- Title row -->
      <Row>
        <Cell ss:MergeAcross="3" ss:StyleID="title">
          <Data ss:Type="String">NexBus Earnings Report — Generated <?= date('Y-m-d H:i') ?></Data>
        </Cell>
      </Row>
      <Row/>

      <!-- Column headers -->
      <Row>
        <Cell ss:StyleID="header"><Data ss:Type="String">Date</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Bus Reg. No</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Source / Note</Data></Cell>
        <Cell ss:StyleID="header"><Data ss:Type="String">Amount (LKR)</Data></Cell>
      </Row>

      <!-- Data rows -->
      <?php foreach ($earnings as $i => $e): ?>
      <?php $style = ($i % 2 === 1) ? ' ss:StyleID="even"' : ''; ?>
      <?php $amount = (float)($e['amount'] ?? 0); ?>
      <Row>
        <Cell<?= $style ?>><Data ss:Type="String"><?= $x($e['date'] ?? '') ?></Data></Cell>
        <Cell<?= $style ?>><Data ss:Type="String"><?= $x($e['bus_reg_no'] ?? '') ?></Data></Cell>
        <Cell<?= $style ?>><Data ss:Type="String"><?= $x($e['source'] ?? '') ?></Data></Cell>
        <Cell ss:StyleID="currency"><Data ss:Type="Number"><?= $amount ?></Data></Cell>
      </Row>
      <?php endforeach; ?>

      <!-- Empty separator -->
      <Row/>

      <!-- Total row -->
      <Row>
        <Cell ss:StyleID="total"><Data ss:Type="String">TOTAL</Data></Cell>
        <Cell ss:StyleID="total"><Data ss:Type="String"></Data></Cell>
        <Cell ss:StyleID="total"><Data ss:Type="String"><?= count($earnings) ?> record(s)</Data></Cell>
        <Cell ss:StyleID="totalCurrency"><Data ss:Type="Number"><?= $total ?></Data></Cell>
      </Row>
    </Table>
  </Worksheet>
</Workbook>
    <?php
    exit;
}


/** /O/feedback */
public function feedback()
{
    $m = new \App\models\bus_owner\FeedbackModel();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';

        // New UI actions (kept private-only by model)
        if ($act === 'reply') {
            $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
            $msg = $_POST['message'] ?? ($_POST['response'] ?? '');
            // Mark in progress when replying
            if ($id !== '') $m->updateStatus((string)$id, 'In Progress');
            $m->sendResponse((string)$id, (string)$msg);
            return $this->redirect('/B/feedback?msg=replied');
        }

        if ($act === 'resolve') {
            $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
            $note = $_POST['note'] ?? ($_POST['message'] ?? ($_POST['response'] ?? ''));
            if ($id !== '') $m->updateStatus((string)$id, 'Resolved');
            if (trim((string)$note) !== '') $m->sendResponse((string)$id, (string)$note);
            return $this->redirect('/B/feedback?msg=resolved');
        }

        if ($act === 'close') {
            $id = $_POST['complaint_id'] ?? ($_POST['feedback_ref'] ?? ($_POST['id'] ?? ''));
            if ($id !== '') $m->updateStatus((string)$id, 'Closed');
            return $this->redirect('/B/feedback?msg=closed');
        }

        if ($act === 'update_status') {
            $m->updateStatus(
                $_POST['feedback_ref'] ?? ($_POST['id'] ?? ''),
                $_POST['status']       ?? 'Open'
            );
            return $this->redirect('/B/feedback?msg=status_updated');
        }

        if ($act === 'send_response') {
            $m->sendResponse(
                $_POST['feedback_ref'] ?? ($_POST['id'] ?? ''),
                $_POST['response']     ?? ''
            );
            return $this->redirect('/B/feedback?msg=response_sent');
        }
    }

    $this->view('bus_owner', 'feedback', [
        'feedback_refs' => $m->getAllIds(),
        'feedback_list' => $m->getAll(),   // includes passenger name + reply_text
    ]);
}


    /** /O/performance */
// at top of controller with other use lines:

// ---------------------------------------------------------
// Performance Reports — single action
// Route idea: /B/reports  (or ?module=bus_owner&page=reports)
// ---------------------------------------------------------
public function reports()
{
    $m = new ReportModel();

    // Read & sanitise filter params from GET (same pattern as admin analytics)
    $routeNo = trim($_GET['route_no'] ?? '');
    $busReg  = trim($_GET['bus_reg']  ?? '');

    $filters = [
        'route_no' => $routeNo,
        'bus_reg'  => $busReg,
    ];

    // Fetch filter-aware metrics
    $metrics = $m->getPerformanceMetrics($filters);

    // Map to $kpi keys matching the view (same shape as admin)
    $kpi = [
        'delayedToday' => $metrics['delayed_buses'],
        'avgRating'    => $metrics['average_rating'] ?? 0,
        'speedViol'    => $metrics['speed_violations'],
        'longWaitPct'  => $metrics['long_wait_rate'],
    ];

    // Build analyticsJson for chart scripts
    $analytics = [
        '_fromServer' => true,
        'kpi'         => $kpi,
    ];

    // Render view: views/bus_owner/reports.php
    $this->view('bus_owner', 'reports', [
        'kpi'           => $kpi,
        'filters'       => $filters,
        'analyticsJson' => json_encode(
            $analytics,
            JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK |
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ),
        'routes'        => $m->getOperatorRoutes(),
        'buses'         => $m->getOperatorBuses(),
        'msg'           => $_GET['msg'] ?? null,
    ]);
}

/** /B/reports/export - Export performance data as CSV */
public function exportReports()
{
    $range = $_GET['range'] ?? '6m';
    $rm = new ReportModel();
    
    // Get performance metrics
    $metrics = $rm->getPerformanceMetrics($range);
    $topDrivers = $rm->topDrivers(10);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="performance_report_' . $range . '_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper Excel UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write metrics section
    fputcsv($output, ['Performance Metrics', 'Range: ' . $range]);
    fputcsv($output, ['']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Trips', $metrics['total_trips'] ?? 0]);
    fputcsv($output, ['Total Revenue (LKR)', number_format((float)($metrics['total_revenue'] ?? 0), 2)]);
    fputcsv($output, ['On-Time Rate (%)', number_format((float)($metrics['ontime_rate'] ?? 0), 1)]);
    fputcsv($output, ['Average Delay (min)', number_format((float)($metrics['avg_delay'] ?? 0), 1)]);
    
    // Separator
    fputcsv($output, ['']);
    fputcsv($output, ['']);
    
    // Write top drivers section
    fputcsv($output, ['Top Performing Drivers']);
    fputcsv($output, ['']);
    fputcsv($output, ['Rank', 'Driver Name', 'Trips', 'Revenue (LKR)', 'On-Time Rate (%)', 'Avg Rating']);
    
    $rank = 1;
    foreach ($topDrivers as $d) {
        fputcsv($output, [
            $rank++,
            $d['driver_name'] ?? 'N/A',
            $d['trips'] ?? 0,
            number_format((float)($d['revenue'] ?? 0), 2),
            number_format((float)($d['ontime_rate'] ?? 0), 1),
            number_format((float)($d['avg_rating'] ?? 0), 2)
        ]);
    }
    
    fclose($output);
    exit;
}

/** /B/attendance — Mark and view staff attendance */
    public function attendance()
    {
        $m = new AttendanceModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $date           = $_POST['work_date'] ?? date('Y-m-d');
            $attendancePost = $_POST['attendance'] ?? [];
            $notesPost      = $_POST['notes']      ?? [];
            $m->bulkSave($date, $attendancePost, $notesPost);
            return $this->redirect('/B/attendance?date=' . urlencode($date) . '&msg=saved');
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        if ($date > date('Y-m-d')) $date = date('Y-m-d');

        $histFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-13 days'));
        $histTo   = $_GET['to']   ?? date('Y-m-d');

        $this->view('bus_owner', 'attendance', [
            'drivers'    => $m->getDrivers(),
            'conductors' => $m->getConductors(),
            'records'    => $m->getForDate($date),
            'summary'    => $m->summary(30),
            'history'    => $m->history($histFrom, $histTo),
            'date'       => $date,
            'histFrom'   => $histFrom,
            'histTo'     => $histTo,
            'msg'        => $_GET['msg'] ?? null,
        ]);
    }

    public function profile()
    {
        $m = new \App\models\bus_owner\ProfileModel();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';

        if ($act === 'update_profile') {
            $ok = $m->updateProfile($_POST);
            return $this->redirect('/B/profile?msg=' . ($ok ? 'updated' : 'update_failed'));
        }

        if ($act === 'change_password') {
            $ok = $m->changePassword($_POST);
            return $this->redirect('/B/profile?msg=' . ($ok ? 'pw_changed' : 'pw_error'));
        }

        if ($act === 'delete_account') {
            $ok = $m->deleteAccount();
            session_destroy();
            return $this->redirect('/login?msg=' . ($ok ? 'account_deleted' : 'delete_failed'));
        }
    }

    $this->view('bus_owner', 'profile', [
        'me'  => $m->getProfile(),
        'msg' => $_GET['msg'] ?? null
    ]);
}

}
