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

// Query to fetch gender count from informationlogtbl joined with visitortbl
$sql_gender = "SELECT v.gender, COUNT(*) as Count 
               FROM informationlogtbl AS il
               JOIN visitortbl AS v ON il.email = v.email
               WHERE il.Date BETWEEN '$from_date' AND '$to_date'
               GROUP BY v.gender";

$result_gender = $conn->query($sql_gender);

// Initialize arrays to store gender data for chart
$gender_labels = [];
$gender_counts = [];

if ($result_gender->num_rows > 0) {
    while ($row = $result_gender->fetch_assoc()) {
        $gender_labels[] = $row['gender'];
        $gender_counts[] = $row['Count'];
    }
}
// Query to fetch category count based on selected date range
$sql_category = "SELECT v.category, COUNT(*) AS count
                 FROM informationlogtbl AS i
                 INNER JOIN visitortbl AS v ON i.email = v.email
                 WHERE i.Date BETWEEN '$from_date' AND '$to_date'
                 GROUP BY v.category";

$result_category = $conn->query($sql_category);

// Initialize arrays to store data for category count
$categoryLabels = [];
$categoryCounts = [];

if ($result_category->num_rows > 0) {
    while ($row = $result_category->fetch_assoc()) {
        $categoryLabels[] = $row['category']; // Category labels for X-axis
        $categoryCounts[] = $row['count']; // Count of logs per category for Y-axis
    }
}
// Query to fetch peak hour data based on selected date range
$sql_peak_hours = "SELECT HOUR(Time) AS Hour, COUNT(*) AS Count
                   FROM informationlogtbl
                   WHERE Date BETWEEN '$from_date' AND '$to_date'
                   GROUP BY HOUR(Time)";

$result_peak_hours = $conn->query($sql_peak_hours);

// Initialize arrays to store data for peak hours chart
$peak_hour_labels = [];
$peak_hour_counts = [];

