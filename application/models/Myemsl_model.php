<?php

require_once APPPATH.'libraries/Requests.php';
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Myemsl_model                                                            */
/*                                                                             */
/*             functionality dealing with MyEMSL API Access calls, etc.        */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Myemsl_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('myemsl');
        Requests::register_autoloader();
        $this->myemsl_ini = read_myemsl_config_file('general');
    }

    public function get_proposals_by_search($filter_term){
        $DB_eus = $this->load->database('eus_for_myemsl',true);
        $DB_eus->like('search_field',lower($filter_term));
        $select_array = array(
            'id','display_name','category','abbreviation'
        );
        $DB_eus->select($select_array);
        $DB_eus->order_by('order_field');
        $query = $DB_eus->get('v_proposal_search');
        $results = array();
        if($query && $query->num_rows() > 0){
            foreach($query->result_array() as $row){
                $results[$row['id']] = $row;
            }
        }
        return $results;
    }

    public function get_instruments_by_proposal($proposal_id,$filter_term = false){
        $DB_eus = $this->load->database('eus_for_myemsl',true);
        if($filter_term){
            $DB_eus->like('i.search_field',strtolower($filter_term));
        }
        $select_array = array(
            'i.id','i.display_name','i.category','i.abbreviation'
        );
        $DB_eus->select($select_array);
        $DB_eus->order_by('order_field');
        $DB_eus->from('v_instrument_search i')->join('proposal_instruments pi','pi.instrument_id = i.id');
        $DB_eus->where('pi.proposal_id',$proposal_id);
        $query = $DB_eus->get();
        $results = array();
        if($query && $query->num_rows() > 0){
            foreach($query->result_array() as $row){
                $results[$row['id']] = $row;
            }
        }
        return $results;
    }

    public function get_user_info(){
        $DB_eus = $this->load->database('eus_for_myemsl', true);

        /*
        Get baseline userinfo from 'users' table
         */
        $select_array = array(
            'person_id','network_id','first_name','last_name',
            'email_address','last_change_date','emsl_employee'
        );
        $user_info = array();
        $query = $DB_eus->select($select_array)->get_where('users', array('person_id' => $this->user_id), 1);
        if($query && $query->num_rows() > 0){
            $user_info = $query->row_array();
        }

        $user_info['proposals'] = array();
        $user_info['instruments'] = array();

        /*
        Add in pertinent proposal data
         */
        $prop_select_array = array(
            'p.proposal_id','p.title','p.group_id','p.accepted_date','p.last_change_date','p.actual_end_date'
        );
        $DB_eus->select($prop_select_array);
        $DB_eus->from('proposals p')->join('proposal_members pm','p.proposal_id = pm.proposal_id');
        $prop_query = $DB_eus->where('pm.person_id',$this->user_id)->get();

        if($prop_query && $prop_query->num_rows() > 0){
            foreach($prop_query->result_array() as $row){
                $prop_id = $row['proposal_id'];
                unset($row['proposal_id']);
                $user_info['proposals'][$prop_id] = $row;
            }
        }

        $prop_id_list = array_map('strval', array_keys($user_info['proposals']));
        $inst_list = array();

        /*
        Get instrument info for proposals
         */
        $prop_inst_select_array = array(
            'instrument_id','proposal_id'
        );
        $DB_eus->select($prop_inst_select_array)->where_in('proposal_id', $prop_id_list);
        $prop_inst_query = $DB_eus->get('proposal_instruments');
        if($prop_inst_query && $prop_inst_query->num_rows() > 0){
            foreach($prop_inst_query->result() as $row){
                $user_info['proposals'][$row->proposal_id]['instruments'][] = $row->instrument_id;
                $inst_list[] = $row->instrument_id;
            }
            $inst_list = array_unique($inst_list);
        }

        $inst_query_select_array = array(
            'instrument_id','instrument_name','last_change_date','name_short',
            'eus_display_name','active_sw'
        );
        $DB_eus->select($inst_query_select_array)->where_in('instrument_id', $inst_list);
        $inst_query = $DB_eus->get_where('instruments', array('active_sw' => 'Y'));

        if($inst_query && $inst_query->num_rows() > 0){
            foreach($inst_query->result_array() as $row){
                $inst_id = $row['instrument_id'];
                unset($row['instrument_id']);
                $user_info['instruments'][$inst_id] = $row;
            }
        }
        return $user_info;

    }
}
