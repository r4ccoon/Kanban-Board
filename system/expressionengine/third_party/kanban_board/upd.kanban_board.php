<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Kanban Board for GitHub Projects Module Install/Update File
 *
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2014, EllisLab, Inc.
 * @license     https://github.com/EllisLab/Kanban-Board/blob/master/LICENSE.md
 * @link        https://github.com/EllisLab/Kanban-Board
 */

class Kanban_board_upd {

	public $version = '1.0.1';

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		ee()->db->insert('modules', array(
			'module_name'        => 'Kanban_board',
			'module_version'     => $this->version,
			'has_cp_backend'     => "y",
			'has_publish_fields' => 'n'
		));

		ee()->load->dbforge();
		ee()->dbforge->add_field(array(
			'member_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'github_token' => array('type' => 'varchar', 'constraint' => 64)
		));
		ee()->dbforge->add_key('member_id', TRUE);
		ee()->dbforge->create_table('kanban_board');

		return TRUE;
	}

	// ----------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
			->get_where('modules', array(
				'module_name' => 'Kanban_board'
			))->row('module_id');

		ee()->db->where('module_id', $mod_id)
			->delete('module_member_groups');

		ee()->db->where('module_name', 'Kanban_board')
			->delete('modules');

		ee()->load->dbforge();
		ee()->dbforge->drop_table('kanban_board');

		return TRUE;
	}

	// ----------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */
	public function update($current = '')
	{
		// If you have updates, drop 'em in here.
		return TRUE;
	}

}
/* End of file upd.kanban_board.php */
/* Location: /system/expressionengine/third_party/kanban_board/upd.kanban_board.php */
