<?php
/**
 *
 * @package phpBB Extension - bbPoints (formerly Ultimate Points)
 * @copyright (c) 2016 dmzx & posey - https://www.dmzx-web.net
 * @copyright (c) 2026 Andy Vandenberghe (Sajaki) - https://github.com/avatharbe
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbpoints\migrations;

use phpbb\db\migration\migration;

/**
 * bbPoints v2.0.0 install
 */
class bbpoints_install extends migration
{
	/**
	 * The 13 bbAccounts role mapping keys.
	 * Each maps an internal bbPoints role to an admin-configured bbAccounts account_id.
	 * Default 0 = unmapped; admin maps them via the ACP bbAccounts Mapping screen.
	 */
	private const BBACCOUNTS_ROLES = [
		'user_wallets',
		'bank_holdings',
		'lottery_pool',
		'rev_post_costs',
		'rev_attach_costs',
		'rev_penalty',
		'rev_bank_fees',
		'rev_admin_down',
		'exp_posting',
		'exp_registration',
		'exp_random',
		'exp_bank_int',
		'exp_admin_award',
	];

	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function effectively_installed()
	{
		return isset($this->config['bbpoints_version']) && (string) $this->config['bbpoints_version'] === '2.0.0';
	}

	public function update_data()
	{
		$ops = [
			// === Version markers ===
			['config.add', ['bbpoints_version', '2.0.0']],
			['config.add', ['ultimate_points_version', '2.0.0']],
			['config.add', ['points_notification_id', 0]],

			// === Defensive cleanup: remove v1.3.x dual-write config keys if present ===
			// (config.remove is idempotent — no-op if the row doesn't exist)
			['config.remove', ['ultimatepoints_bbaccounts_backfilled']],
			['config.remove', ['ultimatepoints_bbaccounts_canonical']],

			// === Icons and display names (from v1.2.0) ===
			['config.add', ['points_icon_mainicon', 'fa-university']],
			['config.add', ['points_icon_uplist', 'fa-users']],
			['config.add', ['points_icon_bankicon', 'fa-money']],
			['config.add', ['points_icon_lotteryicon', 'fa-ticket']],
			['config.add', ['points_name', 'Points']],
			['config.add', ['points_name_uplist', 'UP List']],
			['config.add', ['points_enable', 1]],

			// === mChat integration flags (from v1.2.2) — opt-in per feature ===
			['config.add', ['lottery_mchat_enable', 0]],
			['config.add', ['robbery_mchat_enable', 0]],
			['config.add', ['transfer_mchat_enable', 0]],
		];

		// === bbAccounts role mapping keys (from v1.3.0) ===
		foreach (self::BBACCOUNTS_ROLES as $role)
		{
			$ops[] = ['config.add', ['ultimatepoints_acct_' . $role, 0]];
		}

		// === Permissions ===
		$ops = array_merge($ops, [
			['permission.add', ['u_use_points', true]],
			['permission.add', ['u_use_bank', true]],
			['permission.add', ['u_use_logs', true]],
			['permission.add', ['u_use_robbery', true]],
			['permission.add', ['u_use_lottery', true]],
			['permission.add', ['u_use_transfer', true]],
			['permission.add', ['f_pay_attachment', false]],
			['permission.add', ['f_pay_topic', false]],
			['permission.add', ['f_pay_post', false]],
			['permission.add', ['m_chg_points', true]],
			['permission.add', ['m_chg_bank', true]],
			['permission.add', ['a_points', true]],

			// === REGISTERED group gets the user-facing permissions by default ===
			['permission.permission_set', ['REGISTERED', 'u_use_points', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_use_bank', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_use_logs', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_use_robbery', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_use_lottery', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_use_transfer', 'group']],

			// === ACP module: top-level category ===
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_POINTS',
			]],

			// === ACP module: bbPoints settings under ACP_POINTS ===
			['module.add', [
				'acp',
				'ACP_POINTS',
				[
					'module_basename' => '\avathar\bbpoints\acp\acp_main_module',
					'modes'           => ['points', 'forumpoints', 'bank', 'lottery', 'robbery', 'bbaccounts'],
				],
			]],

			// === UCP module: top-level category ===
			['module.add', [
				'ucp',
				0,
				'UCP_ULTIMATEPOINTS_TITLE',
			]],

			// === UCP module: bbPoints user views ===
			['module.add', [
				'ucp',
				'UCP_ULTIMATEPOINTS_TITLE',
				[
					'module_basename' => '\avathar\bbpoints\ucp\ucp_main_module',
					'auth'            => 'ext_avathar/bbpoints',
					'modes'           => ['lottery', 'bank', 'robbery', 'transfer'],
				],
			]],

			// === Seed extension-specific config tables ===
			['custom', [[&$this, 'insert_sample_data']]],
			['custom', [[&$this, 'insert_config_data']]],
		]);

		return $ops;
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'points_bank' => [
					'COLUMNS' => [
						'id' => ['UINT:10', null, 'auto_increment'],
						'user_id' => ['UINT:10', 0],
						'holding' => ['DECIMAL:20', 0.00],
						'totalwithdrew' => ['DECIMAL:20', 0.00],
						'totaldeposit' => ['DECIMAL:20', 0.00],
						'opentime' => ['UINT:10', 0],
						'fees' => ['CHAR:5', 'on'],
					],
					'PRIMARY_KEY' => 'id',
				],

				$this->table_prefix . 'points_config' => [
					'COLUMNS' => [
						'config_name' => ['VCHAR', ''],
						'config_value' => ['VCHAR_UNI', ''],
					],
					'PRIMARY_KEY' => 'config_name',
				],

				$this->table_prefix . 'points_lottery_history' => [
					'COLUMNS' => [
						'id' => ['UINT:11', null, 'auto_increment'],
						'user_id' => ['UINT', 0],
						'user_name' => ['VCHAR', ''],
						'time' => ['UINT:11', 0],
						'amount' => ['DECIMAL:20', 0.00],
					],
					'PRIMARY_KEY' => 'id',
				],

				$this->table_prefix . 'points_lottery_tickets' => [
					'COLUMNS' => [
						'ticket_id' => ['UINT:11', null, 'auto_increment'],
						'user_id' => ['UINT:11', 0],
					],
					'PRIMARY_KEY' => 'ticket_id',
				],

				$this->table_prefix . 'points_values' => [
					'COLUMNS' => [
						'bank_cost' => ['DECIMAL:10', 0.00],
						'bank_fees' => ['DECIMAL:10', 0.00],
						'bank_interest' => ['DECIMAL:10', 0.00],
						'bank_interestcut' => ['DECIMAL:20', 0.00],
						'bank_last_restocked' => ['UINT:11', null],
						'bank_min_deposit' => ['DECIMAL:10', 0.00],
						'bank_min_withdraw' => ['DECIMAL:10', 0.00],
						'bank_name' => ['VCHAR:100', null],
						'bank_pay_period' => ['UINT:10', 2592000],
						'lottery_base_amount' => ['DECIMAL:10', 0.00],
						'lottery_chance' => ['DECIMAL', 50.00],
						'lottery_draw_period' => ['UINT:10', 3600],
						'lottery_jackpot' => ['DECIMAL:20', 50.00],
						'lottery_last_draw_time' => ['UINT:11', null],
						'lottery_max_tickets' => ['UINT:10', 10],
						'lottery_name' => ['VCHAR:100', ''],
						'lottery_prev_winner' => ['VCHAR', ''],
						'lottery_prev_winner_id' => ['UINT:10', 0],
						'lottery_ticket_cost' => ['DECIMAL:10', 0.00],
						'lottery_winners_total' => ['UINT', 0],
						'number_show_per_page' => ['UINT:10', 0],
						'number_show_top_points' => ['UINT', 0],
						'points_bonus_chance' => ['DECIMAL:10', 0.00],
						'points_bonus_min' => ['DECIMAL:10', 0.00],
						'points_bonus_max' => ['DECIMAL:10', 0.00],
						'points_per_attach' => ['DECIMAL:10', 0.00],
						'points_per_attach_file' => ['DECIMAL:10', 0.00],
						'points_per_poll' => ['DECIMAL:10', 0.00],
						'points_per_poll_option' => ['DECIMAL:10', 0.00],
						'points_per_post_character' => ['DECIMAL:10', 0.00],
						'points_per_post_word' => ['DECIMAL:10', 0.00],
						'points_per_topic_character' => ['DECIMAL:10', 0.00],
						'points_per_topic_word' => ['DECIMAL:10', 0.00],
						'points_per_warn' => ['DECIMAL:10', 0.00],
						'reg_points_bonus' => ['DECIMAL:10', 0.00],
						'robbery_chance' => ['DECIMAL:5', 0.00],
						'robbery_loose' => ['DECIMAL:5', 0.00],
						'robbery_max_rob' => ['DECIMAL:5', 10.00],
						'transfer_fee' => ['UINT:10', 0],
						'lottery_pm_from' => ['UINT:10', 0],
						'forum_topic' => ['DECIMAL:10', 0.00],
						'forum_post' => ['DECIMAL:10', 0.00],
						'forum_edit' => ['DECIMAL:10', 0.00],
						'forum_cost' => ['DECIMAL:10', 0.00],
						'forum_cost_topic' => ['DECIMAL:10', 0.00],
						'forum_cost_post' => ['DECIMAL:10', 0.00],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'users' => [
					'user_points' => ['DECIMAL:20', 0.00],
				],

				$this->table_prefix . 'posts' => [
					'points_received' => ['DECIMAL:20', 0.00],
					'points_poll_received' => ['DECIMAL:20', 0.00],
					'points_attachment_received' => ['DECIMAL:20', 0.00],
					'points_topic_received' => ['DECIMAL:20', 0.00],
					'points_post_received' => ['DECIMAL:20', 0.00],
				],

				$this->table_prefix . 'forums' => [
					'forum_perpost' => ['DECIMAL:10', 5.00],
					'forum_peredit' => ['DECIMAL:10', 0.05],
					'forum_pertopic' => ['DECIMAL:10', 15.00],
					'forum_cost' => ['DECIMAL:10', 0.00],
					'forum_cost_topic' => ['DECIMAL:10', 0.00],
					'forum_cost_post' => ['DECIMAL:10', 0.00],
				],
			],
			'drop_tables' => [
				// Defensive: if upgrading from v1.3.x in place, the log table exists.
				$this->table_prefix . 'points_log',
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'points_bank',
				$this->table_prefix . 'points_config',
				$this->table_prefix . 'points_lottery_history',
				$this->table_prefix . 'points_lottery_tickets',
				$this->table_prefix . 'points_values',
			],
			'drop_columns' => [
				$this->table_prefix . 'users' => [
					'user_points',
				],

				$this->table_prefix . 'posts' => [
					'points_received',
					'points_poll_received',
					'points_attachment_received',
					'points_topic_received',
					'points_post_received',
				],

				$this->table_prefix . 'forums' => [
					'forum_perpost',
					'forum_peredit',
					'forum_pertopic',
					'forum_cost',
					'forum_cost_topic',
					'forum_cost_post',
				],
			],
		];
	}

