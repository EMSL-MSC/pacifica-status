<?php
/**
 * Pacifica
 *
 * Pacifica is an open-source data management framework designed
 * for the curation and storage of raw and processed scientific
 * data. It is based on the [CodeIgniter web framework](http://codeigniter.com).
 *
 *  The Pacifica-upload-status module provides an interface to
 *  the ingester status reporting backend, allowing users to view,
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
 * Cart API Model
 *
 * The **Cart_api_model** talks to the cart daemon on the backend to make
 * and retrieve carts and files.
 *
 * Cart submission object needs to contain...
 *  - name (string): A descriptive name for the cart
 *  - description (optional, string): optional extended description
 *  - files (array): list of file IDs and corresponding paths to pull
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Cart_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database('default');
        $this->load->library('PHPRequests');
    }

    /**
     *  Generates the an ID for the cart, then makes the appropriate entries
     *  in the cart status database
     *
     *  @param array $cart_submission_object Cart request JSON, converted to array
     *  @param array $request_info           Apache request object data
     *
     *  @return string  cart_uuid
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function cart_create($cart_submission_json, $request_info)
    {
        $cart_submission_object = $this->clean_cart_submission($cart_submission_json);
        $cart_uuid = $this->generate_cart_uuid($cart_submission_object);
        $this->create_cart_entry($cart_uuid, $cart_submission_object);

    }

    public function cart_status($cart_uuid_list, $request_info)
    {

    }

    public function cart_retrieve($cart_uuid, $request_info)
    {

    }

    public function cart_delete($cart_uuid)
    {

    }

    public function update_cart_info($cart_uuid, $update_object)
    {

    }

    /**
     *  Takes the submitted JSON string from the request, cleans it up, and
     *  verifies that all the entries that it needs are present. Returns
     *  the object as an array, or FALSE if invalid.
     *
     *  @param    string   $cart_submission_json   Originally submitted cart request JSON
     *
     *  @return   array   cleaned up cart submission object
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    private function clean_cart_submission($cart_submission_json){
        $raw_object = json_decode($cart_submission_json, TRUE);
        $description = array_key_exists('description', $raw_object) ? $raw_object['description'] : "";
        $name = array_key_exists('name', $raw_object) ? $raw_object['name'] : FALSE;
        $file_list = array_key_exists('files', $raw_object) ? $raw_object['files'] : FALSE;
        $submission_timestamp = new DateTime();
        if(!$name || !$file_list){
            //throw an error, as this is an incomplete cart object
        }

        $cleaned_object = array(
            'name' => $name,
            'description' => $description,
            'files' => $this->check_and_clean_file_list($file_list),
            'submitter' => $this->user_id,
            'submission_timestamp' => $submission_timestamp->getTimestamp()
        );

        return $cleaned_object;
    }

    private function check_and_clean_file_list($file_id_list){

    }

    private function generate_cart_uuid($cart_submission_object)
    {
        $clean_cart_string = json_encode($cart_submission_object);
        return hash('sha256', $clean_cart_string);
    }

    private function create_cart_entry($cart_uuid, $cart_submission_object, $file_details)
    {
        $this->db->trans_start();

        $insert_data = array(
            'cart_uuid' => strtolower($cart_uuid),
            'name' => $cart_submission_object['name'],
            'owner' => $cart_submission_object['submitter'],
            'json_submission' => json_encode($cart_submission_object)
        );
        if(array_key_exists('description', $cart_submission_object) && !empty($cart_submission_object['description'])){
            $insert_data['description'] = $cart_submission_object['description'];
        }
        $this->db->insert('cart', $insert_data);

        $file_insert_data = array();
        foreach($file_details as $file_id => $file_info){
            $file_insert_data[] = array(
                'file_id' => $file_id,
                'cart_uuid' => $cart_uuid,
                'relative_local_path' => "{$file_info['filepath']}/{$file_info['filename']}",
                'file_size_bytes' => $file_info
            )
        }
        $this->db->trans_complete();

        if($this->db->trans_status() === FALSE){
            //error thrown during db insert
        }
    }

    private function update_cart_download_stats($cart_uuid, $request_info)
    {

    }




}
