# Chapter 1 — Point Settings

> ACP path: `ACP → Customise → Ultimate Points → Point Settings`

## What this screen controls

Point Settings is the global configuration page for UltimatePoints. It enables or disables the entire points system, sets the currency name shown throughout the board, controls which companion features (transfers, logs, user list, statistics) are active, defines rewards earned for posting actions, a random bonus applied on topic/post/edit events, and a registration bonus. The bottom of the page provides bulk admin actions: resetting all user point balances to zero and applying a group-wide add, subtract, or set adjustment.

## Fields

### Global

**Enable Points** (`points_enable`) — Master switch. When set to No, no points are awarded or deducted, and the disabled message is shown in place of the points interface. *Default:* Yes (1). *Valid range:* Yes / No.

**Disabled message** (`points_disablemsg`) — Text shown to users when the system is disabled. Maximum 50 characters. *Default:* `Ultimate Points is currently disabled!`. *Valid range:* free text, max 50 characters.

**Display points statistics on index** (`stats_enable`) — Shows a points statistics block on the main board index page. *Default:* Yes (1). *Valid range:* Yes / No.

### Currency Naming

**Points name** (`points_name`) — The singular display label used in place of the word "Points" across the board (post view, profile, transfer page, etc.). Maximum 30 characters. *Default:* `Points`. *Valid range:* free text, max 30 characters.

**Select main icon** (`points_icon_mainicon`) — Font Awesome 4.7 icon class rendered next to the points name in topics and the ACP heading. Use the icon picker to select; the value is stored as the CSS class string (e.g. `fa-university`). *Default:* `fa-university`. *Valid range:* any valid Font Awesome 4.7 class.

**Enable Ultimate Points List** (`uplist_enable`) — Enables the points ranking list (UP List page). *Default:* Yes (1). *Valid range:* Yes / No.

**Name userlist** (`points_name_uplist`) — Label used for the UP List page link and heading. Maximum 30 characters. *Default:* `UP List`. *Valid range:* free text, max 30 characters.

**Select icon for userlist** (`points_icon_uplist`) — Font Awesome 4.7 icon class for the userlist navigation entry. *Default:* `fa-users`. *Valid range:* any valid Font Awesome 4.7 class.

**Display an icon instead of points name** (`images_topic_enable`) — When Yes, a Font Awesome icon (the main icon) is rendered in topic view instead of the text points name. *Default:* Yes (1). *Valid range:* Yes / No.

**Display an icon instead of points name in profile** (`images_memberlist_enable`) — When Yes, a Font Awesome icon is rendered in user profiles instead of the text points name. *Default:* Yes (1). *Valid range:* Yes / No.

### Transfer Settings

**Allow Transfers** (`transfer_enable`) — Allows registered users to transfer/donate points to each other via the UCP transfer page. Requires the `u_use_transfer` permission to be granted as well. *Default:* Yes (1). *Valid range:* Yes / No.

**Transfer Fee** (`transfer_fee`) — Percentage withheld from each transfer. Stored as an integer. *Default:* 10. *Valid range:* 0–100 (values above 100 are rejected by the controller with an error).

**Notify user by PM of a transfer** (`transfer_pm_enable`) — When Yes, the recipient receives a PM notification when points are transferred to them. *Default:* Yes (1). *Valid range:* Yes / No.

**Allow Comments** (`comments_enable`) — Allows the sender to attach a comment to a transfer. Comments appear in the transfer logs. *Default:* Yes (1). *Valid range:* Yes / No.

### Logs and Display

**Enable points logs** (`logs_enable`) — Allows users to view their transfer and event log via the UCP. Requires the `u_use_logs` permission as well. *Default:* Yes (1). *Valid range:* Yes / No.

**Number of entries per page** (`number_show_per_page`) — Number of rows displayed per page in the logs and lottery history. *Default:* 15. *Valid range:* integers ≥ 5 (values below 5 are rejected by the controller with an error).

**Number of top rich members to display** (`number_show_top_points`) — How many users appear in the top-richest-users list. *Default:* 10. *Valid range:* non-negative integer; 0 hides the list.

### Posting Rewards (Advanced Points Settings)

These fields appear under the "Advanced Points Settings" fieldset. They are only evaluated when the per-forum points reward (Points Per Post or Points Per Topic) is non-zero; if the forum-level reward is 0, these additional amounts are not calculated.

