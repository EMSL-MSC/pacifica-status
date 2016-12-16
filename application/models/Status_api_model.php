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
            'instrument' => isset($instrument_id) ? $instrument_id : -1,
            'proposal' => isset($proposal_id) ? $proposal_id : -1,
            'start' => $start_time,
            'end' => $end_time,
            'submitter' => isset($submitter) ? $submitter : -1,
            'requesting_user' => $this->user_id
        );
        $transactions_url .= http_build_query($url_args_array, '', '&');
        // try {
            $query = Requests::get($transactions_url, array('Accept' => 'application/json'));
            $results = json_decode($query->body, TRUE);
        // } catch (Exception $e) {
        //     $results = array();
        // }
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


    /**
     *  Return the list of files and their associated metadata
     *  for a given transaction id
     *
     *  @param integer $transaction_id The transaction to pull
     *
     *  @return [type]   [description]
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_files_for_transaction($transaction_id)
    {
        $files_url = "{$this->policy_url_base}/status/transactions/files/{$transaction_id}?";
        $url_args_array = array(
            'user' => $this->user_id
        );
        $files_url .= http_build_query($url_args_array, '', '&');
        $results = array();
        // try{
            $query = Requests::get($files_url, array('Accept' => 'application/json'));
            if($query->status_code / 100 == 2){
                $results = json_decode($query->body, TRUE);
            }
        // } catch (Exception $e){
        //     $results = array();
        // }

        if ($results && !empty($results) > 0) {
            $dirs = array();
            foreach ($results as $item_id => $item_info) {
                $subdir = preg_replace('|^proposal\s[^/]+/[^/]+/\d{4}\.\d{1,2}\.\d{1,2}/?|i', '', trim($item_info['subdir'],'/'));
                $filename = $item_info['name'];
                $path = !empty($subdir) ? "{$subdir}/{$filename}" : $filename;
                $path_array = explode('/', $path);
                build_folder_structure($dirs, $path_array, $item_info);
            }

            return array('treelist' => $dirs, 'files' => $results);
        }


        // $DB_metadata = $this->load->database('default', TRUE);
        //
        // if($DB_metadata->dbdriver != 'sqlite3') {
        //     $file_select_array = array(
        //         'f.item_id',
        //         'f.name',
        //         'f.subdir',
        //         "DATE_TRUNC('second',t.stime) AT TIME ZONE 'US/Pacific' as stime",
        //         'f.mtime as modified_time',
        //         'f.ctime as created_time',
        //         'f."transaction"',
        //         'f.size',
        //     );
        // }else{
        //     $file_select_array = array(
        //         'f.item_id',
        //         'f.name',
        //         'f.subdir',
        //         "DATE_TRUNC('second',t.stime) as stime",
        //         'f.mtime as modified_time',
        //         'f.ctime as created_time',
        //         'f."transaction"',
        //         'f.size',
        //     );
        // }
        //
        // $DB_metadata->trans_start();
        // if($DB_metadata->dbdriver != 'sqlite3') {
        //     $DB_metadata->query("set local timezone to '{$this->local_timezone}';");
        // }
        // $DB_metadata->select($file_select_array)->from('transactions t')->join('files f', 't."transaction" = f."transaction"');
        // $DB_metadata->where('f."transaction"', $transaction_id);
        // $DB_metadata->order_by('f.subdir, f.name');
        // $files_query = $DB_metadata->get();
        // $DB_metadata->trans_complete();
        // $files_list = array();

        // if ($files_query && $files_query->num_rows() > 0) {
        //     foreach ($files_query->result_array() as $row) {
        //         $files_list[$row['item_id']] = $row;
        //     }
        //     $file_tree = array();
        //
        //     $dirs = array();
        //     foreach ($files_list as $item_id => $item_info) {
        //         $subdir = preg_replace('|^proposal\s[^/]+/[^/]+/\d{4}\.\d{1,2}\.\d{1,2}/?|i', '', $item_info['subdir']);
        //         $filename = $item_info['name'];
        //         $path = !empty($subdir) ? "{$subdir}/{$filename}" : $filename;
        //         $path_array = explode('/', $path);
        //         build_folder_structure($dirs, $path_array, $item_info);
        //     }
        //
        //     return array('treelist' => $dirs, 'files' => $files_list);
        // }
    }


}
