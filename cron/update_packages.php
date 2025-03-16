<?php
// Load WordPress environment without rendering a page
define('WP_USE_THEMES', false);
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Increase memory limit to handle large datasets
ini_set('memory_limit', '512M');

// Make sure only CLI can run this
if (php_sapi_name() !== 'cli') {
    // Web access - verify secret key
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'your_secret_key_here') {
        die('Access denied.');
    }
}

// Function to log messages
function log_message($message) {
    echo date('[Y-m-d H:i:s]') . " $message\n";
    error_log($message);
}

log_message("Starting eSIM packages update...");

// Get API credentials
$api_key = get_option('AdPro_api_key');
$merchant_id = get_option('AdPro_merchant_id');

if (empty($api_key) || empty($merchant_id)) {
    log_message("Error: Missing API credentials");
    die("Error: Missing API credentials");
}

// Build API URL - request all packages at once
$api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products?category=esim_realtime';

// Set up request arguments
$args = [
    'headers' => [
        'Accept' => 'text/plain',
        'merchantId' => $merchant_id,
        'api-key' => $api_key,
    ],
    'timeout' => 60, // Longer timeout for the larger request
];

log_message("Requesting all eSIM packages...");

// Make API request
$response = wp_remote_get($api_url, $args);

// Check for errors
if (is_wp_error($response)) {
    log_message("API Error: " . $response->get_error_message());
    die("API Error: " . $response->get_error_message());
}

// Parse response
$response_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);

// Check response validity
if ($response_code !== 200 || !isset($data['statusCode']) || $data['statusCode'] !== 200) {
    log_message("Invalid response: Status $response_code");
    log_message("Response body: " . substr($body, 0, 1000) . "..."); // Log part of the response
    die("Invalid response: Status $response_code");
}

// Get all packages from response
$all_packages = isset($data['result']) ? $data['result'] : [];
$total_packages = count($all_packages);

if (empty($all_packages)) {
    log_message("No packages found");
    die("No packages found");
}

log_message("Retrieved $total_packages packages successfully");

// Organize packages by country
log_message("Organizing packages by country...");
$packages_by_country = [];
foreach ($all_packages as $package) {
    if (isset($package['country'])) {
        $iso = $package['country'];
        if (!isset($packages_by_country[$iso])) {
            $packages_by_country[$iso] = [];
        }
        $packages_by_country[$iso][] = $package;
    }
}

// If package doesn't have a 'country' field but has 'countries' array
// This is a fallback in case the API structure is different
if (empty($packages_by_country)) {
    log_message("No 'country' field found, checking 'countries' array...");
    foreach ($all_packages as $package) {
        if (isset($package['countries']) && is_array($package['countries'])) {
            foreach ($package['countries'] as $iso) {
                if (!isset($packages_by_country[$iso])) {
                    $packages_by_country[$iso] = [];
                }
                $packages_by_country[$iso][] = $package;
            }
        }
    }
}

// Save to JSON files
$json_dir = dirname(__FILE__) . '/../data';

if (!file_exists($json_dir)) {
    mkdir($json_dir, 0755, true);
    log_message("Created data directory: $json_dir");
}

// Save individual JSON file for each country
$country_count = 0;
foreach ($packages_by_country as $iso => $packages) {
    $country_file = $json_dir . '/' . strtolower($iso) . '.json';
    $country_data = json_encode($packages);
    
    if (file_put_contents($country_file, $country_data)) {
        $country_count++;
        $package_count = count($packages);
        log_message("Saved $package_count packages for country $iso to $country_file");
    } else {
        log_message("Error: Failed to save packages for country $iso");
    }
}

log_message("Successfully saved packages for $country_count countries");

// Save list of countries for reference
$countries_list_file = $json_dir . '/countries_list.json';
$countries_list = array_keys($packages_by_country);
if (file_put_contents($countries_list_file, json_encode($countries_list))) {
    log_message("Saved list of " . count($countries_list) . " countries with packages");
} else {
    log_message("Error: Failed to save countries list");
}

// Update last update time
update_option('adpro_packages_last_update', time());

log_message("eSIM packages update completed");