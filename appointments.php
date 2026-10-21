<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireLogin();

require __DIR__ . '/db.php'; 

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole(['admin']);
    verifyCsrfToken();

    $deleteId = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);

    if (!$deleteId || $deleteId < 1) {
        $error = 'Invalid appointment record.';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
            $stmt->execute([$deleteId]);

            $message = $stmt->rowCount() > 0
                ? 'Appointment record deleted successfully.'
                : 'Appointment record was not found.';
        } catch (PDOException $e) {
            error_log('Appointment deletion failed: ' . $e->getMessage());
            $error = 'Unable to delete the appointment. Please try again.';
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = max(1, $page ?: 1);

$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];

if ($search !== '') {
    $where = " WHERE patient_name LIKE :name
               OR national_id LIKE :national_id
               OR phone_number LIKE :phone
               OR email_address LIKE :email
               OR department LIKE :department ";

    $term = '%' . $search . '%';

    $params = [
        ':name' => $term,
        ':national_id' => $term,
        ':phone' => $term,
        ':email' => $term,
        ':department' => $term
    ];
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM appointments' . $where);
$countStmt->execute($params);
$totalRecords = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRecords / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = 'SELECT appointment_id, patient_name, national_id, gender, phone_number,
               email_address, department, appointment_date
        FROM appointments'
        . $where .
        ' ORDER BY appointment_date DESC LIMIT :limit OFFSET :offset';

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$appointments = $stmt->fetchAll();

$role = $_SESSION['staff_role'] ?? '';
$csrf = csrfToken();

$departmentNames = [
    'general' => 'General Medicine',
    'pediatrics' => 'Pediatrics',
    'cardiology' => 'Cardiology',
    'maternity' => 'Obstetrics & Gynecology',
    'neurology' => 'Neurology',
    'dental' => 'Dental Care'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="images/sunrise_logo.png">
    <link rel="stylesheet" href="styles.css">
    <title>Appointment Records | Sunrise Community Hospital</title>
    <style>
        .records-page {
            width: 94%;
            max-width: 1500px;
            margin: 40px auto;
            color: #2c3e50;
        }
        .records-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }
        .records-heading h1 { font-size: 2.8rem; }
        .records-heading p { font-size: 1.4rem; }
        .records-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .records-actions a {
            padding: 10px 16px;
            border-radius: 6px;
            background: var(--secondary-color);
            color: #fff;
            text-decoration: none;
            font-size: 1.4rem;
        }
        .records-search {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .records-search input {
            flex: 1;
            min-width: 0;
            padding: 12px;
            border: 1px solid #ccd5dd;
            border-radius: 6px;
            font-size: 1.4rem;
        }
        .records-search button {
            border: 0;
            border-radius: 6px;
            padding: 10px 18px;
            background: var(--primary-color);
            cursor: pointer;
            font-size: 1.4rem;
        }
        .records-search a{
            text-decoration: none;
        }
        .records-table-wrap {
            overflow-x: auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,.06);
        }
        .records-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }
        .records-table th,
        .records-table td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e6ebef;
            font-size: 1.3rem;
            vertical-align: top;
        }
        .records-table th {
            background: var(--navbar-color);
            color: #263746;
        }
        .records-table td { overflow-wrap: anywhere; }
        .records-table tr:hover td { background: #f8fbfd; }
        .delete-form { margin: 0; }
        .delete-button {
            border: 0;
            border-radius: 5px;
            padding: 8px 10px;
            background: #b42318;
            color: #fff;
            cursor: pointer;
        }
        .notice {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 1.4rem;
        }
        .notice-success { background: #e7f6e7; color: #236b2b; }
        .notice-error { background: #fff0f0; color: #9d2525; }
        .records-pagination {
            display: flex;
            gap: 14px;
            align-items: center;
            margin: 22px 0;
            font-size: 1.4rem;
        }
        .records-pagination a {
            color: var(--secondary-color);
        }
        @media (max-width: 600px) {
            .records-search { flex-direction: column; }
            .records-heading h1 { font-size: 2.3rem; }
        }
    </style>
</head>
<body>
    <div class="page-elements">
        <header>
            <div class="title-bar">
                <div class="logo_name">
                    <a href="index.html" class="logo_as_link" target="_self">
                        <img src="images/sunrise_logo.png" alt="sunrise logo" class="logo">
                        <div class="hospital-name">
                            <p>
                                <span class="sunrise">Sunrise </span> 
                                <span class="community">Community</span> 
                                <span class="hospital">Hospital</span> 
                            </p>
                        </div>
                    </a>
                </div>
                <div class="header-details-container">
                    <div class="hospital_contact">
                        <div class="info_widgets">&#128222;</div>
                        <div class="head-info">
                            <div class="info_text">EMERGENCY</div>
                            <div class="info-item">(254) 545-753-865</div>
                        </div>
                    </div>
                    <div class="work_hour">
                        <div class="info_widgets">&#x1F552;</div>
                        <div class="head-info">
                            <div class="info_text">WORK HOUR</div>
                            <div class="info-item">07:00 - 22:00 Everyday</div>
                        </div>
                    </div>
                    <div class="location">
                        <div class="info_widgets">&#128205;</div>
                        <div class="head-info">
                            <div class="info_text">LOCATION</div>
                            <div class="hospital-area">
                                <a href="https://maps.app.goo.gl/b62ZyZZxRLboZtiKA" target="_blank" class="map_link">6th Avenue Parklands</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nav-bar">
                <nav class="nav-links">
                    <ul>
                        <li><a href="index.html" target="_self" class="page">Home</a></li>
                        <li><a href="About_Us.html" target="_self" class="page">About Us</a></li>
                        <li><a href="Services.html" target="_self" class="page">Services</a></li>
                        <li><a href="Contact_Us.html" target="_self" class="page">Contact Us</a></li>
                    </ul>
                </nav>
                <div class="appointment">
                    <span style="color:#f6ce19" class="page">Appointment Records</span>
                    <button type="button" name="appointment">
                        <a href="Appointment.html" target="_self" class="page1">Book Appointment</a>
                    </button>
                </div>
            </div>
        </header>

        <main class="records-page">
            <header class="records-heading">
                <div>
                    <h1>Appointment Records</h1>
                    <p>
                        Welcome, <?= h((string) ($_SESSION['staff_name'] ?? 'Staff')) ?>
                        (<?= h($role) ?>)
                    </p>
                    <p>Total matching records: <?= $totalRecords ?></p>
                </div>
                <nav class="records-actions">
                    
                    <a href="logout.php">Log Out</a>
                </nav>
            </header>

            <?php if ($message !== ''): ?>
                <div class="notice notice-success" role="status"><?= h($message) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="notice notice-error" role="alert"><?= h($error) ?></div>
            <?php endif; ?>

            <form class="records-search" method="get" action="appointments.php">
                <input type="search" name="search" value="<?= h($search) ?>" placeholder="Search by name, ID, phone, email or department" aria-label="Search appointment records">
                <button type="submit">Search</button>
                <a href="appointments.php">Clear</a>
            </form>

            <div class="records-table-wrap">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>Record ID</th>
                            <th>Patient Name</th>
                            <th>National ID</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Appointment Date</th>
                            <?php if ($role === 'admin'): ?>
                                <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$appointments): ?>
                        <tr>
                            <td colspan="<?= $role === 'admin' ? 9 : 8 ?>">No appointment records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?= (int) $appointment['appointment_id'] ?></td>
                                <td><?= h($appointment['patient_name']) ?></td>
                                <td><?= h($appointment['national_id']) ?></td>
                                <td><?= h(ucfirst($appointment['gender'])) ?></td>
                                <td><?= h($appointment['phone_number']) ?></td>
                                <td><?= h($appointment['email_address']) ?></td>
                                <td><?= h($departmentNames[$appointment['department']] ?? $appointment['department']) ?></td>
                                <td><?= h(date('d M Y, H:i', strtotime($appointment['appointment_date']))) ?></td>
                                <?php if ($role === 'admin'): ?>
                                    <td>
                                        <form method="post" action="appointments.php" class="delete-form" onsubmit="return confirm('Delete this appointment record permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="delete_id" value="<?= (int) $appointment['id'] ?>">
                                            <button type="submit" class="delete-button">Delete</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <nav class="records-pagination" aria-label="Appointment pages">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(['search' => $search, 'page' => $page - 1]) ?>">← Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(['search' => $search, 'page' => $page + 1]) ?>">Next →</a>
                <?php endif; ?>
            </nav>
        </main>

        <footer>
            <div class="footer-row1">
                <!--Hospital's name and motto-->
                <div class="name-motto">
                    <div class="footer-name">
                        <a href="index.html" class="hospital-name" target="_self">
                            <h2 class="footer_name">
                                <span class="sunrise">Sunrise</span> 
                                <span class="community">Community</span> 
                                <span class="hospital">Hospital</span> 
                            </h2>
                        </a>
                    </div>
                    <div class="footer-motto">
                        
                        <pre>A Brighter day for your health,
                            Healing with every new dawn.</pre>
                        
                    </div>
                </div>

                <!--links for webpages-->
                <div class="web-links">
                    <h2>Web Links</h2>
                    <a href="Appointment.html" target="_self" class="page">Book Appointment</a>
                    <a href="About_Us.html" target="_self" class="page">About Us</a>
                    <a href="Services.html" target="_self" class="page">Services</a>
                    <a href="Contact_Us.html" target="_self" class="page">Contact Us</a>
                </div>

                <!--Hospitals contact info-->
                <div class="contact-info" >
                    <h2>Contact Us</h2>
                    <div class="number">
                        Call: (254) 545-753-865
                    </div>
                    <div>
                        <a href="mailto:communityhospitalsunrise@gmail.com" class="page">Email:communityhospitalsunrise@gmail.com</a>
                    </div>
                    <div>
                        <a href="https://maps.app.goo.gl/b62ZyZZxRLboZtiKA" target="_blank" class="page" >Address: 6th Avenue Parklands</a>
                    </div>
                </div>
            </div>

            <!-- copy rights -->
            <div class="copyright-container">
                <div class="copyright">
                    <p>&#169; 2026 Hospital's name All rights Reserved by PNTEC-LTD</p>
                </div>
            </div>
        </footer>
    </div>
    <script src="script.js"></script>
</body>
</html>