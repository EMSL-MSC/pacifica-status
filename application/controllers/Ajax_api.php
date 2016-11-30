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

class Ajax_api extends Baseline_api_controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('status_api_model','status');
		$this->load->model('myemsl_api_model','myemsl');
		$this->load->library('PHPRequests');
	}

	public function get_proposals_by_name($terms = FALSE)
    {
        $prop_list = $this->eus->get_proposals_by_name(
            $terms, $this->user_id, FALSE
        );
        $results = array(
            'total_count' => sizeof($prop_list),
            'incomplete_results' => FALSE,
            'more' => FALSE,
            'items' => array()
        );
        $max_text_len = 200;
        foreach($prop_list as $item){
            $textLength = strlen($item['title']);
            $result = substr_replace(
                $item['title'],
                '...',
                $max_text_len/2,
                $textLength-$max_text_len
            );

            $item['text'] = "<span title='{$item['title']}'>{$result}</span>";
            $results['items'][] = $item;
        }
        send_json_array($results);
    }

	public function get_instruments_for_proposal(
		$proposal_id = FALSE, $terms = FALSE
	)
	{
		if(!$proposal_id || empty($proposal_id)){
			//some kind of error callback
			return array();
		}
		$policy_url = "{$this->policy_url_base}/status/instrument/by_proposal_id/{$proposal_id}";
		$query = Requests::get($policy_url, array('Accept' => 'application/json'));
		// $results_body = $query->body;

		print($query->body);
	}

}
