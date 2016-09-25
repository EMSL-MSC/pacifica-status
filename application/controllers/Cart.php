<?php
/**
 * Controllers Cart
 *
 * PHP Version 5
 *
 * @category Controllers
 * @package  Cart
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
require_once 'Baseline_controller.php';

/**
 * Cart controller class
 * 
 * @category Class
 * @package  Cart
 * @author   Ken Auberry  <Kenneth.Auberry@pnnl.gov>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://github.com/EMSL-MSC/pacifica-upload-status
 */
class Cart extends Baseline_controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cart_model', 'cart');
        $this->load->helper(array('url', 'network'));
    }

    /**
     * Get a cart authentication token.
     * 
     * @param int $item_id single itemid to get a cart for.
     * 
     * @return void
     */
    public function get_cart_token($item_id = FALSE)
    {
        $HTTP_RAW_POST_DATA = file_get_contents('php://input');
        $values = json_decode($HTTP_RAW_POST_DATA, TRUE);
        if (empty($values) && $item_id) {
            $item_list = array($item_id);
        } else {
            $item_list = $values['items'];
        }
        echo generate_cart_token($item_list, $this->user_id);
    }

    /**
     * List the active carts
     * 
     * @param string $optional_message optional message to send to user.
     * 
     * @return void
     */
    public function listing($optional_message = '')
    {
        $cart_list = $this->cart->get_active_carts($this->user_id);
        $cart_list['optional_message'] = $optional_message;
        $this->load->view('cart_list_insert.html', array('carts' => $cart_list));
    }

    /**
     * Delete a cart based on ID
     * 
     * @param int $cart_id cart ID to delete
     * 
     * @return void
     */
    public function delete($cart_id)
    {
        $success_info = $this->cart->delete_dead_cart($cart_id);
        $this->listing($success_info['message']);
    }

    /**
     * Test cart token method.
     * 
     * @return void
     */
    public function test_generate_cart_token()
    {
        $item_list = array(105655);
        echo $this->get_cart_token(105655);
    }

    /**
     * Test cart listing method
     * 
     * @return void
     */
    public function test_get_cart_list()
    {
        echo '<pre>';
        var_dump($this->cart->get_active_carts($this->user_id, FALSE));
        echo '</pre>';
    }
}
