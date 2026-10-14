// Function to show the sidebar
function showSidebar(){
    const sidebar = document.querySelector('#sidebar')
    sidebar.style.display = 'flex'
}

// Function to hide the sidebar
function hideSidebar(){
    const sidebar = document.querySelector('#sidebar')
    sidebar.style.display = 'none'
}

// Funcyion for buttons when clicked
document.addEventListener('DOMContentLoaded', function(){
    const servicesBtn = document.getElementById('services-btn');
    if (servicesBtn) {
        servicesBtn.addEventListener('click' , function(){
            window.location.href = 'Services.html' ;
        });
    }
});

document.addEventListener('DOMContentLoaded', function(){
    const appointmentBtn = document.getElementById('appointment-btn');
    if (appointmentBtn) {
        appointmentBtn.addEventListener('click' , function(){
            window.location.href = 'Appointment.html' ;
        });
    }
});

document.addEventListener('DOMContentLoaded', function(){
    const contactBtn = document.getElementById('contact-btn');
    if (contactBtn) {
        contactBtn.addEventListener('click' , function(){
            window.location.href = 'Contact_Us.html' ;
        });
    }
});


