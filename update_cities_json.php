<?php

$destinationsFile = __DIR__ . '/database/seeders/data/DomesticPropertyDestination.json';
$citiesFile = __DIR__ . '/database/seeders/data/DomesticPropertyCity.json';

if (!file_exists($destinationsFile) || !file_exists($citiesFile)) {
    echo "Files not found.\n";
    exit(1);
}

$destinations = json_decode(file_get_contents($destinationsFile), true);
$cities = json_decode(file_get_contents($citiesFile), true);

$destMap = [];
foreach ($destinations as $dest) {
    $destMap[$dest['Id']] = $dest;
}

$updatedCities = [];
foreach ($cities as $city) {
    $destId = $city['PropertyDestinationId'] ?? null;
    $dest = $destMap[$destId] ?? null;

    $city['province_name'] = $dest['NameFa'] ?? null;
    $city['province_name_en'] = $dest['Name'] ?? null;

    // Default Country Info for Iran
    $city['country_name'] = 'ایران';
    $city['country_name_en'] = 'Iran';
    $city['country_code_alpha_2'] = 'IR';
    $city['country_code_alpha_3'] = 'IRN';

    $updatedCities[] = $city;
}

file_put_contents($citiesFile, json_encode($updatedCities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Updated " . count($updatedCities) . " cities.\n";
