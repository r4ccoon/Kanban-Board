<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'vendor/autoload.php';

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

/**
 * Kanban Board for GitHub Projects Module Front End File
 *
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2014, EllisLab, Inc.
 * @license     https://github.com/EllisLab/Kanban-Board/blob/master/LICENSE.md
 * @link        https://github.com/EllisLab/Kanban-Board
 */

class Kanban_board {

	public $return_data;

	private $account       = '';
	private $repositories  = array();
	private $client_id     = '';
	private $client_secret = '';
	private $token         = FALSE;
	private $client        = FALSE;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->client_id     = ee()->config->item('kanban_board_client_id');
		$this->client_secret = ee()->config->item('kanban_board_client_secret');

		$this->account       = ee()->TMPL->fetch_param('account');
		$this->repositories  = explode("|", ee()->TMPL->fetch_param('repositories'));

		// Get the token from the cache or the database
		if ( ! ($this->token = ee()->session->cache(__CLASS__, 'token')))
		{
			$token_query = ee()->db->limit(1)
				->get_where('kanban_board', array(
					'member_id' => ee()->session->userdata('member_id')
				));

			$this->token = ($token_query->num_rows() > 0)
				? $token_query->row('github_token') : FALSE;

			ee()->session->set_cache(__CLASS__, 'token', $this->token);
		}

