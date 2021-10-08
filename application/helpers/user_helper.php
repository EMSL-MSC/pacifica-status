<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view
 *  the current state of any uploads they may have performed, as
 *  well as enabling the download and retrieval of that data.
 *
 *  This file contains a number of common functions related to
 *  file info and handling.
 *
 * PHP version 5.5
 *
 * @package Pacifica-upload-status
 *
 * @author  Ken Auberry <kenneth.auberry@pnnl.gov>
 * @license BSD https://opensource.org/licenses/BSD-3-Clause
 *
 * @link http://github.com/EMSL-MSC/Pacifica-reporting
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

function get_user()
{
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $remote_user = false;
    $auth_method = false;
    $remote_user = $_SERVER["OIDC_CLAIM_email"] ?? $_SERVER["REMOTE_USER"] ?? $_SERVER["PHP_AUTH_USER"] ?? false;

    if (!$remote_user) {
        return $remote_user;
    }

    $query_url = "{$CI->nexus_backend_url}/get_nexus_user_id_for_identifier/";
    $query_url .= urlencode($remote_user);

    try {
        $options = ['verify' => false];
        $query = Requests::get($query_url, array('Accept' => 'application/json'), $options);
    } catch (Exception $e) {
        $results = [];
        return $results;
    }
    $results_body = $query->body;
    $results_json = json_decode($results_body, true);
    if ($query->status_code == 200 && !empty($results_json)) {
        $results = $results_json['message']['user_id'];
    }
    // var_dump($results);
    return $results;
}

/**
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from Apache
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_old()
{
    $CI =& get_instance();
    $CI->load->library('PHPRequests');
    $md_url = $CI->metadata_url_base;
    $remote_user = array_key_exists("REMOTE_USER", $_SERVER) ? $_SERVER["REMOTE_USER"] : false;
    $remote_user = !$remote_user && array_key_exists("PHP_AUTH_USER", $_SERVER) ? $_SERVER["PHP_AUTH_USER"] : $remote_user;
    $results = false;
    $cookie_results = false;
    if ($CI->config->item('enable_cookie_redirect') && !$remote_user) {
        $cookie_results = get_user_from_cookie();
        if ($cookie_results && is_array($cookie_results) && array_key_exists('eus_id', $cookie_results)) {
            $cookie_results['id'] = $cookie_results['eus_id'];
            $url_args_array = [
                "_id" => $cookie_results['eus_id']
            ];
        } else {
            return $results;
        }
    } elseif ($remote_user) {
        //check for email address as username
        $selector = filter_var($remote_user, FILTER_VALIDATE_EMAIL) ? 'email_address' : 'network_id';
        $url_args_array = [
            $selector => strtolower($remote_user)
        ];
    } else {
        return $results;
    }
    if (empty($url_args_array)) {
        return $results;
    }
    $query_url = "{$CI->nexus_backend_url}/get_nexus_user_id_for_identifier/";
    $query_url .= urlencode($remote_user);

    try {
        $options = ['verify' => false];
        $query = Requests::get($query_url, array('Accept' => 'application/json'), $options);
    } catch (Exception $e) {
        $results = [];
        return $results;
    }
    $results_body = $query->body;
    $results_json = json_decode($results_body, true);
    if ($cookie_results) {
        array_merge($results_json, $cookie_results);
    }
    if ($query->status_code == 200 && !empty($results_json)) {
        $results = $results_json['message']['user_id'];
    }
    return $results;
}

/**
 *  Properly formats the user returned in the ['REMOTE_USER']
 *  variable from OpenIDC
 *
 * @param integer $user_id The user_id to format
 *
 * @return array
 *
 * @author Ken Auberry <kenneth.auberry@pnnl.gov>
 */
function get_user_details_simple()
{
    $user_info = [
        'user_id' => strtolower($_SERVER['REMOTE_USER']) ?? false,
        'first_name' => $_SERVER['OIDC_CLAIM_given_name'] ?? 'Anonymous Stranger',
        'last_name' => $_SERVER['OIDC_CLAIM_family_name'] ?? '',
        'email' => strtolower($_SERVER['OIDC_CLAIM_email'] ?? false
    ];
    return $user_info;
}
