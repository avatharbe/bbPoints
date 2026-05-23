<?php
/**
 *
 * @package phpBB Extension - Ultimate Points
 * @copyright (c) 2016 dmzx & posey - https://www.dmzx-web.net
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbpoints\acp;

class acp_main_info
{
	function module()
	{
		return [
			'filename' => '\avathar\bbpoints\acp\acp_main_module',
			'title' => 'ACP_POINTS',
			'modes' => [
				'points' => [
					'title' => 'ACP_POINTS_INDEX_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
				'forumpoints' => [
					'title' => 'ACP_POINTS_FORUM_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
				'bank' => [
					'title' => 'ACP_POINTS_BANK_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
				'lottery' => [
					'title' => 'ACP_POINTS_LOTTERY_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
				'robbery' => [
					'title' => 'ACP_POINTS_ROBBERY_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
				'bbaccounts' => [
					'title' => 'ACP_POINTS_BBACCOUNTS_TITLE',
					'auth' => 'ext_avathar/bbpoints && acl_a_board',
					'cat' => ['ACP_POINTS'],
				],
			],
		];
	}
}
