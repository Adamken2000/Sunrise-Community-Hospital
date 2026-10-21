
<?php
// health_api.php
declare(strict_types=1);

/*
 * Public WHO health statistics.
 * This endpoint retrieves life expectancy at birth data.
 * No patient information is sent to the external API.
 */

header('Content-Type: application/json; charset=utf-8');

$url = 'https://ghoapi.azureedge.net/api/WHOSIS_000001?$top=20';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => "Accept: application/json\r\n"
    ]
]);

$json = @file_get_contents($url, false, $context);

if ($json === false) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Public health information is temporarily unavailable.'
    ]);
    exit;
}

$data = json_decode($json, true);

if (!is_array($data) || !isset($data['value']) || !is_array($data['value'])) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'The public health service returned an unexpected response.'
    ]);
    exit;
}

$results = [];

foreach ($data['value'] as $item) {
    $results[] = [
        'country_code' => $item['SpatialDim'] ?? '',
        'year' => $item['TimeDim'] ?? '',
        'value' => $item['NumericValue'] ?? $item['Value'] ?? null,
        'indicator' => 'Life expectancy at birth',
        'unit' => 'years'
    ];
}

echo json_encode([
    'success' => true,
    'source' => 'World Health Organization — Global Health Observatory',
    'indicator' => 'Life expectancy at birth',
    'data' => $results
], JSON_UNESCAPED_SLASHES);
