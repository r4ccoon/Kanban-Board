Kanban Board for GitHub Projects
================================

The Kanban Board module allows you to easily display all GitHub issues and pull
requests associated with various milestones and labels. While it *can* just
display issues in a list, it's most comfortable showing a table of those
milestones and issues, that might look something like this:

| Milestone | Planning         | In Progress      | In Review        |
| --------- | ---------------- | ---------------- | ---------------- |
| 1.0       | *list of issues* | *list of issues* | *list of issues* |
| 2.0       | *list of issues* | *list of issues* | *list of issues* |
| 3.0       | *list of issues* | *list of issues* | *list of issues* |

With the `{exp:kanban_board:board}` tag, you'll specify an account, a list of
repositories, a list of issues, and optionally a list of milestones to iterate
over. By default, the application will pull all milestones related to all of the
repositories specified, but by specifying the list of milestones, you can
override that behavior.

Requirements
------------

Before you can use the Kanban Board plugin, you'll need to [register your
application](https://github.com/settings/applications/new). Make sure that your
application's authorization callback url points to the page where your Kanban
Board will live. This page **does not** need to be publicly accessible, but it
does need to be an actual page.

You'll also need to have [Composer](https://getcomposer.org) installed so you
can install the necessary dependencies.

Installation
------------

1. Run `composer install` in `kanban_board`
2. Copy `kanban_board` to your `system/expressionengine/third_party` directory
3. Go to your ExpressionEngine Control Panel
4. Navigate to Add-ons -> Modules
5. Install the module
6. Go to the settings page and enter your GitHub developer client ID and client
   secret
7. (*Optional*) If your project is in a git repository, add
   `system/expressionengine/third_party/kanban_board/vendor` to your `.gitignore`
   file.

Usage
-----

### `{exp:kanban_board:register}`

In order for people to use your Kanban Board, you'll need to request permissions
from their GitHub account---this is done through an OAuth Authorization. What
this tag does is simply print out the correct URL based on your application's
client ID:

	{exp:kanban_board:register}
		<p>You need to <a href="{link}">register first</a>.</p>
	{/exp:kanban_board:register}

It has a grand total of one variable: `{link}` and it's just the URL that your
users will need to click on to register with your application.

It also has one *optional* parameter: `redirect` where you can specify the page
that will be redirected to after the user is successfully registered with
GitHub. If this parameter is not used, they will be redirect to the page where
the `{exp:kanban_board:register}` tag is located.

**Make sure this tag is somewhere on the page with the rest of your Kanban Board.
Without it your users will not be able to view the board.**

### `{exp:kanban_board:board}`

This is the meat and potatoes of the module. There are four possible parameters,
and a set of nested variable pairs and variables.

**Note:** Milestones are sorted using [Semantic Versioning](http://semver.org)
if *all* of your milestones are valid semantic versions. If any are not valid,
your milestones are sorted alphanumerically.

#### Parameters

- `account` - Simply used to specify the account of the repository you want to
  pull issues and pull requests from.
- `repositories` - A pipe-delimited list of repositories to pull issues and pull
  requests from.
- `labels` - A pipe-delimited list of labels to iterate over. Can also use two
  special labels: `ISSUE` (which specifies issues) and `not <label-name>` which
  negates a specific label
- `milestones` - (*Optional*) A pipe delimited list of milestones to
  specifically show. **If not specified, all milestones from all specified
  `repositories` will be pulled and shown.**
- `avatar_size` - (*Optional*) A number in pixels of how big you want the image
  from GitHub to be. For example, if you set `avatar_size="25"` you would get
  back avatars that are 25px by 25px.

#### Variables

The variables within the `{exp:kanban_board:board}` tag are all nested, meaning
everything is inside something else. Here's what they look like:

- `{milestone}` - the name of the milestone
- `{deadline}` - the date the milestone is due, use the standard EE date format= parameter for formatting
- `{days_left}` - the number of days left before the milestone is due
- `{labels}...{/labels}`
	- `{label}` - the name of the label
	- `{issues}...{/issues}`
		- `{title}` - the title
		- `{body}` - the body
		- `{url}` - the url
		- `{asignee}` - the username of the person currently assigned
		- `{asignee_avatar}` - the avatar of the person currently assigned
		- `{issue_labels}...{/issue_labels}`
			- `{name}` - the name of the label
			- `{color}` - the color of the label
		- `{tasks_total}` - the total number of tasks in the description of the pull request, both complete and incomplete
		- `{tasks_incomplete}` - the number of incomplete tasks
		- `{tasks_complete}` - the number of complete tasks
		- `{tasks_percentage}` - the percentage of tasks completed
		- `{tasks_progress`}` - the progress of the tasks based on various ranges and `{tasks_percentage}` (`quarter` when `{tasks_percentage}` > 25, `half` when `{tasks_percentage}` > 50, 'third' when `{tasks_percentage}` > 75, 'done' when `{tasks_percentage}` == 100)

#### Example

	{exp:kanban_board:board
		account="EllisLab"
		repositories="ExpressionEngine|ExpressionEngine-User-Guide"
		labels="ISSUE|not in-review|in-review"
	}
		{if count == "1"}
			<table class="board-table">
				<thead>
					<tr>
						<th>Milestone</th>
						<th width="33%">Planning<br><small>(issues)</small></th>
						<th width="33%">In Progress<br><small>(pull-requests <em>without</em> <code>in-review</code> label)</small></th>
						<th width="33%">In Review<br><small>(pull-requests <em>with</em> <code>in-review</code> label)</small></th>
					</tr>
				</thead>
				<tbody>
		{/if}

		<tr{switch='| class="alt"'}>
			<th class="milestone">{milestone}</th>
			{labels}
				<td>
					<ul>
						{issues}
							<li>
								<img src="{assignee_avatar}" width="25">
								<a href="{url}">{title}</a>
								{if label_count}
									<ul class="git-label">
										{issue_labels}
											<li style="background-color: {color};" title="{name}"></li>
										{/issue_labels}
									</ul>
								{/if}
							</li>
						{/issues}
					</ul>
				</td>
			{/labels}
		</tr>

		{if count == total_results}
				</tbody>
			</table>
		{/if}
	{/exp:kanban_board:board}

Changelog
---------

### 1.0.0

- Initial release

Disclaimer
----------

GitHub® is a registered trademark of GitHub, Inc. Kanban Board for GitHub
Projects is an independent project and has not been authorized, sponsored, or
otherwise approved by GitHub, Inc.

