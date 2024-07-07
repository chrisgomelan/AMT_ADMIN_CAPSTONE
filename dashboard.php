<?php
// Include your connection function or establish connection here
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
if (strtotime($to_date) > strtotime($today_date)) {
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

// Initialize $data array to store table rows
$data = [];
if ($result_table->num_rows > 0) {
    while ($row = $result_table->fetch_assoc()) {
        $data[] = $row; // Store each row in $data array
    }
} else {
    echo "No logs found for the selected date range.";
}

// Query to fetch visitor data
$sql_visitor = "SELECT email, firstname, middlename, lastname, city, province, gender, school_insti, category FROM visitortbl";
$result_visitor = $conn->query($sql_visitor);

// Initialize $visitorChart array to store table rows
$visitorChart = [];
if ($result_visitor->num_rows > 0) {
    while ($row = $result_visitor->fetch_assoc()) {
        $visitorChart[] = $row; // Store each row in $visitorChart array
    }
}

// Query to fetch peak hour data for today
$sql_peak_hour = "SELECT Time, COUNT(*) as Count FROM informationlogtbl WHERE Date = '$today_date' GROUP BY Time ORDER BY Count DESC LIMIT 1";
$result_peak_hour = $conn->query($sql_peak_hour);
$peak_hour_data = $result_peak_hour->fetch_assoc();
$peak_hour = $peak_hour_data['Time'];
$peak_hour_count = $peak_hour_data['Count'];

// Query to get gender counts
$sql_gender_count = "SELECT gender, COUNT(*) as Count FROM visitortbl GROUP BY gender";
$result_gender_count = $conn->query($sql_gender_count);

// Initialize arrays to store gender counts
$gender_labels = ['Male', 'Female', 'Prefer not to say'];
$gender_counts = [0, 0, 0];

if ($result_gender_count->num_rows > 0) {
    while ($row = $result_gender_count->fetch_assoc()) {
        if ($row['gender'] == 'Male') {
            $gender_counts[0] = $row['Count'];
        } elseif ($row['gender'] == 'Female') {
            $gender_counts[1] = $row['Count'];
        } elseif ($row['gender'] == 'Prefer not to say') {
            $gender_counts[2] = $row['Count'];
        }
    }
}

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
                                    Gender Count Bar Chart
                                </div>
                                <div class="card-body">
                                    <canvas id="genderBarChart" width="100%" height="40"></canvas>
                                </div>
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

        <script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById("genderBarChart").getContext("2d");
        var genderBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($gender_labels); ?>,
                datasets: [{
                    label: 'Gender Count',
                    data: <?php echo json_encode($gender_counts); ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)', // Male
                        'rgba(255, 99, 132, 0.2)', // Female
                        'rgba(75, 192, 192, 0.2)'  // Prefer not to say
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

    </body>
</html>
