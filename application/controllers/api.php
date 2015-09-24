<?php
require_once('baseline_controller.php');

class API extends Baseline_controller {

  function __construct() {
    parent::__construct();
    $this->load->model('status_model','status');
    $this->load->model('API_model','api');
    $this->load->model('Cart_model','cart');
    $this->load->helper(array('inflector','item','url','opwhse_search','form','network'));
    $this->load->library(array('table'));
    $this->status_list = array(
      0 => 'Submitted', 1 => 'Received', 2 => 'Processing',
      3 => 'Verified', 4 => 'Stored', 5 => 'Available', 6 => 'Archived'
    );
  }
  
  /*
   * Expects alternating terms of field/value/field/value like...
   * <item_search/group.omics.dms.dataset_id/267771/group.omics.dms.instrument/ltq_4>
   */
  function item_search($search_operator = "AND"){
    //are we GET or POST?
    //check for POST body
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,true);
    
    if(empty($values)){
      //must be GET request
      if($this->uri->total_rsegments() % 2 == 0){
        //got an even number of segments, yields incomplete pairs
        return false;
      }
      $pairs = $this->uri->ruri_to_assoc(4);
      if(!$pairs){
        //return error message about not having anything to search for
        return false;
      }
    }else{
      //looks like a POST, parse the body and rock on
      $search_operator = array_key_exists('search_operator',$values) && !empty($values['search_operator']) ? $values['search_operator'] : "AND";
      $pairs = array_key_exists('search_terms',$values) ? $values['search_terms'] : array();
    }
    $results = !empty($pairs) ? $this->api->search_by_metadata($pairs) : array('transactions' => array(), 'result_count' => 0, 'metadata' => array());
    transmit_array_with_json_header($results);
  }
  
  
  
  
  
  /*
   * testing functions below this line
   */
  
  function test_get_available_group_types($filter = ""){
    $types = $this->api->get_available_group_types($filter);
    echo "<pre>";
    var_dump($types);
    echo "</pre>";
  }
  
  
}
?>