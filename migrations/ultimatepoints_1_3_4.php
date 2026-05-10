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
 * v1.3.4 — Phase E (drop dual-write, gated cutover).
 *
 * Adds `ultimatepoints_bbaccounts_canonical` (default 0). When the admin
 * flips it to 1 from the ACP "bbAccounts mapping" page:
 *   - `post_to_ledger()` becomes the sole writer: posts the journal entry
 *     AND updates `phpbb_users.user_points` / `phpbb_points_bank.holding` /
 *     `phpbb_points_values.lottery_jackpot` as cache columns.
 *   - `add_points()`, `substract_points()`, `set_points()`, `set_bank()`,
 *     and the bulk SQL inside `run_bank()` early-return.
 *
 * Reversible — toggle back to 0 and the legacy add_points/substract_points
 * paths re-engage (Phase B-2 dual-write).
 */
class ultimatepoints_1_3_4 extends migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\ultimatepoints\migrations\ultimatepoints_1_3_1',
		];
	}

	public function effectively_installed()
	{
		return isset($this->config['ultimatepoints_bbaccounts_canonical']);
	}

	public function update_data()
	{
		return [
			['config.update', ['ultimate_points_version', '1.3.4']],
			['config.add', ['ultimatepoints_bbaccounts_canonical', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['ultimatepoints_bbaccounts_canonical']],
		];
	}
}