		// Get the GitHub Client from the cache or make a new one
		if ($this->token
			&& ! ($this->client = ee()->session->cache(__CLASS__, 'client')))
		{
			$this->client = new \Github\Client();
			$this->client->authenticate($this->token, NULL, \Github\Client::AUTH_URL_TOKEN);
			ee()->session->set_cache(__CLASS__, 'client', $this->client);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Get the full board of issues/pull requests from GitHub
	 * @return string Parsed template sent into {exp:kanban_board:board}
	 */
	public function board()
	{
		// No client? No business being here.
		if ( ! $this->client)
		{
			return ee()->TMPL->no_results();
		}

		$milestones = array();

		foreach ($this->milestones() as $milestone_name => $milestones_data)
		{
			$labels = array();

			foreach (explode('|', ee()->TMPL->fetch_param('labels')) as $label_name)
			{
				$issues = array();

				foreach ($milestones_data as $repository => $milestone_data)
				{
					$issues = array_merge($issues, $this->issues(
						$repository,
						$milestone_data['number'],
						$label_name,
						ee()->TMPL->fetch_param('avatar_size', 0)
					));
				}

				$labels[] = array(
					'label'  => $label_name,
					'issues' => $issues
				);
			}

			// Figure out the dates based around the milestone
			$first_milestone = reset($milestones_data);
			$deadline = NULL;
			$days_left = NULL;
			if ( ! empty($first_milestone['due_on']))
			{
				$deadline = strtotime($first_milestone['due_on']);
				$days_left = floor(($deadline - time())/(60*60*24));
			}

			$milestones[] = array(
				'milestone' => $milestone_name,
				'deadline'  => $deadline,
				'days_left' => $days_left,
				'labels'    => $labels
			);
		}

		return $this->return_data = ee()->TMPL->parse_variables(
			ee()->TMPL->tagdata,
			$milestones,
			FALSE
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Returns the milestones for the given (Control Panel) set of repositories
	 * @return array Sorted array of milestones, with the name as the key and an
	 *               indexed array of repositories and milestone IDs as the
	 *               value
	 */
	private function milestones()
	{
		$milestones_param = ee()->TMPL->fetch_param('milestones', FALSE);

		$milestones = array();
		$milestones_api = $this->client->api('issues')->milestones();

		foreach ($this->repositories as $repository)
		{
			if ($milestones_param == 'none')
			{
				$milestones['none'][$repository] = array('number' => 'none');
			}
			else
			{
				$milestone_request = $milestones_api->all($this->account, $repository);
				foreach ($milestone_request as $data)
				{
					// Skip unspecified milestones if any are specified
					if ( ! empty($milestones_param)
						&& ! in_array($data['title'], explode('|', $milestones_param)))
					{
						continue;
					}

					$milestones[$data['title']][$repository] = $data;
				}
			}
		}

		// Only sort if the list of milestones is not provided
		if (empty($milestones_param))
		{
			ksort($milestones);

			// If we have any milestones that aren't using semantic versions
			// just return a ksort()'ed list
			foreach ($milestones as $version => $data)
			{
				try {
					$semantic_version = new version($version);
				} catch (Exception $e) {
					return $milestones;
				}
			}

			uksort($milestones, function ($versionA, $versionB) {
				return version::compare($versionA, $versionB);
			});
		}

		return $milestones;
	}

	// -------------------------------------------------------------------------

	/**
	 * Returns the issues for the given milestone and label
	 * @param string $repository   The name of the repository
	 * @param int    $milestone_id The ID (not the name) of the milestone
	 * @param string $label        The name of the label
	 * @param int    $avatar_size  The size of the square avatar in pixels
	 * @return array Associative array of issues in the repository, milestone,
	 *               with the associated label
	 */
	private function issues($repository, $milestone_id, $label, $avatar_size)
	{
		$condition = NULL;

		if ($label == 'ISSUE' OR strncmp($label, 'not ', 4) === 0)
		{
			$condition = $label;
			$label     = '';
		}

		$issues = array();

		try {
			// Note: GitHub API wants milestone to be the milestone number, not
			// the name

			$issue_parameters = array('milestone' => $milestone_id);

			if ($label)
			{
				$issue_parameters['labels'] = $label;
			}

			$issue_request = $this->client->api('issue')->all(
				$this->account,
				$repository,
				$issue_parameters
			);
		} catch (Exception $e) {
			// Exceptions come up when you try to get a milestone from the wrong
			// repository, which occurs because we have multiple repositories
			return;
		}

		foreach ($issue_request as $issue)
		{
			// Weed out the issues from the pull requests
			if ($condition == 'ISSUE')
			{
				if ($issue['pull_request']['html_url'])
				{
					continue;
				}
			}
			else
			{
				if ( ! $issue['pull_request']['html_url'])
				{
					continue;
				}
			}

			// Gather labels for this specific issue
			$labels = array();
			foreach ($issue['labels'] as $label)
			{
				if (strncmp($condition, 'not ', 4) === 0)
				{
					$negative = substr($condition, 4);
					if ($label['name'] == $negative)
					{
						// Get out of the labels loop and the current issue
						continue 2;
					}
				}

				$labels[] = array(
					'name'  => $label['name'],
					'color' => '#'.$label['color']
				);
			}

			// Retrieve the number of tasks completed vs tasks available
			$complete = substr_count(strtolower($issue['body']), '[x]');
			$incomplete = substr_count(strtolower($issue['body']), '[ ]');
			$total = $complete + $incomplete;
			$percentage = ($complete OR $incomplete) ? round($complete / $total * 100) : 0;

			// Progress (how far along we are)
			$progress = '';
			if ($percentage >= 25 && $percentage < 50)
			{
				$progress = 'quarter';
			}
			elseif ($percentage >= 50 && $percentage < 75)
			{
				$progress = 'half';
			}
			else if ($percentage >= 75 && $percentage < 100)
			{
				$progress = 'third';
			}
			else if ($percentage == 100)
			{
				$progress = 'done';
			}

			if (is_numeric($avatar_size) && $avatar_size > 0
				&& ! empty($issue['assignee']['avatar_url']))
			{
				$issue['assignee']['avatar_url'] .= 's='.$avatar_size;
			}

			$issues[] = array(
				'title'            => ee()->functions->encode_ee_tags($issue['title'], TRUE),
				'body'             => $issue['body'],
				'url'              => $issue['html_url'],
				'assignee'         => $issue['assignee']['login'],
				'assignee_avatar'  => $issue['assignee']['avatar_url'],
				'label_count'      => count($labels),
				'issue_labels'     => $labels,
				'tasks_complete'   => $complete,
				'tasks_incomplete' => $incomplete,
				'tasks_total'      => $total,
				'tasks_percentage' => $percentage,
				'tasks_progress'   => $progress,
			);
		}

		return $issues;
	}

	// -------------------------------------------------------------------------

	/**
	 * Provide a tag specifically for registering the token
	 * @return string Rendered string with {link} as the registration URL or
	 *                nothing at all if already logged in
	 */
	public function register()
	{
		if ( ! $this->token
			&& ($code = ee()->input->get('code'))
			&& ee()->session->userdata('member_id'))
		{
			// Register them if they just came back from GitHub
			$token = $this->_get_token($code);

			ee()->db->insert(
				'kanban_board',
				array(
					'member_id'    => ee()->session->userdata('member_id'),
					'github_token' => $token
				)
			);

			ee()->functions->redirect(
				ee()->TMPL->fetch_param('redirect', ee()->uri->uri_string)
			);
		}
		elseif ( ! $this->token)
		{
			return $this->return_data = ee()->TMPL->parse_variables(
				ee()->TMPL->tagdata,
				array(array('link' => 'https://github.com/login/oauth/authorize?scope=user,repo&client_id='.$this->client_id)),
				FALSE
			);
		}

		return $this->return_data = ee()->TMPL->no_results();
	}

	// -------------------------------------------------------------------------

	/**
	 * Get the actual OAuth token from GitHub
	 * @param  string $code temporary code granted from GitHub
	 * @return string       permanent access token
	 */
	private function _get_token($code)
	{
		$data = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'code' => $code
		);

		// String-ify the data
		$fields = '';
		foreach($data as $key => $value) {
			$fields .= $key . '=' . $value . '&';
		}
		rtrim($fields, '&');

		$post = curl_init();
		curl_setopt($post, CURLOPT_URL, "https://github.com/login/oauth/access_token");
		curl_setopt($post, CURLOPT_POST, count($data));
		curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($post, CURLOPT_HTTPHEADER, array("Accept: application/json"));

		// Get contents
		ob_start();
		curl_exec($post);
		$content = ob_get_contents();
		curl_close($post);

		// Only return the access token
		$content = json_decode($content);
		return $content->access_token;
	}
}
/* End of file mod.kanban_board.php */
/* Location: /system/expressionengine/third_party/kanban_board/mod.kanban_board.php */
