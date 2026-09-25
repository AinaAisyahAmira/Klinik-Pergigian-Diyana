const reportData = window.reportData || {
    treatmentLabels: [],
    treatmentCounts: [],
    forecastLabels: [],
    forecastCounts: [],
    statusLabels: [],
    statusCounts: []
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 15, font: { size: 12 } }
        }
    }
};

// Treatment Breakdown
new Chart(document.getElementById('treatmentChart'), {
    type: 'pie',
    data: {
        labels: reportData.treatmentLabels,
        datasets: [{
            data: reportData.treatmentCounts,
            backgroundColor: ['#2E8B8B', '#4DB6AC', '#80CBC4', '#B2DFDB', '#E0F2F1']
        }]
    },
    options: chartOptions
});

// Forecast Patients
new Chart(document.getElementById('forecastChart'), {
    type: 'bar',
    data: {
        labels: reportData.forecastLabels,
        datasets: [{
            label: 'Patients per Month',
            data: reportData.forecastCounts,
            backgroundColor: '#2E8B8B'
        }]
    },
    options: chartOptions
});

// Status Breakdown
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: reportData.statusLabels,
        datasets: [{
            data: reportData.statusCounts,
            backgroundColor: ['#FFC107', '#28A745', '#DC3545']
        }]
    },
    options: chartOptions
});