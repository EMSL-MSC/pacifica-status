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
 * PHP Version 5
 *
 * @package Pacifica-upload-status
 * @author  Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */

/**
 * Status API Model
 *
 * The **Status_api_model** performs most of the heavy lifting for the status site.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Status_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->local_timezone = 'US/Pacific';
        // $this->load->library('EUS', '', 'eus');
        $this->load->model('Myemsl_api_model', 'myemsl');
        $this->load->helper('item', 'network');

        $this->status_list = array(
            0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
            3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        );
        $this->load->library('PHPRequests');
    }

    public function get_transactions($instrument_id, $proposal_id, $start_time, $end_time, $submitter = -1)
    {
        $transactions_url = "{$this->policy_url_base}/status/transactions/search/details?";

        $url_args_array = array(
            'instrument' => $instrument_id,
            'proposal' => $proposal_id,
            'start' => $start_time,
            'end' => $end_time,
            'submitter' => $submitter,
            'requesting_user' => $this->user_id
        );
        $transactions_url .= http_build_query($url_args_array, '', '&');


        try {
            $query = Requests::get($transactions_url, array('Accept' => 'application/json'));
            $results = json_decode($query->body, TRUE);
        } catch (Exception $e) {
            $results = array();
        }
        return $results;
    }

    public function get_proposals_by_name($terms, $requester_id, $is_active = 'active'){
        $proposals_url = "{$this->policy_url_base}/status/proposals/search/{$terms}?";
        $url_args_array = array(
            'user' => $this->user_id
        );
        $proposals_url .= http_build_query($url_args_array, '', '&');

        try{
            $query = Requests::get($proposals_url, array('Accept' => 'application/json'));
            // var_dump($query);
            $results = json_decode($query->body, TRUE);
        } catch (Exception $e){
            $results = array();
        }
        return $results;
    }

}