	/**
	 * Seed `phpbb_points_values` with one row of numerical defaults.
	 *
	 * v1.x defaults preserved verbatim. lottery_jackpot starts at 50 (matches
	 * the schema default); admins can tune via the Lottery ACP screen.
	 */
	public function insert_sample_data()
	{
		if (!$this->db_tools->sql_table_exists($this->table_prefix . 'points_values'))
		{
			return;
		}

		// Skip if already seeded (idempotent for upgrades from v1.x).
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->table_prefix . 'points_values';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if ((int) $row['cnt'] > 0)
		{
			return;
		}

		$sample_data = [
			[
				'number_show_per_page' => '15',
				'number_show_top_points' => '10',
				'reg_points_bonus' => '50',
				'lottery_jackpot' => '50',
				'lottery_winners_total' => '0',
				'lottery_prev_winner' => '0',
				'lottery_prev_winner_id' => '0',
				'lottery_last_draw_time' => '0',
				'bank_last_restocked' => '0',
				'lottery_base_amount' => '50.00',
				'lottery_draw_period' => '3600',
				'lottery_ticket_cost' => '10',
				'bank_fees' => '0',
				'bank_interest' => '0',
				'bank_pay_period' => '2592000',
				'bank_min_withdraw' => '0',
				'bank_min_deposit' => '0',
				'bank_interestcut' => '0',
				'points_bonus_chance' => '0',
				'points_bonus_min' => '10.00',
				'points_bonus_max' => '50.00',
				'points_per_poll_option' => '0',
				'points_per_poll' => '0',
				'points_per_attach_file' => '0',
				'points_per_attach' => '0',
				'points_per_post_word' => '0',
				'points_per_post_character' => '0',
				'points_per_topic_word' => '0',
				'points_per_topic_character' => '0',
				'points_per_warn' => '0',
				'robbery_chance' => '50',
				'robbery_loose' => '50',
				'transfer_fee' => '10',
				'bank_cost' => '0',
				'bank_name' => 'BANK NAME',
				'lottery_name' => 'LOTTERY NAME',
			],
		];

		$this->db->sql_multi_insert($this->table_prefix . 'points_values', $sample_data);
	}

