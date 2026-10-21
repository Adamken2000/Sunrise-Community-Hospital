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


document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("appointmentForm");
    if (!form) return;

    const errorBox = document.getElementById("formErrors");

    const fields = {
        name: document.getElementById("patient_name"),
        nationalId: document.getElementById("national_id"),
        gender: document.getElementById("gender"),
        phone: document.getElementById("phone_number"),
        email: document.getElementById("email"),
        department: document.getElementById("department"),
        date: document.getElementById("appointment_date")
    };

    // Display today's local date/time as the minimum selectable value.
    function localDateTimeValue(date) {
        const pad = number => String(number).padStart(2, "0");

        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
            `T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function setMinimumDate() {
        fields.date.min = localDateTimeValue(new Date());
    }

    setMinimumDate();

    // Remove previous error messages.
    function clearErrors() {
        errorBox.replaceChildren();
        errorBox.hidden = true;

        Object.values(fields).forEach(field => {
            field.removeAttribute("aria-invalid");

            const oldError = document.getElementById(`${field.id}-error`);
            if (oldError) oldError.remove();
        });
    }

    // Display a field-specific error using DOM manipulation.
    function showFieldError(field, message) {
        field.setAttribute("aria-invalid", "true");

        let error = document.getElementById(`${field.id}-error`);

        if (!error) {
            error = document.createElement("small");
            error.id = `${field.id}-error`;
            error.className = "field-error";
            field.insertAdjacentElement("afterend", error);
        }

        error.textContent = message;
    }

    function validateForm() {
        clearErrors();
        const errors = [];

        function fail(field, message) {
            errors.push(message);
            showFieldError(field, message);
        }

        const name = fields.name.value.trim();
        const nationalId = fields.nationalId.value.trim();
        const gender = fields.gender.value;
        const phone = fields.phone.value.trim();
        const email = fields.email.value.trim();
        const department = fields.department.value;
        const dateValue = fields.date.value;

        // Patient name.
        if (!name) {
            fail(fields.name, "Patient name is required.");
        } else if (name.length > 120) {
            fail(fields.name, "Name cannot exceed 120 characters.");
        } else if (!/^[\p{L}\p{M} .'-]+$/u.test(name)) {
            fail(fields.name, "Enter a valid name using letters and spaces.");
        }

        // National ID: 8–14 letters/numbers or hyphens.
        if (!/^[A-Za-z0-9-]{8,14}$/.test(nationalId)) {
            fail(fields.nationalId, "National ID must contain 8–14 letters or numbers.");
        }

        // Gender.
        if (!["Male", "Female"].includes(gender)) {
            fail(fields.gender, "Please select a gender.");
        }

        // Phone: allows an optional + and spaces, with 10–12 digits.
        const digits = phone.replace(/\D/g, "");

        if (
            !/^\+?[0-9 ]{10,16}$/.test(phone) ||
            digits.length < 10 ||
            digits.length > 12
        ) {
            fail(fields.phone, "Enter a valid phone number containing 10–12 digits.");
        }

        // Email.
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            fail(fields.email, "Email address is required.");
        } else if (email.length > 254 || !emailPattern.test(email)) {
            fail(fields.email, "Enter a valid email address, e.g. john@mail.com.");
        }

        // Department.
        if (!fields.department.value) {
            fail(fields.department, "Please select a department.");
        }

        // Date/time.
        setMinimumDate();

        if (!dateValue) {
            fail(fields.date, "Please select an appointment date and time.");
        } else {
            const selectedDate = new Date(dateValue);
            const now = new Date();

            if (Number.isNaN(selectedDate.getTime())) {
                fail(fields.date, "Please enter a valid appointment date and time.");
            } else if (selectedDate <= now) {
                fail(fields.date, "Appointment date and time must be in the future.");
            }
        }

        if (errors.length > 0) {
            errorBox.hidden = false;

            const heading = document.createElement("p");
            heading.textContent = "Please correct the following errors:";
            errorBox.appendChild(heading);

            const list = document.createElement("ul");

            errors.forEach(message => {
                const item = document.createElement("li");
                item.textContent = message;
                list.appendChild(item);
            });

            errorBox.appendChild(list);

            const firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) firstInvalid.focus();

            return false;
        }

        return true;
    }

    // Client-side checks before the browser sends the form.
    form.addEventListener("submit", event => {
        if (!validateForm()) {
            event.preventDefault();
        }
    });

    // Remove errors as the patient corrects fields.
    Object.values(fields).forEach(field => {
        field.addEventListener("input", () => {
            field.removeAttribute("aria-invalid");

            const oldError = document.getElementById(`${field.id}-error`);
            if (oldError) oldError.remove();

            if (form.querySelectorAll('[aria-invalid="true"]').length === 0) {
                errorBox.replaceChildren();
                errorBox.hidden = true;
            }
        });

        field.addEventListener("change", () => {
            if (field === fields.date) setMinimumDate();
        });
    });

    form.addEventListener("reset", () => {
        setTimeout(clearErrors, 0);
    });
});



