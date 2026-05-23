# Chapter 2 — Forum Points Settings

> ACP path: `ACP → Customise → Ultimate Points → Forum Points Settings`

## What this screen controls

Forum Points Settings is a bulk-write utility. It sets six per-forum point values — three earning rules and three cost rules — across every forum on the board simultaneously. The values are stored as columns in `phpbb_forums` and are read at post time for individual award and charge calculations. Per-forum values can also be set individually for each forum via `ACP → Forums → Manage forums` (see [How the per-forum values are set](#how-the-per-forum-values-are-set)).

## How the table works

There is no matrix table on this screen. The ACP page presents a single form with six fields. Submitting the form updates every row in `phpbb_forums` with the entered values after a confirmation dialogue. The confirmation warns that the update overwrites all current per-forum settings and cannot be reversed.

The individual per-forum values live in `phpbb_forums` as six `DECIMAL(10)` columns added by the install migration. They are set per forum via `ACP → Forums → Manage forums`: the UltimatePoints fieldset `Ultimate Points Settings` is injected into each forum's edit page via the `core.acp_manage_forums_display_form` event and the template at `adm/style/event/acp_forums_normal_settings_prepend.html`.

## How the per-forum values are set

Two routes:

1. **Bulk set (this screen):** Enter values and submit. All forums are updated to the same values. Useful for initial configuration. Repeated use overwrites any per-forum customisation done via route 2.
2. **Per-forum:** `ACP → Forums → Manage forums` → select or create a forum → `Ultimate Points Settings` fieldset. The same six fields appear, scoped to that one forum.

When a new forum is created, all six values initialise to 0.00 (set by the `core.acp_manage_forums_initialise_data` event handler in `event/listener.php:663`). The `phpbb_forums` column defaults installed by the migration (5.00, 0.05, 15.00) apply only to forums that existed before UltimatePoints was enabled.

## Earning fields

These fields control how many points a user earns for posting actions in the forum.

**Points Per Topic** (`forum_pertopic` in `phpbb_forums`) — Points awarded when a user creates a new topic (`mode == 'post'`). Must be greater than 0 for any award to fire for new topics; when 0, no topic reward is given and the global per-word/per-character bonuses from Chapter 1 are also skipped for new topics. *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_topic`). *Default for existing forums on first install:* 15.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters (6 on the per-forum form, 8 on the bulk form).

**Points Per Post** (`forum_perpost` in `phpbb_forums`) — Points awarded when a user creates a reply (`mode == 'reply'` or `mode == 'quote'`). Must be greater than 0 for any award to fire for replies; when 0, the global per-word/per-character bonuses from Chapter 1 are also skipped for replies. *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_post`). *Default for existing forums on first install:* 5.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points Per Edit** (`forum_peredit` in `phpbb_forums`) — Points awarded (positive diff only) when a user edits a topic (`mode == 'edit_topic'` or `mode == 'edit_first_post'`) or a post (`mode == 'edit'` or `mode == 'edit_last_post'`). Must be greater than 0 for any edit award to fire. See [Award conditions](#award-conditions) for the diff behaviour and the known bug. *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_edit`). *Default for existing forums on first install:* 0.05. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

## Cost fields

These fields control how many points a user must pay to perform an action in the forum. Each cost is charged only when the corresponding `f_pay_*` forum permission (Chapter [06-permissions.md](06-permissions.md)) is granted to the user and the field value is greater than 0.

**Points Per Attachment Download** (`forum_cost` in `phpbb_forums`) — Points deducted from the downloader's wallet each time they download an attachment in the forum. Requires the `f_pay_attachment` permission to be granted for the cost to apply. When the user's balance is insufficient, the download is blocked. *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_cost`). *Default for existing forums on first install:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points to pay for new topic** (`forum_cost_topic` in `phpbb_forums`) — Points deducted from the poster's wallet when they create a new topic. Requires the `f_pay_topic` permission to be granted for the cost to apply. When the user's balance is below this amount, the posting page is blocked with an error before submission (`core.modify_posting_auth` event, `event/listener.php:990`). *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_cost_topic`). *Default for existing forums on first install:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

**Points to pay for new post** (`forum_cost_post` in `phpbb_forums`) — Points deducted from the poster's wallet when they create a reply. Requires the `f_pay_post` permission to be granted for the cost to apply. When the user's balance is below this amount, the posting page is blocked with an error before submission (`core.modify_posting_auth` event, `event/listener.php:996`). *Default (bulk-set form):* 0.00 (stored in `phpbb_points_values.forum_cost_post`). *Default for existing forums on first install:* 0.00. *Valid range:* non-negative decimal, 2 decimal places, max 8 characters.

## Bulk-set behaviour

Submitting this form calls `UPDATE phpbb_forums SET forum_pertopic = ?, forum_perpost = ?, forum_peredit = ?, forum_cost = ?, forum_cost_topic = ?, forum_cost_post = ?` with no `WHERE` clause — every forum row is overwritten, including categories and link forums. Those forum types cannot receive posts anyway (phpBB enforces this), so the overwrite is harmless for them, but the form gives no warning about it.

A confirmation dialogue is shown before the update fires: "Are you sure you want to update all forum points with the given values? This step will overwrite all current settings and cannot made reversible!"

After a successful bulk update, the entered values are also saved to `phpbb_points_values` (keys `forum_topic`, `forum_post`, `forum_edit`, `forum_cost`, `forum_cost_topic`, `forum_cost_post`) so the form re-displays the last-used bulk values.

There is no per-column "apply to all" top-row affordance. The only bulk option is the whole-form submit that sets all six fields at once.

## Award conditions

The following conditions are evaluated inside `submit_post_end` in `event/listener.php:784`. All must be satisfied for a post to earn or be charged points.

- **Extension enabled** (`listener.php:788`): `$this->config['points_enable']` must be true. If the system is disabled, the entire handler returns immediately.
- **Forum must have a non-zero per-action value** (`listener.php:866, 879, 892, 925`): `forum_pertopic > 0` for new topics; `forum_perpost > 0` for replies; `forum_peredit > 0` for edits. When the relevant column is 0, no award fires and the global per-word/per-character bonuses from Chapter 1 are also skipped for that action. This is the single most common reason points are not awarded after installation — the bulk-set form defaults to 0.00 for all six fields.
- **No explicit `u_use_points` check in the post handler**: the `submit_post_end` handler does not check the `u_use_points` permission before awarding points. That permission gates the points UI (profile display, donation links, transfer page) but does not suppress the award calculation at post time. Any user who can post in a forum will receive the award regardless of whether they hold `u_use_points`.
- **Pay-to-post costs require `f_pay_*`** (`listener.php:834, 840`): a cost is charged only when the corresponding forum permission is granted. The `f_pay_topic` permission gates `forum_cost_topic`; `f_pay_post` gates `forum_cost_post`; `f_pay_attachment` gates `forum_cost`.
- **Post approval queue does not affect immediate award**: `core.submit_post_end` fires for all posts at submission time regardless of approval status. UltimatePoints does not check `post_visibility`. A post placed in a moderation queue awards (or charges) points immediately, not on approval.
- **Edit awards the positive diff only** (`listener.php:908, 911` for topic edits; `listener.php:941, 944` for post edits): the handler computes `difference = total_points − prev_points`. If the difference is positive, the difference is added to the wallet and the denorm column is updated. **Known bug (v1.3.4):** if the difference is zero or negative (the post was edited down to fewer words or a shorter message), the handler returns without adjustment — the wallet retains the originally awarded amount and the denorm column is not updated. See Chapter [08-troubleshooting.md](08-troubleshooting.md) for details. The bbAccounts integration eliminates this once enabled (reversal entries become first-class).
- **Random bonus always fires** regardless of forum reward values (`listener.php:798`): `random_bonus_increment()` is called unconditionally at the start of `submit_post_end`, before any forum-value check. A user can receive a random bonus even when the forum has zero earning values.

## Things to know

- **Per-word and per-character rewards are global, not per-forum.** The fields `points_per_post_word`, `points_per_post_character`, `points_per_topic_word`, and `points_per_topic_character` (Chapter [01-points.md](01-points.md)) are stored in `phpbb_points_values` and apply board-wide. This screen has no per-forum word-rate column. However, the global per-word/per-character calculation is skipped entirely when the forum-level reward for the action type is 0 (`listener.php:866, 879`).
- **`phpbb_posts` denorm columns** — `add_points_to_table()` in `core/functions_points.php:968` updates four columns on `phpbb_posts` after each award: `points_received` (total award), `points_topic_received` or `points_post_received` (mode-specific total, used as `prev_points` on subsequent edits), `points_attachment_received` (attachment count), and `points_poll_received` (poll option count). These are display and diff-tracking values only. The wallet truth is in `phpbb_users.user_points`.
- **Cost charges use `substract_points()`** (`core/functions_points.php:268`; note: the function name in the source is spelled "substract", not "subtract"). Pay-to-post and attachment-download charges are logged through this function. When bbAccounts is configured, a corresponding ledger entry is posted.
- **Setting a value to 0 disables that rule.** There is no separate enable/disable toggle per forum rule. 0 means no award and no cost for that column.
- **The bulk-set form and the per-forum form share the same `phpbb_forums` columns.** Using the bulk-set form after customising individual forums will overwrite every forum's per-forum values.
- **Attachment download cost** (`forum_cost`) is evaluated at download time via the `core.download_file_send_to_browser_before` and `core.parse_attachments_modify_template_data` events, not via `submit_post_end`. The cost is charged when the file is downloaded, not when the post is created. The `display_cat != 1` check in the download handler (`listener.php:445, 629`) ensures the cost check is skipped for category forums, though categories cannot hold attachments in practice.

---

Previous: [01-points.md](01-points.md) | Next: [03-bank.md](03-bank.md)
