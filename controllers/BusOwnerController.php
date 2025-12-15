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
                $m->create($_POST);
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

        $this->view('bus_owner', 'fleet', ['buses' => $m->all()]);
    }

    /** /B/fleet/assign - Handle driver/conductor assignment */
    public function fleetAssign()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/B/fleet');
        }

        // Assignment logic would go here
        // For now, just redirect back
        return $this->redirect('/B/fleet?msg=assigned');
    }

    /** /O/drivers */


    public function drivers()
    {
        $m = new DriverModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';

            if ($act === 'create') {
                if (empty($_POST['private_operator_id'])) {
                    $_POST['private_operator_id'] = $m->getResolvedOperatorId();
                }
                $m->create($_POST);
                return $this->redirect('/B/drivers?msg=created');
            }

            if ($act === 'update') {
                $id = (int)($_POST['private_driver_id'] ?? $_POST['driver_id'] ?? 0);
                $m->update($id, $_POST);
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


/** /B/earnings/export - Export earnings data as CSV */
public function exportEarnings()
{
    $range = $_GET['range'] ?? '6m';
    $m = new EarningModel();
    
    // Get all earnings
    $earnings = $m->getAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="earnings_report_' . $range . '_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper Excel UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header
    fputcsv($output, ['Earnings Report', 'Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['']);
    
    // Column headers
    fputcsv($output, ['Date', 'Bus Registration', 'Route', 'Amount (LKR)', 'Type', 'Remarks']);
    
    // Write data rows
    $totalAmount = 0;
    foreach ($earnings as $e) {
        $amount = (float)($e['amount'] ?? 0);
        $totalAmount += $amount;
        
        fputcsv($output, [
            $e['date'] ?? '',
            $e['bus_reg_no'] ?? 'N/A',
            $e['route'] ?? 'N/A',
            number_format($amount, 2),
            $e['earning_type'] ?? 'Revenue',
            $e['remarks'] ?? ''
        ]);
    }
    
    // Summary row
    fputcsv($output, ['']);
    fputcsv($output, ['Total Earnings', '', '', number_format($totalAmount, 2), '', '']);
    
    fclose($output);
    exit;
}


/** /O/feedback */
public function feedback()
{
    $m = new \App\models\bus_owner\FeedbackModel();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';

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

    // Optional: honor the dropdown filter (UI-only for now)
    $range = $_GET['range'] ?? '6m';
    if (!in_array($range, ['6m','3m','1m'], true)) {
        $range = '6m';
    }

    // Fetch metrics + top drivers
    $metrics    = $m->getPerformanceMetrics();
    $driversRaw = $m->topDrivers(10);

    // Normalize to fields expected by the view
    $topDrivers = array_map(function ($r) {
        return [
            'name'             => $r['full_name'] ?? '',
            'assignment_route' => '',    // no route in current schema; leave blank
            'rating'           => null,  // view will show 0.0 if null
        ];
    }, $driversRaw ?: []);

    // Render view: views/bus_owner/reports.php
    $this->view('bus_owner', 'reports', [
        'metrics'      => $metrics,
        'top_drivers'  => $topDrivers,
        'range'        => $range,
        'msg'          => $_GET['msg'] ?? null,
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
