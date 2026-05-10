<?php
/**
 *
 * @package phpBB Extension - Ultimate Points
 * @copyright (c) 2026 dmzx https://www.dmzx-web.net
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace dmzx\ultimatepoints\migrations;

use phpbb\db\migration\migration;

/**
 * v1.3.0 — bbAccounts integration: mapping infrastructure (Phase B-1).
 *
 * Introduces 13 config keys that map UltimatePoints' internal *roles*
 * (User Wallets, Bank Holdings, Posting Rewards, …) to bbAccounts
 * `account_id`s the admin has manually created in bbAccounts ACP.
 * All defaults are 0 (= unmapped). At runtime (Phase B-2 onward),
 * mutation code consults the mapping; unmapped roles silently skip
 * the bbAccounts side and keep the legacy denormalised storage path.
 *
 * Adds an ACP mode `bbaccounts` to the existing UltimatePoints module
 * for the admin-facing mapping page. Bumps PHPBB compat floor to 3.3
 * (handled in composer.json + ext.php).
 *
 * See contrib/specs/2026-05-10-bbaccounts-integration.md §3.1 for the
 * role contract.
 */
class ultimatepoints_1_3_0 extends migration
{
	/** Internal roles the admin maps to bbAccounts accounts. Order matches §3.1. */
	private const ROLES = [
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
		return [
			'\dmzx\ultimatepoints\migrations\ultimatepoints_1_2_8',
		];
	}

	public function effectively_installed()
	{
		return isset($this->config['ultimatepoints_acct_user_wallets']);
	}

	public function update_data()
	{
		$ops = [
			['config.update', ['ultimate_points_version', '1.3.0']],
		];

		foreach (self::ROLES as $role)
		{
			$ops[] = ['config.add', ['ultimatepoints_acct_' . $role, 0]];
		}

		// Add the bbAccounts mapping ACP mode to the existing module.
		$ops[] = ['module.add', [
			'acp',
			'ACP_POINTS',
			[
				'module_basename' => '\dmzx\ultimatepoints\acp\acp_ultimatepoints_module',
				'modes'           => ['bbaccounts'],
			],
		]];

		return $ops;
	}

	public function revert_data()
	{
		$ops = [];

		foreach (self::ROLES as $role)
		{
			$ops[] = ['config.remove', ['ultimatepoints_acct_' . $role]];
		}

		return $ops;
	}
}