**General points for adding attachments in a post** (`points_per_attach`) — Points awarded each time a post includes one or more attachments. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Additional points for each file attachment** (`points_per_attach_file`) — Additional points per individual attached file, stacked on top of `points_per_attach`. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per new poll** (`points_per_poll`) — Points awarded when a topic includes a new poll. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per option in a poll** (`points_per_poll_option`) — Additional points per poll option added. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per word on new topics** (`points_per_topic_word`) — Per-word reward for new topic posts, applied in addition to the forum-level per-topic amount. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per character on new topics** (`points_per_topic_character`) — Per-character reward for new topic posts. Applied alongside the per-word reward if both are set. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per word in new posts** (`points_per_post_word`) — Per-word reward for reply posts. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points per character in new posts** (`points_per_post_character`) — Per-character reward for reply posts. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Amount of points to be subtracted per user warning (set 0 to disable this feature)** (`points_per_warn`) — Points deducted from a user's balance each time a moderator issues a warning. Set to 0.00 to disable. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Registration Points Bonus** (`reg_points_bonus`) — Points awarded to a new user on first registration. Goes through `add_points()` in `core/functions_points.php`. Set to 0.00 to disable. *Default:* 50.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

### Random Bonus

**Point Bonus Chance** (`points_bonus_chance`) — Probability (0–100 %) that a user receives a random bonus on any new topic, post, or edit. Set to 0 to disable the random bonus entirely. *Default:* 0.00. *Valid range:* 0.00–100.00; no upper-bound validation in the controller beyond the 2-decimal rounding, so values above 100 are accepted by the form but are meaningless (100 % means always awarded).

**Point Bonus Value** — Pair of fields defining the range from which the bonus amount is drawn randomly:

- *Minimum* (`points_bonus_min`): lower bound. *Default:* 10.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.
- *Maximum* (`points_bonus_max`): upper bound. If set equal to the minimum, the bonus is a fixed amount. *Default:* 50.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

### Transfer mChat Settings

This fieldset is only rendered when the mChat extension (`dmzx.mchat.settings`) is detected at runtime.

**Enable posting in mChat for transfer** (`transfer_mchat_enable`) — When Yes, point transfers from members are posted as mChat messages. *Default:* No (0). *Valid range:* Yes / No.

## Bulk actions

### Reset users points

Resets every row in `phpbb_users.user_points` to 0 via a single `UPDATE phpbb_users SET user_points = 0`. This action is irreversible and is preceded by a confirmation dialogue. It does **not** touch bank balances (`phpbb_points_bank.holding`), the lottery jackpot, or any log records. Requires the `a_points` permission.

### Reset users logs

Deletes all rows from `phpbb_points_log` via `DELETE FROM phpbb_points_log`. This action is irreversible and is preceded by a confirmation dialogue. It does not affect any balance columns. Requires the `a_points` permission.

### Group Transfer

Applies a point adjustment to all non-pending members of a chosen group. Workflow:

1. Select the target group from the **User group** drop-down.
2. Enter the amount in the **Value** field.
3. Optionally provide a **Subject** and **Comment** for a PM to each affected member. Both fields must be filled or both left blank; providing only one triggers an error.
4. Select the **Function**: **Add** (credit each member's wallet by the amount), **Subtract** (debit each member's wallet by the amount), or **Set** (override each member's wallet to the exact amount).

The Bots and Guests groups are rejected. **Set** is destructive: it overwrites existing balances with no per-user audit of the difference. Add and Subtract post corresponding entries to the bbAccounts ledger (role `exp_admin_award` / `rev_admin_down`) when bbAccounts is configured; Set does not currently post a ledger entry (noted as TODO in the source).

### Per-user point edit

Individual user balances can be edited from `ACP → Users → Manage users` — find the user, open their account, and use the **User Points** field in the overview tab. This field is injected by the `acp_users_overview_options_append` event template.

## Things to know

- The currency name (`points_name`) is a display label only. Balances are stored as `DECIMAL(20)` in `phpbb_users.user_points`. Changing the name has no effect on stored values.
- `points_name` and `points_name_uplist` are stored in phpBB's core `phpbb_config` table, not in the extension's own `phpbb_points_config` table. The icon fields (`points_icon_mainicon`, `points_icon_uplist`) are likewise in `phpbb_config`.
- The registration bonus (`reg_points_bonus`) and random bonus (`points_bonus_min` / `points_bonus_max`) are both applied through `add_points()` in `core/functions_points.php`. When the bbAccounts integration is configured, `add_points()` posts a corresponding journal entry to the ledger (roles `exp_registration` and `exp_random` respectively). See [07-bbaccounts-integration.md](07-bbaccounts-integration.md) for setup details.
- Per-word and per-character posting rewards (`points_per_post_word`, `points_per_post_character`, etc.) are additive with the forum-level per-post/per-topic amounts defined in Chapter 2. Both sets of rewards are skipped entirely if the forum-level amount for that action type is 0.
- The random bonus chance field has no server-side upper-bound check in the controller; only the 2-decimal rounding is applied. Values above 100 are stored but a 100 % chance is already the effective maximum (the random roll is `rand(1, 100) <= chance`).
- `transfer_fee` is an integer percentage (not a decimal). The controller rejects values strictly greater than 100.
- The Transfer mChat Settings fieldset is conditionally rendered: it appears only when the mChat extension is enabled. If mChat is later disabled, the stored `transfer_mchat_enable` value is preserved but has no effect.