	/**
	 * Seed `phpbb_points_config` with extension-feature toggles.
	 *
	 * v1.x defaults preserved verbatim, except the disabled-message string
	 * is updated for v2.0 branding.
	 */
	public function insert_config_data()
	{
		if (!$this->db_tools->sql_table_exists($this->table_prefix . 'points_config'))
		{
			return;
		}

		// Skip rows that already exist (idempotent for upgrades from v1.x).
		$sql = 'SELECT config_name FROM ' . $this->table_prefix . 'points_config';
		$result = $this->db->sql_query($sql);
		$existing = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$existing[$row['config_name']] = true;
		}
		$this->db->sql_freeresult($result);

		$config_data = [
			['config_name' => 'transfer_enable',            'config_value' => '1'],
			['config_name' => 'transfer_pm_enable',         'config_value' => '1'],
			['config_name' => 'comments_enable',            'config_value' => '1'],
			['config_name' => 'logs_enable',                'config_value' => '1'],
			['config_name' => 'images_topic_enable',        'config_value' => '1'],
			['config_name' => 'images_memberlist_enable',   'config_value' => '1'],
			['config_name' => 'lottery_enable',             'config_value' => '1'],
			['config_name' => 'bank_enable',                'config_value' => '1'],
			['config_name' => 'robbery_enable',             'config_value' => '1'],
			['config_name' => 'points_disablemsg',          'config_value' => 'bbPoints is currently disabled!'],
			['config_name' => 'stats_enable',               'config_value' => '1'],
			['config_name' => 'lottery_multi_ticket_enable','config_value' => '1'],
			['config_name' => 'robbery_notify',             'config_value' => '1'],
			['config_name' => 'display_lottery_stats',      'config_value' => '1'],
			['config_name' => 'uplist_enable',              'config_value' => '1'],
		];

		$to_insert = [];
		foreach ($config_data as $row)
		{
			if (!isset($existing[$row['config_name']]))
			{
				$to_insert[] = $row;
			}
		}

		if (!empty($to_insert))
		{
			$this->db->sql_multi_insert($this->table_prefix . 'points_config', $to_insert);
		}
	}
}
