/*!
    * Start Bootstrap - SB Admin v7.0.7 (https://startbootstrap.com/template/sb-admin)
    * Copyright 2013-2023 Start Bootstrap
    * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
    */
    // 
// Scripts
// 

window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment Below to persist sidebar toggle between refreshes
        // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        //     document.body.classList.toggle('sb-sidenav-toggled');
        // }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

});


// Calculate sex-disaggregated data
var maleCount = 0;
var femaleCount = 0;
var preferNotToSayCount = 0;
var visitedDates = {};

visitorChart.forEach(function(visitor) {
    var date = visitor['Date'];
    var gender = visitor['gender'];

    // Count each visitor only once per day
    if (!visitedDates[date]) {
        visitedDates[date] = true;

        if (gender === 'Male') {
            maleCount++;
        } else if (gender === 'Female') {
            femaleCount++;
        } else {
            preferNotToSayCount++;
        }
    }
});

// Prepare data for sex-disaggregated report
var sexDisaggregatedData = [maleCount, femaleCount, preferNotToSayCount];


// Calculate categories report
var categories = {};
data.forEach(function(log) {
    var category = log['category'];

    if (category && category !== '') {
        if (!categories[category]) {
            categories[category] = 1;
        } else {
            categories[category]++;
        }
    }
});

// Prepare data for categories report
var categoryLabels = Object.keys(categories);
var categoryData = Object.values(categories);


// Calculate peak hours report
var peakHours = {
    '8 AM - 12 PM': 0,
    '1 PM - 5 PM': 0,
    '6 PM - 10 PM': 0
};

data.forEach(function(log) {
    var time = log['Time'];

    if (time) {
        var hour = parseInt(time.split(':')[0], 10);

        if (hour >= 8 && hour < 12) {
            peakHours['8 AM - 12 PM']++;
        } else if (hour >= 13 && hour < 18) {
            peakHours['1 PM - 5 PM']++;
        } else if (hour >= 18 && hour <= 22) {
            peakHours['6 PM - 10 PM']++;
        }
    }
});

// Prepare data for peak hours report
var peakHourLabels = Object.keys(peakHours);
var peakHourData = Object.values(peakHours);