if ($result_peak_hours->num_rows > 0) {
    while ($row = $result_peak_hours->fetch_assoc()) {
        $peak_hour_labels[] = $row['Hour'] . ':00'; // Format hour for X-axis labels
        $peak_hour_counts[] = $row['Count']; // Count of logs per hour for Y-axis data
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    </head>
    <body class="sb-nav-fixed">

        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar Brand-->
            <span class="navbar-brand ps-3 flexi">DOST-STII LIBRARY ATTENDANCE MANAGEMENT SYSTEM ADMIN</span>
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                      
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
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
                            <a class="nav-link" href="dashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <div class="sb-sidenav-menu-heading">Reports</div>
                            <a class="nav-link" href="monthly-reports.php" >
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Monthly Reports
                               
                            </a>
       
                            <a class="nav-link" href="daily-reports.php" >
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Daily Reports
                               
                            </a>
       
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        Admin
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
                        <button id="generatePDF">Generate PDF</button>

                        <div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-area me-1"></i>
        Visits Count (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
    </div>
    <div class="card-body">
        <canvas id="myAreaChart" width="100%" height="40"></canvas>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-bar me-1"></i>
        Gender Count Bar Chart (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
    </div>
    <div class="card-body">
        <canvas id="genderBarChart" width="100%" height="40"></canvas>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-bar me-1"></i>
        Category Count Bar Chart (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
    </div>
    <div class="card-body">
        <canvas id="categoryBarChart" width="100%" height="40"></canvas>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-bar me-1"></i>
        Peak Hours (<?php echo $from_date; ?> to <?php echo $to_date; ?>)
    </div>
    <div class="card-body">
        <canvas id="peakHourBarChart" width="100%" height="40"></canvas>
    </div>
</div>


                
                        
    <div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
     Information Log 
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



<div class="container mt-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Visitor credentials
            </div>
            
            <div class="card-body table-responsive">
                <table id="datatablesSimple" class="table table-bordered">
                    <div class="datatable-search">
                </div>
              
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
    var genderCounts = <?php echo json_encode($gender_counts); ?>;
    console.log(genderCounts); // Check if the output is as expected

    // Now integrate this data into your Chart.js configuration
    var ctx = document.getElementById("genderBarChart");
    var genderBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($gender_labels); ?>,
            datasets: [{
                label: 'Visitors by Gender',
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                data: genderCounts // Use the PHP-initialized data here directly
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });

    
</script>
<script>
$(document).ready(function() {
    var table = $('#datatablesSimple').DataTable();
    
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>

<script>
    // Assuming you have fetched and processed your category counts in PHP
    var categoryLabels = <?php echo json_encode($categoryLabels); ?>;
    var categoryCounts = <?php echo json_encode($categoryCounts); ?>;

    // Configuring the Chart.js chart
    var ctx = document.getElementById("categoryBarChart").getContext('2d');
    var categoryBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Visitors by Category',
                backgroundColor: 'rgba(255, 99, 132, 0.5)', // Adjust colors as needed
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                data: categoryCounts
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });
</script>
<script>
var peakHourLabels = <?php echo json_encode($peak_hour_labels); ?>;
var peakHourCounts = <?php echo json_encode($peak_hour_counts); ?>;

// Configuring the Chart.js chart for peak hours
var ctx = document.getElementById("peakHourBarChart").getContext('2d');
var peakHourBarChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: peakHourLabels,
        datasets: [{
            label: 'Peak Hours',
            backgroundColor: 'rgba(255, 206, 86, 0.5)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1,
            data: peakHourCounts
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }]
        }
    }
});
</script>
<script>
document.getElementById('generatePDF').addEventListener('click', function() {
    var { jsPDF } = window.jspdf;

    // Create a new jsPDF instance
    var doc = new jsPDF();

    // Title
    doc.setFontSize(16);
    doc.text('DOST-STII Library Attendance Management System', 10, 10);

    // Capture charts as images and add to PDF
    html2canvas(document.querySelector("#myAreaChart")).then(canvas => {
    var imgData = canvas.toDataURL('image/png');
    doc.addImage(imgData, 'PNG', 10, 20, 180, 60);

    // Add text details for area chart
    doc.setFontSize(12);
    doc.text('Visits Count Details:', 10, 90);
    for (var i = 0; i < chartLabels.length; i++) {
        var text = chartLabels[i] + ': ' + chartData[i]; // Modify as needed for percentage
        doc.text(text, 10, 100 + i * 10);
    }
    var gender_labels = <?php echo json_encode($gender_labels); ?>;
    var gender_counts = <?php echo json_encode($gender_counts); ?>;


            html2canvas(document.querySelector("#genderBarChart")).then(canvas => {
            imgData = canvas.toDataURL('image/png');
            doc.addPage();
            doc.addImage(imgData, 'PNG', 10, 20, 180, 60);

            // Add text details for gender bar chart
            doc.setFontSize(12);
            doc.text('Gender Count Details:', 10, 90);
            for (var i = 0; i < gender_labels.length; i++) {
                var text = gender_labels[i] + ': ' + gender_counts[i]; // Modify as needed for percentage
                doc.text(text, 10, 100 + i * 10);
            }


            html2canvas(document.querySelector("#categoryBarChart")).then(canvas => {
            imgData = canvas.toDataURL('image/png');
            doc.addPage();
            doc.addImage(imgData, 'PNG', 10, 20, 180, 60);

            // Add text details for category bar chart
            doc.setFontSize(12);
            doc.text('Category Count Details:', 10, 90);
            for (var i = 0; i < categoryLabels.length; i++) {
                var text = categoryLabels[i] + ': ' + categoryCounts[i]; // Modify as needed for percentage
                doc.text(text, 10, 100 + i * 10);
            }

                // Capture the next chart
                html2canvas(document.querySelector("#peakHourBarChart")).then(canvas => {
                imgData = canvas.toDataURL('image/png');
                doc.addPage();
                doc.addImage(imgData, 'PNG', 10, 20, 180, 60);

                // Add text details for peak hour bar chart
                doc.setFontSize(12);
                doc.text('Peak Hours Details:', 10, 90);
                for (var i = 0; i < peakHourLabels.length; i++) {
                    var text = peakHourLabels[i] + ': ' + peakHourCounts[i]; // Modify as needed for percentage
                    doc.text(text, 10, 100 + i * 10);
                }
   

    // Add text details for peak hour bar chart
    doc.setFontSize(12);
    doc.text('Peak Hours Details:', 10, 90);
    for (var i = 0; i < peakHourLabels.length; i++) {
        var text = peakHourLabels[i] + ': ' + peakHourCounts[i]; // Modify as needed for percentage
        doc.text(text, 10, 100 + i * 10);
    }

                    // Save the PDF
                    doc.save('chart.pdf');
                });
            });
        });
    });
});
</script>

    </body>
</html>
