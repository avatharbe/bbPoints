<?php
/**
 *
 * @package phpBB Extension - bbPoints (formerly Ultimate Points)
 * @copyright (c) 2016 dmzx & posey - https://www.dmzx-web.net
 * @copyright (c) 2026 Andy Vandenberghe (Sajaki) - https://github.com/avatharbe
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbpoints;

use phpbb\extension\base;

class ext extends base
{
	/**
	 * Enable extension only when phpBB version requirement is met
	 * and bbAccounts is enabled (bbPoints v2.0 is bbAccounts-canonical).
	 *
	 * @return bool
	 * @access public
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		if (version_compare($config['version'], '3.3.0', '<'))
		{
			return false;
		}

		$ext_manager = $this->container->get('ext.manager');
		if (!$ext_manager->is_enabled('avathar/bbaccounts'))
		{
			$user = $this->container->get('user');
			$user->add_lang_ext('avathar/bbpoints', 'common');
			return [$user->lang('EXT_BBPOINTS_REQUIRES_BBACCOUNTS')];
		}

		return true;
	}

	protected static $notification_types = [
		'avathar.bbpoints.notification.type.points',
	];

	/**
	 * Enable our notifications.
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 * @access public
	 */
	public function enable_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet
				/* @var $phpbb_notifications manager */
				$phpbb_notifications = $this->container->get('notification_manager');
				foreach (self::$notification_types as $type)
				{
					$phpbb_notifications->enable_notifications($type);
				}
				return 'notifications';
				break;
			default:
				// Run parent enable step method
				return parent::enable_step($old_state);
				break;
		}
	}

	/**
	 * Disable our notifications.
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 * @access public
	 */
	public function disable_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet
				/* @var $phpbb_notifications manager */
				$phpbb_notifications = $this->container->get('notification_manager');
				foreach (self::$notification_types as $type)
				{
					$phpbb_notifications->disable_notifications($type);
				}
				return 'notifications';
				break;
			default:
				// Run parent disable step method
				return parent::disable_step($old_state);
				break;
		}
	}

	/**
	 * Purge our notifications
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return mixed Returns false after last step, otherwise temporary state
	 * @access public
	 */
	public function purge_step($old_state)
	{
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet
				/* @var $phpbb_notifications manager */
				$phpbb_notifications = $this->container->get('notification_manager');
				foreach (self::$notification_types as $type)
				{
					$phpbb_notifications->purge_notifications($type);
				}
				return 'notifications';
				break;
			default:
				// Run parent purge step method
				return parent::purge_step($old_state);
				break;
		}
	}
}
