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
                'user', 'url', 'html', 'myemsl_api', 'file_info'
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
     public function index(){
        echo "index page";
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
        if(!$this->input->is_ajax_request()){
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

            echo "<pre>";
            var_dump($full_user_info);
            echo "</pre>";

        }
    }
}
