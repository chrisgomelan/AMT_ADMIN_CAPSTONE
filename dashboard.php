<?php
// // Include your connection function or establish connection here
include 'connections/connect.php';
$conn = connection(); // Assuming connection() function returns the mysqli connection

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}


// Default values for date selection (can be set dynamically based on admin input)
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Ensure that $to_date does not exceed today's date
$today_date = date('Y-m-d');
if ($to_date > $today_date) {
    $to_date = $today_date; // Set $to_date to today's date if it exceeds
}

// Query to fetch data for chart based on selected date range
$sql = "SELECT Date, COUNT(*) as Count FROM informationlogtbl WHERE Date BETWEEN '$from_date' AND '$to_date' GROUP BY Date";
$result = $conn->query($sql);

// Initialize arrays to store data for chart
$chart_labels = [];
$chart_data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chart_labels[] = $row['Date']; // Assuming Date field for X-axis labels
        $chart_data[] = $row['Count']; // Count of logs per Date for Y-axis data
    }
}

// Query to fetch data for DataTable
$sql_table = "SELECT logID, email, Date, Time, purpose FROM informationlogtbl WHERE Date BETWEEN '$from_date' AND '$to_date'";
$result_table = $conn->query($sql_table);
$sql_visitor = "SELECT email, firstname, middlename, lastname, city, province, gender, school_insti, category FROM visitortbl";
$result_visitor = $conn->query($sql_visitor);

// Initialize $data array to store table rows
$data = [];
if ($result_table->num_rows > 0) {
    while ($row = $result_table->fetch_assoc()) {
        $data[] = $row; // Store each row in $data array
    }
}
// Initialize $visitorChart array to store table rows
$visitorChart = [];
if ($result_visitor->num_rows > 0) {
    while ($row = $result_visitor->fetch_assoc()) {
        $visitorChart[] = $row; // Store each row in $visitorChart array
    }
}
// Assuming $from_date and $to_date are properly defined
$sql_logs = "SELECT il.Date, COUNT(DISTINCT vt.email) as Count, vt.gender 
             FROM informationlogtbl il 
             INNER JOIN visitortbl vt ON il.email = vt.email 
             WHERE il.Date BETWEEN '$from_date' AND '$to_date' 
             GROUP BY il.Date, vt.gender";
$result_logs = $conn->query($sql_logs);

// Process the results
$gender_counts = [];
while ($row = $result_logs->fetch_assoc()) {
    $date = $row['Date'];
    $gender = $row['gender'];
    $count = $row['Count'];

    // Store or process the data as needed
    // Example: Storing in an array for display or further processing
    if (!isset($gender_counts[$date])) {
        $gender_counts[$date] = [];
    }
    $gender_counts[$date][$gender] = $count;
}

// Example output structure:
// $gender_counts['2024-07-07']['Male'] = 2; // Assuming there are 2 male visitors
// $gender_counts['2024-07-07']['Female'] = 0; // Assuming there are 0 female visitors



// Initialize arrays to store data for chart and table
$chart_labels = [];
$male_counts = [];
$female_counts = [];

if ($result_logs->num_rows > 0) {
    while ($row = $result_logs->fetch_assoc()) {
        $chart_labels[$row['Date']] = $row['Date']; // Assuming Date field for X-axis labels
        if ($row['gender'] == 'Male') {
            $male_counts[$row['Date']] = $row['Count']; // Male visitor counts per Date
        } else {
            $female_counts[$row['Date']] = $row['Count']; // Female visitor counts per Date
        }
    }
}

// Calculate total counts
$total_male_logs = array_sum($male_counts);
$total_female_logs = array_sum($female_counts);

// Calculate total logs (optional, if needed for other purposes)
$total_logs = $total_male_logs + $total_female_logs;

// Calculate percentages
$percentage_male_logs = ($total_logs > 0) ? round(($total_male_logs / $total_logs) * 100, 2) : 0;
$percentage_female_logs = ($total_logs > 0) ? round(($total_female_logs / $total_logs) * 100, 2) : 0;


