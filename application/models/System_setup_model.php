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

 // if (!is_cli()) exit('No URL-based access allowed');

/**
 * System setup model
 *
 * The **System_setup_model** configures the database backend and gets the
 * underlying system architecture in place during deployment.
 *
 * @category CI_Model
 * @package  Pacifica-upload-status
 * @author   Ken Auberry <kenneth.auberry@pnnl.gov>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link    http://github.com/EMSL-MSC/pacifica-upload-status
 */
class System_setup_model extends CI_Model{
    /**
     *  Class constructor.
     *
     *  @author Ken Auberry <kenneth.auberry@pnnl.gov>
     */
    public function __construct()
    {
        parent::__construct();

        $this->statusdb_name = 'pacifica_upload_status';
        $this->global_try_count = 0;
    }

    private function _check_and_create_database($db_name){
        $this->load->database('init_postgres');
        $this->load->dbutil();
        $this->load->dbforge();

        if(!$this->dbutil->database_exists($db_name)){
            //db doesn't already exist, so make it
            if($this->dbforge->create_database($db_name)){
                echo "Created {$db_name} database instance" . PHP_EOL;
            }else{
                die("Could not create the {$this->statusdb_name} database instance");
            }
        }
        $this->db->close();
    }

    public function setup_db_structure(){
        //check for database existence

        $this->_check_and_create_database($this->statusdb_name);

        $this->load->database('default');
        $this->load->dbforge();
        $this->load->dbutil();

        //ok, the database should be there now. Let's make some tables
        $cart_fields = array(
            'cart_uuid' => array(
                'type' => 'VARCHAR',
                'constraint' => '64',
                'unique' => TRUE
            ),
            'name' => array(
                'type' => 'VARCHAR'
            ),
            'description' => array(
                'type' => 'VARCHAR',
                'null' => TRUE
            ),
            'owner' => array(
                'type' => 'INT'
            ),
            'json_submission' => array(
                'type' => 'json'
            ),
            'created' => array(
                'type' => 'TIMESTAMP',
                'default' => 'now()'
            ),
            'updated' => array(
                'type' => 'TIMESTAMP'
            ),
            'deleted' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE
            )
        );

        if(!$this->db->table_exists('cart')){
            $this->dbforge->add_field($cart_fields);
            $this->dbforge->add_key('cart_uuid', TRUE);
            if($this->dbforge->create_table('cart')){
                echo "Created 'cart' table..." . PHP_EOL;
            };
        }

        $cart_items_fields = array(
            'id' => array(
                'type' => 'NUMERIC',
                'auto_increment' => TRUE
            ),
            'file_id' => array(
                'type' => 'BIGINT'
            ),
            'cart_uuid' => array(
                'type' => 'VARCHAR',
                'constraint' => 64
            ),
            'relative_local_path' => array(
                'type' => 'VARCHAR'
            ),
            'file_size_bytes' => array(
                'type' => 'BIGINT'
            ),
            'file_mime_type' => array(
                'type' => 'VARCHAR',
                'null' => TRUE
            )
        );

        if(!$this->db->table_exists('cart_items')){
            $this->dbforge->add_field($cart_items_fields);
            $this->dbforge->add_key(array('file_id', 'cart_uuid'), TRUE);
            if($this->dbforge->create_table('cart_items')){
                echo "Created 'cart_items' table..." . PHP_EOL;
            };

        }
    }

}
