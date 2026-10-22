//Search bar function
document.addEventListener('DOMContentLoaded', function() {
    searchBar = document.getElementById('search-text');
    if (searchBar) 
    {
        document.getElementById('search-text').addEventListener('keyup', function(){
            let searchTerm = this.value.toLowerCase();
            let items = document.querySelectorAll('.search-item');
            
            items.forEach(function(item) {
                let text = item.getAttribute('data-search');
                if (text.includes(searchTerm)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    }
});


//Tag Search Function
document.addEventListener('DOMContentLoaded', function() {
    const badges = document.querySelectorAll('.badge-toggle');
    const items = document.querySelectorAll('.search-item');

    const updateSearch = () => {
        let activeTags = Array.from(badges) //Get all badges that aren't disabled
        

            .filter(badge => !badge.classList.contains('disabled')) 
            .map(badge => badge.textContent.trim().toLowerCase());

        activeTags = activeTags.map(str => str.replace("/", ""));
        //Get all the tags for each item
        items.forEach(item => {
            const itemTags = item.getAttribute('data-tags').toLowerCase().split(' ');

            //If no tags are enabled, show everything.
            if (activeTags.length === 0) {
                item.classList.remove('hidden'); // Show
            } else {
                const hasAllTags = activeTags.every(tag => itemTags.includes(tag));
                
                if (hasAllTags) {
                    item.classList.remove('hidden'); // Show
                } else {
                    item.classList.add('hidden'); // Hide
                }
            }
        });
    };

    // Initialize badges and click events
    badges.forEach(badge => {
        // Start all as disabled
        badge.classList.add('disabled'); 

        badge.addEventListener('click', () => {
            // Toggle the disabled class
            badge.classList.toggle('disabled');
            
            // Trigger the filter
            updateSearch();
        });
    });
});


function showPassword() {
var x = document.getElementById("inp-password");
if (x.type === "password") {
    x.type = "text";
} else {
    x.type = "password";
}
}

function extendActivation() {
    // Show the hidden form
    const form = document.getElementById("activation-form");
    form.classList.remove("hidden");

    const btn = document.getElementById("activate-btn");

    // Overwrite the onclick attribute directly
    btn.setAttribute("onclick", "handleActivateAccount()");
    
    // Update the text so the user knows the button changed its job
    btn.textContent = "Confirm Activation";
}

function managerMetricsNav(clickedId) {
    // Map buttons to sections
    const sectionMap = {
        'btn-general': 'man-general',
        'btn-project': 'man-project',
        'btn-employee': 'man-employee'
    };

    // Hide sections and remove active from nav-buttons
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('nav-active'));
    Object.values(sectionMap).forEach(id => {
        const section = document.getElementById(id);
        if (section){
            section.classList.add('hidden');
        }
    });

    // Activate selected button and show its section
    const btn = document.getElementById(clickedId);
    if (btn) btn.classList.add('nav-active');

    const targetSectionId = sectionMap[clickedId];
    const targetSection = document.getElementById(targetSectionId);
    if (targetSection){
        targetSection.classList.remove('hidden');
    }

    // Remove hash for general
    if(clickedId == 'btn-general'){
        window.location.hash = "";
    }
}

// Handle Page Reload
window.addEventListener('DOMContentLoaded', () => {
    const currentHash = window.location.hash.replace('#', '');
    
    // Map hashes to their corresponding button
    const reverseHashMap = {
        'manager-project': 'btn-project',
        'employee-metrics': 'btn-employee'
    };

    // Find which button needs to be pressed
    const expectedBtnId = reverseHashMap[currentHash];

    // Simulate the button press
    if (currentHash && expectedBtnId && document.getElementById(expectedBtnId)) {
        managerMetricsNav(expectedBtnId);
    } else {
        // Fallback to General if no hash or invalid hash
        managerMetricsNav('btn-general');
    }
});

// function gauge(notComplete, complete, overdue){
//   const completed = 9;
//   const total = 12;
//   const overdue = 1;
  
//   new Chart(document.getElementById("employee-general-task-gauge"), {
//       type: "doughnut",
//       data: {
//           datasets: [{
//               data: [completed, overdue, total - (completed + overdue)],
//               backgroundColor: ["#007BFF", "#C82333", "#d9d9d9"],
//               borderWidth: 0
//           }]
//       },
//       options: {
//           responsive: true,
//           aspectRatio: 1,
//           cutoutPercentage: 88,
//           circumference: 1.3 * Math.PI,
//           rotation: 0.85 * Math.PI,
//           legend: { display: false },
//           tooltips: { enabled: false },
//           layout: {
//               padding:0
//           }
//       }
//   });
// }

// function graph(expected, completed){
//   const allMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
//   const currentMonthIndex = new Date().getMonth();




//   /* Slicing labels and data to start from the current month */
//   const dynamicLabels = allMonths.slice(currentMonthIndex);
//   const completedData = [2, 5, 7, 10, 12, 13, 14, 15, 17, 19, 21, 23].slice(currentMonthIndex);
//   const expectedData = [3, 5, 7, 10, 11, 13, 14, 16, 18, 20, 21, 23].slice(currentMonthIndex);

//   const barColors = completedData.map((val, i) => {
//       if (val > expectedData[i]) return "#28a745";//green
//       if (val < expectedData[i]) return "#C82333";//red
//       return "#007BFF";//blue
//   });

//   var ctx = document.getElementById('general-graph').getContext('2d');
//   var taskChart = new Chart(ctx, {
//       type: 'bar',
//       data: {
//           labels: dynamicLabels,
//           datasets: [
//               {
//                   label: 'Expected Tasks',
//                   type: 'line',
//                   data: expectedData,
//                   fill: false,
//                   borderColor: '#333',
//                   borderDash: [5, 5],
//                   pointStyle: 'rectRot',
//                   lineTension: 0.3
//               },
//               {
//                   label: 'Tasks Completed',
//                   data: completedData,
//                   backgroundColor: barColors,
//                   borderWidth: 1
//               }
//           ]
//       },
//       options: {
//           responsive: true,
//           maintainAspectRatio: false,
//           legend: {
//               display: true,
//               position: 'bottom',
//               labels: {
//                   usePointStyle: true,
//                   padding: 20,
//                   fontStyle: 'bold'
//               }
//           },
//           scales: {
//               yAxes: [{
//                   ticks: { beginAtZero: true, stepSize: 2 }
//               }],
//               xAxes: [{
//                   gridLines: { display: false }
//               }]
//           }
//       }
//   });
// }