<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Kanban Board for GitHub Projects Module Control Panel File
 *
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2014, EllisLab, Inc.
 * @license     https://github.com/EllisLab/Kanban-Board/blob/master/LICENSE.md
 * @link        https://github.com/EllisLab/Kanban-Board
 */

class Kanban_board_mcp {

	public $return_data;

	private $_base_url;

	public function __construct()
	{
		$this->_base_form = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=kanban_board';
		// $this->_base_url = cp_url('addons_modules/show_module_cp', array('module' => 'kanban_board'));
		$this->_base_url = BASE.AMP.$this->_base_form;;
	}

	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return string The rendered view for the CP
	 */
	public function index()
	{
		ee()->load->library('table');
		ee()->view->cp_page_title = lang('kanban_board_module_name');

		$data['settings'] = array(
			'kanban_board_client_id' =>
				ee()->input->post('kanban_board_client_id', TRUE)
				?: ee()->config->item('kanban_board_client_id'),
			'kanban_board_client_secret' =>
				ee()->input->post('kanban_board_client_secret', TRUE)
				?: ee()->config->item('kanban_board_client_secret'),
		);

		if (ee()->input->post('submit'))
		{
			ee()->config->_update_config($data['settings']);
		}

		$data['base_form'] = $this->_base_form;

		return ee()->load->view('index', $data, TRUE);
	}
}
/* End of file mcp.kanban_board.php */
/* Location: /system/expressionengine/third_party/kanban_board/mcp.kanban_board.php */
