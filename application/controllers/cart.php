<?php
require_once('baseline_controller.php');

class Cart extends Baseline_controller {

  function __construct() {
    parent::__construct();
    $this->load->model('Cart_model','cart');
    $this->load->helper(array('url','network'));
  }
  
  public function get_cart_token(){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,TRUE);
    $item_list = $values['items'];
    echo generate_cart_token($item_list,$this->user_id);
  }
  
  
  public function listing($show_expired = false){
    $cart_list = $this->cart->get_active_carts($this->user_id, $show_expired);
    $this->load->view('cart_list_insert.html',array('carts' => $cart_list));
  }
  
  
  public function test_generate_cart_token(){
    $item_list = array(105655);
    echo generate_cart_token($item_list,$this->user_id);
  }
  
  public function test_get_cart_list(){
    echo "<pre>";
    var_dump($this->cart->get_active_carts($this->user_id, false));
    echo "</pre>";
  }
}