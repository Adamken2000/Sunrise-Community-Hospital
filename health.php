<?php
declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$apiUrl = 'health_api.php';
$context = stream_context_create([
    'http' => [
        'timeout' => 12,
        'header' => "Accept: application/json\r\n"
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);
$api = is_string($response) ? json_decode($response, true) : null;

$success = is_array($api) && ($api['success'] ?? false) === true;
$rows = $success && is_array($api['data'] ?? null) ? $api['data'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Health Information | Sunrise Community Hospital</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .health-page {
            width: 90%;
            max-width: 1150px;
            margin: 50px auto;
            color: #2c3e50;
        }
        .health-page h1 { font-size: 3rem; margin-bottom: 12px; }
        .health-page p { font-size: 1.5rem; line-height: 1.8; }
        .health-panel {
            margin-top: 25px;
            padding: 24px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 22px rgba(0,0,0,.06);
        }
        .health-table-wrap { overflow-x: auto; }
        .health-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        .health-table th,
        .health-table td {
            padding: 13px;
            border-bottom: 1px solid #e5e9ed;
            text-align: left;
            font-size: 1.4rem;
        }
        .health-table th {
            background: var(--navbar-color);
            color: #263746;
        }
        .health-message {
            padding: 15px;
            border-radius: 6px;
            background: #fff5dd;
        }
        .health-source {
            margin-top: 20px;
            font-size: 1.3rem !important;
        }
        .health-source a { color: var(--secondary-color); }
    </style>
</head>
<body>
<main class="health-page">
    <span style="color:var(--secondary-color);font-weight:bold">
        PUBLIC HEALTH INFORMATION
    </span>
    <h1>Health Data and Statistics</h1>
    <p>
        Explore selected public health statistics retrieved from the
        World Health Organization's Global Health Observatory.
    </p>

    <section class="health-panel">
        <h2>Life Expectancy at Birth</h2>
        <p>
            Life expectancy at birth is a population-level indicator.
            The figures below are statistical data, not individual medical advice.
        </p>

        <?php if ($success && count($rows) > 0): ?>
            <div class="health-table-wrap">
                <table class="health-table">
                    <thead>
                        <tr>
                            <th>Country Code</th>
                            <th>Year</th>
                            <th>Indicator</th>
                            <th>Value (years)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h((string) ($row['country_code'] ?? '—')) ?></td>
                            <td><?= h((string) ($row['year'] ?? '—')) ?></td>
                            <td><?= h((string) ($row['indicator'] ?? '—')) ?></td>
                            <td>
                                <?php
                                $value = $row['value'] ?? null;
                                echo is_numeric($value)
                                    ? h(number_format((float) $value, 2))
                                    : 'Not available';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="health-message">
                Public health data could not be retrieved right now.
                Please try again later.
            </p>
        <?php endif; ?>

        <p class="health-source">
            Data source:
            <a href="https://www.who.int/data/gho/info/gho-odata-api"
               target="_blank" rel="noopener noreferrer">
                WHO Global Health Observatory API documentation
            </a>
        </p>
    </section>

    <p><a href="index.html">Return to Sunrise Community Hospital</a></p>
</main>
</body>
</html>