$conn->close(); // Close the database connection
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>DOST-STII AMS ADMIN</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="icon" href="assets/img/dost-stii_logo-white.png">
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar Brand-->
            <span class="navbar-brand ps-3 flexi">DOST-STII LIBRARY ATTENDANCE MANAGEMENT SYSTEM ADMIN</span>
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                <div class="input-group">
                    <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="#!">Settings</a></li>
                        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="#!">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Core</div>
                            <a class="nav-link" href="index.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <div class="sb-sidenav-menu-heading">Interface</div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Layouts
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="layout-static.html">Static Navigation</a>
                                    <a class="nav-link" href="layout-sidenav-light.html">Light Sidenav</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Pages
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAuth" aria-expanded="false" aria-controls="pagesCollapseAuth">
                                        Authentication
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseAuth" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a class="nav-link" href="login.html">Login</a>
                                            <a class="nav-link" href="register.html">Register</a>
                                            <a class="nav-link" href="password.html">Forgot Password</a>
                                        </nav>
                                    </div>
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false" aria-controls="pagesCollapseError">
                                        Error
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a class="nav-link" href="401.html">401 Page</a>
                                            <a class="nav-link" href="404.html">404 Page</a>
                                            <a class="nav-link" href="500.html">500 Page</a>
                                        </nav>
                                    </div>
                                </nav>
                            </div>
                            <div class="sb-sidenav-menu-heading">Addons</div>
                            <a class="nav-link" href="charts.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Charts
                            </a>
                            <a class="nav-link" href="tables.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Tables
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        Start Bootstrap
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Dashboard</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                       <!-- Date Range Selection Form -->
                        <form method="GET" onsubmit="validateDateInputs();">
                            <label for="from_date">From Date:</label>
                            <input type="date" id="from_date" name="from_date" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">

                            <label for="to_date">To Date:</label>
                            <input type="date" id="to_date" name="to_date" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">

                            <button type="submit">Generate Report</button>
                        </form>
                        <!-- Area Chart Example -->
                        <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-area me-1"></i>
                            Visits Chart (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
                        </div>
                        <div class="card-body">
                            <canvas id="myAreaChart" width="100%" height="40"></canvas>
                        </div>
                        </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Bar Chart Example
                                    </div>
                                    <div class="card-body"><canvas id="myBarChart" width="100%" height="40"></canvas></div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Peak Hour as of Today
                                    </div>
                                    <div class=""><canvas id="peakHour" width="100%" height="40"></canvas></div>
                                </div>
                            </div>
                        <div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        DataTable Example
    </div>
    <div class="card-body">
        <table id="datatablesSimple" class="table table-bordered">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>Log ID</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo $row['logID']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['Date']; ?></td>
                        <td><?php echo $row['Time']; ?></td>
                        <td><?php echo $row['purpose']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Visitor Statistics from Information Log (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Total Logs</h5>
                <p>Total Male Logs: <?php echo $total_male_logs; ?></p>
                <p>Total Female Logs: <?php echo $total_female_logs; ?></p>
            </div>
            <div class="col-md-6">
                <h5>Percentage Distribution</h5>
                <p>Male: <?php echo $percentage_male_logs; ?>%</p>
                <p>Female: <?php echo $percentage_female_logs; ?>%</p>
            </div>
        </div>
    </div>
</div>




<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Visitor credentials
    </div>
    <div class="card-body table-responsive">
    <table id="datatablesSimple" class="table table-bordered">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Middle Name</th>
                    <th>Surname</th>
                    <th>Gender</th>
                    <th>Category</th>
                    <th>School Institution</th>
                    <th>Province</th>
                    <th>City</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitorChart as $row): ?>
                    <tr>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['firstname']; ?></td>
                        <td><?php echo $row['middlename']; ?></td>
                        <td><?php echo $row['lastname']; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['school_insti']; ?></td>
                        <td><?php echo $row['province']; ?></td>
                        <td><?php echo $row['city']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Your Website 2023</div>
                            <div>
                                <a href="#">Privacy Policy</a>
                                &middot;
                                <a href="#">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="assets/demo/chart-setup.js"></script> <!-- Include the external JavaScript file -->

        <script>
        var chartLabels = <?php echo json_encode($chart_labels); ?>;
        var chartData = <?php echo json_encode($chart_data); ?>;
         </script>

        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
    </body>
</html>
