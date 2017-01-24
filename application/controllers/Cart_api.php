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
 * Cart is a CI Controller class that extends Baseline_controller
 *
 * The *Cart* class interacts with the MyEMSL Cart web API to
 * allow download of archived data, as well as generating proper
 * cart_token entities to allow for multi-file download specifications.
 *
 * @category Class
 * @package  Pacifica-upload-status
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Cart_api extends Baseline_api_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cart_api_model', 'cart');
        $this->load->helper(array('url', 'network'));
    }

    /**
     * Retrieve the list of active carts owned by this user
     *
     * @return void sends out JSON text to browser
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function listing()
    {
        $cart_list = $this->cart->get_active_carts();
    }

    /**
     *  Create a new download cart
     *
     *  @return void
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function create()
    {
        $req_method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : "GET";
        if($req_method != "POST") {
            //return info on how to use this function
            echo "That's not how you use this function!!!";
            exit();
        }
        $submit_block = json_decode($this->input->raw_input_stream, TRUE);
        if(empty($submit_block)) {
            //bad json-block or empty post body
            echo "Hey! There's no real data here!";
        }
        // var_dump($this->input->request_headers());
        $this->cart->cart_create($this->input->raw_input_stream);

    }


}
