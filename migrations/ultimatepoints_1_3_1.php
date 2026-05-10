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
 * v1.3.1 — bbAccounts integration: backfill state flag (Phase C foundation).
 *
 * Adds `ultimatepoints_bbaccounts_backfilled` (default 0). Set to 1 after
 * the admin runs the one-shot opening-balance backfill from the ACP
 * "bbAccounts mapping" page. The mapping page hides the backfill section
 * once this flag is set.
 */
class ultimatepoints_1_3_1 extends migration
{
	static public function depends_on()
	{
		return [
			'\dmzx\ultimatepoints\migrations\ultimatepoints_1_3_0',
		];
	}

	public function effectively_installed()
	{
		return isset($this->config['ultimatepoints_bbaccounts_backfilled']);
	}

	public function update_data()
	{
		return [
			['config.update', ['ultimate_points_version', '1.3.1']],
			['config.add', ['ultimatepoints_bbaccounts_backfilled', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['ultimatepoints_bbaccounts_backfilled']],
		];
	}
}
