<?php
/**
 *
 * @package phpBB Extension - Ultimate Points
 * @copyright (c) 2016 dmzx & posey - https://www.dmzx-web.net
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbpoints\ucp;

class ucp_main_info
{
	function module()
	{
		return [
			'filename' => '\avathar\bbpoints\ucp\ucp_main_module',
			'title' => 'UCP_ULTIMATEPOINTS_TITLE',
			'modes' => [
				'lottery' => [
					'title' => 'LOTTERY_TITLE_MAIN',
					'auth' => 'ext_avathar/bbpoints',
					'cat' => ['UCP_MAIN'],
				],
				'bank' => [
					'title' => 'BANK_TITLE_MAIN',
					'auth' => 'ext_avathar/bbpoints',
					'cat' => ['UCP_MAIN'],
				],
				'robbery' => [
					'title' => 'POINTS_ROBBERY',
					'auth' => 'ext_avathar/bbpoints',
					'cat' => ['UCP_MAIN'],
				],
				'transfer' => [
					'title' => 'POINTS_TRANSFER_RECIEVED',
					'auth' => 'ext_avathar/bbpoints',
					'cat' => ['UCP_MAIN'],
				],
			],
		];
	}
}
