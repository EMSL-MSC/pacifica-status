<?php
/**
 * CI Default Pacifica Config
 *
 * PHP Version 5
 *
 * @category Configuration
 * @package  Default_Pacifica
 * @author   Ken Auberry <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */

defined('BASEPATH') or exit('No direct script access allowed');

$config['local_timezone'] = 'America/Los_Angeles';

$cart_port = getenv('CART_PORT');
$cart_dl_port = getenv('CART_DOWNLOAD_PORT');
$site_theme_name = getenv('SITE_THEME');

$files_dl_port = getenv('FILE_DOWNLOAD_PORT');

$config['internal_cart_url'] = !empty($cart_port) ?
    str_replace('tcp://', 'http://', $cart_port) :
    'http://cart:8081';

$config['external_cart_url'] = !empty($cart_dl_port) ?
    str_replace('tcp://', 'https://', $cart_dl_port) :
    'http://cart.emsl.pnl.gov';

$config['external_file_url'] = !empty($files_dl_port) ?
    str_replace('tcp://', 'https://', $files_dl_port) :
    'http://files.emsl.pnl.gov';


$config['template'] = 'emsl';
$config['site_color'] = 'orange';

if ($site_theme_name == 'external') {
    $config['theme_name'] = 'myemsl';
    $config['site_identifier'] = "MyEMSL";
    $config['site_slogan'] = 'EMSL User Portal Data Retrieval';
    $config['ui_instrument_desc'] = 'Select an Instrument';
    $config['ui_project_desc'] = 'Select a Project';
} elseif ($site_theme_name == 'myemsl') {
    $config['theme_name'] = 'myemsl';
    $config['site_identifier'] = "MyEMSL";
    $config['site_slogan'] = 'Data Management for Science';
    $config['ui_instrument_desc'] = 'Select an Instrument';
    $config['ui_project_desc'] = 'Select an EUS Project';
} else {
    $config['theme_name'] = 'datahub';
    $config['site_identifier'] = 'DataHub';
    $config['site_slogan'] = 'Data Management for Science';
    $config['ui_instrument_desc'] = 'Select an Instrument';
    $config['ui_project_desc'] = 'Select a Project';
}

$config['application_version'] = "2.8.0";

$config['cookie_encryption_key'] = "eus_rocks_2019!!!";
$config['cookie_name'] = "EUS_ID";
$config['enable_cookie_redirect'] = false;
$config['cookie_redirect_url'] = "https://d-eusi.emsl.pnl.gov/Portal";
$config['enable_single_file_download'] = false;
$config['enable_require_credentials_for_cart_download'] = false;
$config['nexus_backend_url'] = "https://nexus-dev-srv.emsl.pnl.gov";
$config['nexus_portal_url'] = "https://nexus-dev.emsl.pnl.gov/Portal";
$config['cart_data_url'] = "/get_active_cart_information_by_user_id";
$config['cart_data_add_url'] = "/add_new_cart_tracking_information";
$config['cart_deactivate_url'] = "/deactivate_cart_by_cart_uuid";
