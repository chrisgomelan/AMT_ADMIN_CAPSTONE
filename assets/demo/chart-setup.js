// chart-setup.js

// Wait for the DOM to be fully loaded before running the chart setup
document.addEventListener("DOMContentLoaded", function() {
    // JavaScript chart setup using Chart.js
    var ctx = document.getElementById('myAreaChart').getContext('2d');
    var myAreaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Number of Visits',
                data: chartData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
});
