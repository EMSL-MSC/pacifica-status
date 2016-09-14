<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     EUS_Library                                                             */
/*                                                                             */
/*             functionality for dealing with EUS supplied data                */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class EUS
{
    protected $CI;
    protected $myemsl_array;
    public function __construct()
    {
        $this->CI = &get_instance();

        define('INST_TABLE', 'instruments');
        define('INST_PROPOSAL_XREF', 'proposal_instruments');
        define('PROPOSALS_TABLE', 'proposals');
        define('PROPOSAL_MEMBERS', 'proposal_members');
        define('USERS_TABLE', 'users');

        $this->CI->load->database('eus_for_myemsl');
    }


    public function get_ers_instruments_list($unused_only = false, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);

        $select_array = array(
            'instrument_id',
            'instrument_name as instrument_description',
            'name_short as instrument_name_short',
            'COALESCE(`eus_display_name`,`instrument_name`) as display_name',
            'last_change_date as last_updated',
        );

        if (!empty($filter)) {
            //check for numeric only and use in instrument_id?
            $DB_ers->like('instrument_name', $filter);
            $DB_ers->or_like('name_short', $filter);
            $DB_ers->or_like('eus_display_name', $filter);
        }

        $DB_ers->select($select_array, true)->where('active_sw', 'Y');
        $DB_ers->order_by('eus_display_name');
        if ($unused_only) {}

        $query = $DB_ers->get(INST_TABLE);
        $results = array();
        $categorized_results = array();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $inst_id = $row['instrument_id'];
                unset($row['instrument_id']);
                $results[$inst_id] = $row;
                $display_name_info = explode(':', $row['display_name']);
                $display_name_type = sizeof($display_name_info) > 1 ? array_shift($display_name_info) : '';
                $row['display_name'] = trim($display_name_info[0]);
                $inst_info = explode(':', $row['instrument_description']);
                $inst_type = sizeof($inst_info) > 1 ? array_shift($inst_info) : 'Other';
                $inst_desc = trim($inst_info[0]);

                $row['instrument_description'] = $inst_desc;
                $categorized_results[$inst_type][$inst_id] = $row;
            }
        }

        return $categorized_results;
    }

    public function get_eus_user_list($filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);

        $select_array = array(
            'person_id as eus_id', 'first_name', 'last_name', 'email_address',
        );

        if (!empty($filter)) {
            $DB_ers->like('first_name', $filter)->or_like('last_name', $filter)->or_like('email_address', $filter);
        }

        $query = $DB_ers->select($select_array)->get(USERS_TABLE);

        $results_array = array(
            'success' => false,
            'message' => "No EUS users found using the filter '*{$filter}*'",
            'names' => array(),
        );

        if ($query && $query->num_rows() > 0) {
            $plural_mod = $query->num_rows() > 1 ? 's' : '';
            $results_array['message'] = $query->num_rows()." EUS user{$plural_mod} found with filter '*{$filter}*'";
            $results_array['success'] = true;
            foreach ($query->result() as $row) {
                $display_name = ucwords("{$row->first_name} {$row->last_name}");
                $display_name .= !empty($row->email_address) ? " <{$row->email_address}>" : '';
                $name_components = array(
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'email_address' => $row->email_address,
                );
                foreach (array_keys($name_components) as $key_name) {
                    $comp = $name_components[$key_name];
                    $comp = preg_replace("/(.*)({$filter})(.*)/i", '$1<span class="hilite">$2</span>$3', $comp);
                    $marked_components[$key_name] = $comp;
                }
                $marked_up_display_name = ucwords("{$marked_components['first_name']} {$marked_components['last_name']}");
                $marked_up_display_name .= !empty($name_components['email_address']) ? " <{$marked_components['email_address']}>" : '';

                $results_array['names'][$row->eus_id] = array(
                    'eus_id' => $row->eus_id,
                    'first_name' => ucfirst($row->first_name),
                    'last_name' => ucfirst($row->last_name),
                    'email_address' => $row->email_address,
                    'display_name' => $display_name,
                    'marked_up_display_name' => $marked_up_display_name,
                );
            }
        }

        return $results_array;
    }

    public function get_instruments_for_proposal($eus_proposal_id, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);

        $result_array = array(
            'success' => false,
            'message' => '',
            'instruments' => array(),
        );

        $closing_date = new DateTime();
        $closing_date->modify('-6 months');

    // print_r($closing_date);

        $where_array = array(
            'proposal_id' => $eus_proposal_id,
            'actual_end_date <' => $closing_date->format('Y-m-d'),
        );
        $DB_ers->where($where_array);

        $prop_exists = $DB_ers->count_all_results(PROPOSALS_TABLE) > 0 ? true : false;

        if (!$prop_exists) {
            $result_array['message'] = "No proposal with ID = {$eus_proposal_id} was found";

            return $result_array;
        }

        $instrument_list = array();

        $select_array = array(
            'i.instrument_id', 'i.eus_display_name',
        );

        $DB_ers->select($select_array)->from(INST_TABLE.' i');
        $DB_ers->join(INST_PROPOSAL_XREF.' pi', 'i.instrument_id = pi.instrument_id');

        if (!empty($filter)) {
            $filter = urldecode($filter);
            $filter_terms = explode(' ',$filter);
            foreach($filter_terms as $term){
                $DB_ers->like('LOWER(i.eus_display_name)', strtolower($term));
            }
        }

        $inst_query = $DB_ers->get();

        if ($inst_query && $inst_query->num_rows() > 0) {
            $plural_mod = $inst_query->num_rows() > 1 ? 's' : '';
            $result_array['success'] = true;
            $result_array['message'] = $inst_query->num_rows()." instrument{$plural_mod} located for proposal {$eus_proposal_id}";
            foreach ($inst_query->result() as $row) {
                $result_array['instruments'][$row->instrument_id] = $row->eus_display_name;
            }
        } else {
            $result_array['message'] = "No instruments located for proposal {$eus_proposal_id}";
        }

        return $result_array;
    }

    public function get_proposals_for_instrument($eus_instrument_id, $filter = '')
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);

        //check that instrument_id is legal and active
        $where_array = array(
            'active_sw' => 'Y',
            'instrument_id' => $eus_instrument_id,
        );
        $inst_exists = $DB_ers->where($where_array)->count_all_results(INST_TABLE) > 0 ? true : false;

        $result_array = array('success' => false);

        if (!$inst_exists) {
            $result_array['message'] = 'No instrument with ID = '.$eus_instrument_id.' was found';
            $result_array['proposals'] = array();

            return $result_array;
        }
        $today = new DateTime();

        $select_array = array('pi.proposal_id', 'p.title as proposal_name');
        $DB_ers->select($select_array)->where('pi.instrument_id', $eus_instrument_id)->order_by('p.title');
        $DB_ers->where('p.closed_date is null')->where('p.actual_end_date >=', $today->format('Y-m-d'));
        $DB_ers->from(INST_PROPOSAL_XREF.' as pi');
        $DB_ers->join(PROPOSALS_TABLE.' as p', 'p.proposal_id = pi.proposal_id');

        if (!empty($filter)) {
            $filter = urldecode($filter);
            $filter_terms = explode(' ',$filter);
            foreach($filter_terms as $term){
                $DB_ers->like('LOWER(p.title)', strtolower($term));
            }
        }

        $proposal_query = $DB_ers->get();

        $proposal_list = array();
        if ($proposal_query && $proposal_query->num_rows() > 0) {
            $plural_mod = $proposal_query->num_rows > 1 ? 's' : '';
            $result_array['success'] = true;
            $result_array['message'] = $proposal_query->num_rows()." proposal{$plural_mod} located for instrument {$eus_instrument_id}";
            foreach ($proposal_query->result() as $row) {
                $clean_proposal_name = trim(str_replace("\n", ' ', $row->proposal_name));
                $proposal_list[$row->proposal_id] = $clean_proposal_name;
            }
        } else {
            $result_array['message'] = 'No proposals located for instrument '.$eus_instrument_id;
        }
        $result_array['items'] = $proposal_list;

        return $result_array;
    }

    public function get_proposal_name($eus_proposal_id)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        $query = $DB_ers->select('title as proposal_name')->get_where(PROPOSALS_TABLE, array('proposal_id' => strval($eus_proposal_id)), 1);
        if ($query && $query->num_rows() > 0) {
            $result = $query->row()->proposal_name;
        }

        return $result;
    }

    public function get_object_list($object_type, $search_terms = false, $my_objects = false)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        if ($my_objects) {
            $DB_ers->where_in('id', array_map('strval', array_keys($my_objects)));
        }
        if ($search_terms && !empty($search_terms)) {
            foreach ($search_terms as $search_term) {
                $DB_ers->or_like('search_field', $search_term);
            }
        }

        $DB_ers->order_by('order_field');
        $query = $DB_ers->get("v_{$object_type}_search");
        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $results[$row['id']] = $row;
            }
        }

        return $results;
    }

    public function get_object_info($object_id_list, $object_type)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        $DB_ers->where_in('id', $object_id_list);
        $query = $DB_ers->get("v_{$object_type}_search");

        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $results[$row['id']] = $row;
            }
        }

        return $results;
    }

    public function get_name_from_eus_id($eus_id)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        $select_array = array(
            'person_id as eus_id', 'first_name', 'last_name', 'email_address',
        );
        $result = array();
        $query = $DB_ers->select($select_array)->get_where(USERS_TABLE, array('person_id' => $eus_id), 1);
        if ($query && $query->num_rows() > 0) {
            $result = $query->row_array();
            $result['display_name'] = "{$result['last_name']}, {$result['first_name']}";
        }

        return $result;
    }

    public function get_proposals_for_user($eus_user_id)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        $select_array = array('proposal_id');
        $DB_ers->select($select_array)->where('active', 'Y');
        $DB_ers->where('person_id',$eus_user_id)->distinct();
        $query = $DB_ers->get(PROPOSAL_MEMBERS);

        $results = array();
        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $results[] = $row->proposal_id;
            }
        }

        return $results;
    }

    public function get_proposals_by_name($proposal_name_fragment, $eus_id, $is_active = 'active')
    {
        $my_proposals = $this->get_proposals_for_user($eus_id);
        $DB_eus = $this->CI->load->database('eus_for_myemsl', true);
        $DB_eus->select(array(
            'p.proposal_id', 'p.title', 'p.group_id',
            'p.actual_start_date as start_date',
            'p.actual_end_date as end_date')
        );
        $DB_eus->from('proposals p');
        $DB_eus->join('v_proposal_search vs', 'vs.id = p.proposal_id');
        $DB_eus->where('p.closed_date');
        $DB_eus->where('p.actual_start_date is not null');

        if (!empty($proposal_name_fragment)) {
            $filter = urldecode($proposal_name_fragment);
            $filter_terms = explode(' ',$filter);
            foreach($filter_terms as $term){
                $DB_eus->like('vs.search_field', strtolower($term));
            }
            if(!$this->CI->is_emsl_staff){
                $DB_eus->where_in('p.proposal_id',$my_proposals);
            }
        }else{
            $DB_eus->where_in('p.proposal_id',$my_proposals);
        }

        $query = $DB_eus->get();
        $results = array();

        if ($query && $query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $start_date = strtotime($row->start_date) ? date_create($row->start_date) : false;
                $end_date = strtotime($row->end_date) ? date_create($row->end_date) : false;
                $state = 'inactive';
                $currently_active = $start_date && $start_date->getTimestamp() < time() ? true : false;
                $state = $currently_active ? 'active' : 'preactive';
                $currently_active = $state == 'active' && (!$end_date || $end_date->getTimestamp() >= time()) ? true : false;
                // $state = $currently_active ? 'active' : 'inactive';
                $state = !$start_date || !$end_date ? 'invalid' : $state;

                if ($is_active == 'active' && !$currently_active) {
                    continue;
                }

                $results[$row->proposal_id] = array(
                    'id' => $row->proposal_id,
                    'title' => trim($row->title, '.'),
                    'currently_active' => $currently_active ? 'yes' : 'no',
                    'state' => $state,
                    'start_date' => $start_date ? $start_date->format('Y-m-d') : '---',
                    'end_date' => $end_date ? $end_date->format('Y-m-d') : '---',
                    'group_id' => $row->group_id,
                );
            }
        }

        return $results;
    }

    public function get_instrument_name($eus_instrument_id)
    {
        $DB_ers = $this->CI->load->database('eus_for_myemsl', true);
        $select_array = array('eus_display_name as display_name', 'instrument_name', 'name_short as short_name', 'instrument_id');
        $query = $DB_ers->select($select_array)->get_where(INST_TABLE, array('instrument_id' => $eus_instrument_id), 1);
        $results = array();
        if ($query && $query->num_rows()) {
            $results = $query->row_array();
        }

        return $results;
    }
}
