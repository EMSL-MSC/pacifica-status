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
class Data_transfer_api_model extends CI_Model
{
    /**
     *  Class constructor
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();
        $this->config->load('data_release');
        $this->dh_username = getenv('DRHUB_USERNAME') ?: $this->config->item('drhub_username');
        $this->dh_password = getenv('DRHUB_PASSWORD') ?: $this->config->item('drhub_password');
        $this->load->helper(array('item', 'network', 'time'));
        $this->load->model('Status_api_model', 'status');
        $this->ds_table = 'drhub_data_sets';
        $this->dr_table = 'drhub_data_records';
        $this->sess = $this->get_drhub_session();
    }

    private function get_drhub_session(){
        $sess = new Requests_Session($this->drhub_url_base);
        $post_data = [
            'username' => $this->dh_username,
            'password' => $this->dh_password
        ];
        $headers = [
            'Accept' => 'application/json'
        ];
        $response = $sess->post("{$this->drhub_url_base}/dataset/user/login", $headers, $post_data);
        $response_object = json_decode($response->body);
        // var_dump($response_object);
        $sess->headers['X-CSRF-Token'] = $response_object->token;
        return $sess;
    }

    /**
     * This is going to need a fairly specific set of data to push this back out to DRHub
     *
     * @param  [type] $release_info [description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function publish_doi_externally($release_info, $dataset_id)
    {
        $url = $this->config->item('external_release_base_url');
        $publishing_skeleton = [
            'title' => $release_info['release_name'],
            'body' => $release_info['release_description'],
            'field_link_api' => '',  //release URL
            'field_format' => '',  //data format
            'field_ceii' => 1,  //holdover from DRPower, set to '1'
            'field_repository_name' => '',  //probably EMSL in this case
            'field_science_theme' => '',  //pull from proposal info
            'field_instrument_id' => '',  //pull from transaction info
            'field_instrument_name' => '',  //pull from instruments table
            'field_project_id' => '',  //pull from transaction info
            'field_project_name' => '',  //pull from proposals list
            'field_data_creator_name' => '',  //pull from user record
            'field_dataset_ref' => $dataset_id
        ];

        $stored_release_info = $this->get_release_info($release_info['release_id']);
        $url .= "released_data/{$stored_release_info['transaction_id']}";
        $stored_release_info['field_link_api'] = $url;
        $publishing_data = array_merge($publishing_skeleton, $stored_release_info);

        $resource_id = $this->create_new_data_resource($publishing_data);
        $success = link_resource_to_dataset($dataset_id, $release_info['release_name'], $resource_id);
        print(json_encode($output_data));
    }

    private function get_release_info($release_id)
    {
        $md_url = "{$this->metadata_url_base}/transaction_release?";
        $url_args_array = [
            '_id' => $release_id
        ];
        $result = [];
        $md_url .= http_build_query($url_args_array, '', '&');
        $query = Requests::get($md_url, ['Accept' => 'application/json']);
        $results = json_decode($query->body, true);
        if (!$results) {
            return $result;
        }
        $result = array_pop($results);
        $transaction_id = $result['transaction'];

        //now that we have a transaction, get transaction-level info
        $transaction_info = $this->status->get_formatted_transaction($transaction_id);
        $transaction_info = $transaction_info['transactions'][$transaction_id];
        $release_info = [
            'field_project_id' => $transaction_info['metadata']['proposal_id'],
            'field_instrument_id' => $transaction_info['metadata']['instrument_id'],
            'field_data_creator_name' => $this->user_info['display_name'],
            'transaction_id' => $transaction_id
        ];

        //get proposal_info
        $proposal_info = $this->lookup_external_info(
            $transaction_info['metadata']['proposal_id'],
            $transaction_info['metadata']['instrument_id']
        );

        $release_info = array_merge($release_info, $proposal_info);
        return $release_info;
    }

    private function lookup_external_info($proposal_id, $instrument_id)
    {
        $md_url = "{$this->metadata_url_base}/proposalinfo/by_proposal_id/{$proposal_id}";
        $query = Requests::get($md_url, ['Accept' => 'application/json']);
        $result = json_decode($query->body);

        $output = [
            'field_project_name' => $result->display_name,
            'field_instrument_name' => $result->instruments->$instrument_id->display_name,
            'field_data_creator_name' => $this->user_info['display_name'],
            'field_science_theme' => $result->science_theme,

        ];
        return $output;
    }

    /**
     * [get_release_states description]
     *
     * @return [type] [description]
     *
     * @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function get_release_states($transaction_list)
    {
        $md_url = "{$this->metadata_url_base}/transactioninfo/release_state";
        $query = Requests::post($md_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($transaction_list));
        return $query->body;
    }


    private function create_new_data_resource($publishing_data)
    {
        $lang = 'und';
        $formatted_request = [
            'title' => $publishing_data['title'],
            'body' => $publishing_data['body'],
            'type' => 'resource',
            'field_link_api' => [
                $lang => [
                    'attributes' => [],
                    'title' => $publishing_data['field_link_api'],
                    'url' => $publishing_data['field_link_api'],
                ]
            ],
            'og_user_permission_inheritance' => [
                $lang => [
                    [
                        'value' => 0
                    ]
                ]
            ]
        ];
        unset($publishing_data['field_dataset_ref']);
        unset($publishing_data['field_link_api']);
        foreach ($publishing_data as $name => $value) {
            if (substr($name, 0, 6) === "field_" && !empty($value)) {
                $formatted_request[$name] = [
                    $lang => [
                        ['value' => $value]
                    ]
                ];
            }
        }
        $dh_url = "{$this->drhub_url_base}/dataset/node";
        $success = false;
        $query = $this->sess->post($dh_url, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], json_encode($formatted_request));
        if ($query->status_code == 200) {
            $results = json_decode($query->body);
            if (array_key_exists('nid', $results)) {
                $resource_id = $results->nid;
                $success = $resource_id;
            }
        }
        return $success;
    }

    private function link_resource_to_dataset($dataset_id, $resource_title, $resource_id, $lang = "und")
    {
        $formatted_target = "{$resource_title} ({strval($resource_id)})";
        $formatted_request = [
            'field_resources' => [
                $lang => [
                    ['target_id' => $formatted_target]
                ]
            ]
        ];
        $dh_url = "{$this->drhub_url_base}/dataset/node/{$dataset_id}";
        $success = false;
        $query = $this->sess->put($dh_url, array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ), json_encode($formatted_request));
        if ($query->status_code == 200) {
            $results = json_decode($query->body);
            if ($results->nid == strval($dataset_id)) {
                $success = true;
            }
        }
        return $success;
    }

    public function store_transient_data_set($dataset_id, $title, $description="")
    {
        $success = false;
        if($this->drhub_node_exists($dataset_id)){
            $insert_data = [
                'node_id' => $dataset_id,
                'title' => $title
            ];
            if(!empty($description)){
                $insert_data['description'] = $description;
            }
            $check_query = $this->db->get_where($this->ds_table, ['node_id' => $dataset_id]);
            if(!$this->transient_record_exists($this->ds_table, $dataset_id)){
                $this->db->insert($this->ds_table, $insert_data);
                $success = boolval($this->db->affected_rows());
            }else{
                $success = true;
            }
        }
        return $success;
    }

    public function store_transient_data_record($record_id, $dataset_id, $access_url="")
    {
        $success = false;
        if($this->drhub_node_exists($record_id) && $this->drhub_node_exists($dataset_id)){
            $insert_data = [
                'node_id' => $record_id,
                'data_set_node_id' => $dataset_id
            ];
            if(!empty($access_url)){
                $insert_data['accessible_url'] = $access_url;
            }
            if(!$this->transient_record_exists($this->dr_table, $record_id)){
                $this->db->insert($this->dr_table, $insert_data);
                $success = boolval($this->db->affected_rows());
            }else{
                $success = true;
            }
        }
        return $success;
    }

    private function transient_record_exists($table_name, $record_id)
    {
        $check_query = $this->db->get_where($table_name, ['node_id' => $record_id]);
        return boolval($check_query->num_rows());
    }

    public function drhub_node_exists($data_set_id)
    {
        $dh_url = "{$this->drhub_url_base}/dataset/node/{$data_set_id}";
        // $sess = $this->get_drhub_session();
        $response = $this->sess->get($dh_url, ['Accept' => 'application/json']);
        $results = json_decode($response->body);
        return array_key_exists('body', $results);
    }
}
