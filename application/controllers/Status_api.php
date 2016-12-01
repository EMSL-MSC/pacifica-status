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
require_once 'Baseline_api_controller.php';

/**
 * Status API is a CI Controller class that extends Baseline_controller
 *
 * The *Status API* class is the main entry point into the status
 * website. It provides overview pages that summarize a filtered
 * set of all uploads, as well as a single-transaction view
 * that shows the status of a specified upload transaction
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Status_api extends Baseline_api_controller
{
    /**
     * Constructor
     *
     * Defines the base set of scripts/CSS files for every
     * page load
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Status_api_model', 'status');
        $this->load->model('Myemsl_api_model', 'myemsl');
        // $this->load->model('Cart_model', 'cart');
        $this->load->helper(
            array(
                'url', 'html', 'myemsl_api', 'file_info'
            )
        );

        $this->load->helper(
            array(
                'inflector', 'item', 'form', 'network', 'cookie'
            )
        );
        $this->load->library(array('table'));
        $this->status_list = array(
          0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
          3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived',
        );

        $this->last_update_time = get_last_update(APPPATH);

        $this->page_data['script_uris'] = array(
            '/resources/scripts/spinner/spin.min.js',
            '/resources/scripts/fancytree/jquery.fancytree-all.js',
            '/resources/scripts/jquery-crypt/jquery.crypt.js',
            '/resources/scripts/myemsl_file_download.js',
            '/project_resources/scripts/status_common.js',
            '/resources/scripts/select2-4/dist/js/select2.js',
            '/resources/scripts/moment.min.js'
        );
        $this->page_data['css_uris'] = array(
            '/resources/scripts/fancytree/skin-lion/ui.fancytree.min.css',
            '/resources/stylesheets/status.css',
            '/resources/stylesheets/status_style.css',
            '/resources/scripts/select2-4/dist/css/select2.css',
            '/resources/stylesheets/file_directory_styling.css',
            '/resources/stylesheets/bread_crumbs.css',
        );

    }

    /**
     * Primary index redirect method.
     *
     * @return void
     */
    public function index()
    {
        redirect('status_api/overview');
    }

    /**
     * Primary index page shows overview of status for that user.
     *
     * @param string $proposal_id   id of the proposal to display
     * @param string $instrument_id id of the instrument to display
     * @param string $time_period   time period the status should be displayed
     *
     * @return void
     */
    public function overview(
        $proposal_id = FALSE,
        $instrument_id = FALSE,
        $time_period = FALSE
    )
    {
        $proposal_id = $proposal_id ?: get_cookie('last_proposal_selector');
        $instrument_id = $instrument_id ?: get_cookie('last_instrument_selector');
        $time_period = $time_period ?: get_cookie('last_timeframe_selector');

        //add in the page display defaults, etc. if a non-AJAX load
        if(!$this->input->is_ajax_request()) {
            $view_name = 'emsl_mgmt_view.html';
            $this->page_data['page_header'] = 'MyEMSL Status Reporting';
            $this->page_data['title'] = 'Overview';
            $this->page_data['informational_message'] = '';
            $this->page_data['css_uris']
                = array_merge(
                    $this->page_data['css_uris'], array(
                    '/project_resources/stylesheets/selector.css'
                    )
                );
            $this->page_data['script_uris']
                = array_merge(
                    $this->page_data['script_uris'], array(
                    '/resources/scripts/emsl_mgmt_view.js'
                    )
                );

            $this->benchmark->mark('get_user_info_from_ws_start');
            $full_user_info = $this->myemsl->get_user_info();
            $this->benchmark->mark('get_user_info_from_ws_end');

            $proposal_list = array();
            if (array_key_exists('proposals', $full_user_info)) {
                foreach ($full_user_info['proposals'] as $prop_id => $prop_info) {
                    if (array_key_exists('title', $prop_info)) {
                        $proposal_list[$prop_id] = $prop_info['title'];
                    }
                }
            }
            krsort($proposal_list);
            $js = "var initial_proposal_id = '{$proposal_id}';
                    var initial_instrument_id = '{$instrument_id}';
                    var initial_time_period = '{$time_period}';
                    var email_address = '{$this->email}';
                    var lookup_type = 't';
                    var initial_instrument_list = [];";

            $this->page_data['proposal_list'] = $proposal_list;

            $this->page_data['load_prototype'] = FALSE;
            $this->page_data['load_jquery'] = TRUE;
            $this->page_data['selected_proposal'] = $proposal_id;
            $this->page_data['time_period'] = $time_period;
            $this->page_data['instrument_id'] = $instrument_id;
            $this->page_data['js'] = $js;
        } else {
            $view_name = 'upload_item_view.html';
        }
        if (isset($instrument_id) && isset($time_period) && $time_period > 0) {
            // $inst_lookup_id = $instrument_id >= 0 ? $instrument_id : "";
            // $group_lookup_list
            //     = $this->status->get_instrument_group_list($instrument_id);
            if (isset($instrument_id) && isset($proposal_id)) {
                // $results = $this->status->get_transactions_for_group(
                //     array_keys($group_lookup_list['by_inst_id'][$instrument_id]),
                //     $time_period,
                //     $proposal_id
                // );
                $results = $this->status->get_transactions_for_instrument_proposal($instrument_id,$proposal_id);
            } elseif ($instrument_id <= 0) {
                //this should be the "all instruments" trigger
                //  get all the instruments for this proposal

                $results = array(
                    'transaction_list' => array(),
                    'time_period_empty' => FALSE,
                    'message' => '',
                );
                foreach (
                    $group_lookup_list['by_inst_id'] as $inst_id => $group_id_list
                ) {
                    $transaction_list
                        = $this->status->get_transactions_for_group(
                            array_keys($group_id_list),
                            $time_period,
                            $proposal_id
                        );
                    if (!empty($transaction_list['transaction_list'])) {
                        foreach (
                            $transaction_list['transaction_list']['transactions']
                            as $group_id => $group_info
                        ) {
                            if(!array_key_exists(
                                'transactions',
                                $results['transaction_list']
                            )
                            ) {
                                $results['transaction_list']
                                    ['transactions'] = array();
                            }
                            if (!array_key_exists(
                                $group_id,
                                $results['transaction_list']['transactions']
                            )
                            ) {
                                $results['transaction_list']
                                    ['transactions'][$group_id] = $group_info;
                            }
                        }
                    }
                    if (!empty($transaction_list['transaction_list']['times'])) {
                        foreach (
                            $transaction_list['transaction_list']['times']
                            as $ts => $tx_id) {
                            if(!array_key_exists(
                                'times',
                                $results['transaction_list']
                            )
                            ) {
                                $results['transaction_list']['times'] = array();
                            }
                            if(!array_key_exists(
                                $ts,
                                $results['transaction_list']['times']
                            )
                            ) {
                                $results['transaction_list']['times']
                                    [$ts] = $tx_id;
                            }
                        }
                    }
                }
            } else {
                $results = array(
                    'transaction_list' => array(),
                    'time_period_empty' => TRUE,
                    'message' => 'No data uploaded for this instrument',
                );
            }
        } else {
            $results = array(
                'transaction_list' => array(),
                'time_period_empty' => TRUE,
                'message' => 'No data uploaded for this instrument',
            );
        }
        // $this->page_data['cart_data'] = array(
        //     'carts' => $this->cart->get_active_carts($this->user_id, FALSE)
        // );
        $this->page_data['cart_data'] = array('carts' => array());
        if(!empty($results) && array_key_exists('transaction_list', $results)) {
            if(array_key_exists('transactions', $results['transaction_list'])) {
                krsort($results['transaction_list']['transactions']);
            }
            if(array_key_exists('times', $results['transaction_list'])) {
                krsort($results['transaction_list']['times']);
            }
        }
        $this->page_data['enable_breadcrumbs'] = FALSE;
        $this->page_data['status_list'] = $this->status_list;
        $this->page_data['transaction_data'] = $results['transaction_list'];
        if (array_key_exists('transactions', $results['transaction_list'])
            && !empty($results['transaction_list']['transactions'])
        ) {
            $this->page_data['transaction_sizes']
                = $this->status->get_total_size_for_transactions(
                    array_keys($results['transaction_list']['transactions'])
                );
        } else {
            $this->page_data['transaction_sizes'] = array();
        }
        $this->page_data['informational_message'] = $results['message'];
        $this->page_data['request_type'] = 't';

        $this->load->view($view_name, $this->page_data);

    }
}
