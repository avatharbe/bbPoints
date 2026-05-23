# Chapter 4 — Lottery Settings

> ACP path: `ACP → Customise → Ultimate Points → Lottery Settings`

## What the lottery does

Users buy tickets at a configurable cost; each ticket is one entry in the draw. At
the end of each draw period a round settles automatically: one ticket is picked at
random from the pool, a chance roll is applied, and if the roll succeeds the jackpot
is credited to the winner's wallet. If the roll fails (or if no tickets exist) the
jackpot accumulates — the current jackpot grows by `lottery_base_amount` each round
until a winner is drawn, at which point it resets to `lottery_base_amount`. There is
no maximum-tickets-to-draw threshold; draw timing is driven solely by the period
clock. The draw is triggered inline on page load (see [The lottery draw](#the-lottery-draw)
below) — there is no dedicated cron task.

## Fields

### General

**Enable Lottery Module** (`lottery_enable` in `phpbb_points_config`) — Master switch.
When set to No, the lottery page returns a "disabled" error and ticket purchase is
blocked. Existing tickets and the jackpot balance in `phpbb_points_values.lottery_jackpot`
are not affected. *Default:* Yes (1). *Valid range:* Yes / No.
(`admin_controller.php:538, 565–568`)

**Name of your Lottery** (`lottery_name` in `phpbb_points_values`) — Display name
shown on the lottery page, breadcrumb, and PM notifications. *Default:* `LOTTERY NAME`.
*Valid range:* free text, up to 100 characters.
(`admin_controller.php:546, 596`)

**Select icon for lottery** (`points_icon_lotteryicon` in `phpbb_config`) — Font
Awesome 4.7 icon class rendered next to the lottery name in navigation. Use the icon
picker to select. *Default:* `fa-ticket` (set in migration
`ultimatepoints_1_2_0.php:33`). *Valid range:* any valid Font Awesome 4.7 class.
(`admin_controller.php:579, 703`)

### Draw settings

**Base jackpot** (`lottery_base_amount` in `phpbb_points_values`) — The amount the
jackpot is seeded with on a fresh install and the amount it resets to after a winner
is drawn. It is also added to the jackpot each time a round produces no winner. If
you raise this value while a draw period is active, the difference is added to the
current jackpot immediately; if you lower it the jackpot is not reduced. *Default:*
50.00. *Valid range:* non-negative decimal, 2 decimal places.
(`admin_controller.php:543, 558–562, 583`)

**Draw period** (`lottery_draw_period` in `phpbb_points_values`) — How often a
lottery round is settled, entered in **hours** on the ACP form. The controller
multiplies the entered value by 3600 before storing it as seconds. Set to 0 to
disable drawings; tickets and the current jackpot are preserved. The form rejects
values less than 0 (`LOTTERY_DRAW_PERIOD_SHORT`). *Default:* 1 hour (stored as
3600 seconds). *Valid range:* 0 or any positive integer of hours.
(`admin_controller.php:544, 586–593`)

**Chance to win the Jackpot** (`lottery_chance` in `phpbb_points_values`) — An
integer percentage representing the probability that the randomly selected ticket
holder actually wins the jackpot. The draw picks one ticket at random, then rolls
`rand(0, 100)`; if the result is ≤ `lottery_chance` the holder wins. The controller
rejects values greater than 100. *Default:* 50 (50 %). *Valid range:* 0–100.
(`admin_controller.php:547, 551–554, 597`)

### Tickets

**Ticket costs** (`lottery_ticket_cost` in `phpbb_points_values`) — Points deducted
from a user's wallet when they purchase one ticket. Set to 0 to allow free tickets.
*Default:* 10.00 (from `insert_sample_data()`; schema column default is 0.00).
*Valid range:* non-negative decimal, 2 decimal places.
(`admin_controller.php:545, 595`)

**Allow multiple tickets** (`lottery_multi_ticket_enable` in `phpbb_points_config`)
— When set to No, each user may hold at most one ticket per draw period. When set to
Yes, users may buy additional tickets up to the `lottery_max_tickets` limit.
*Default:* Yes (1). *Valid range:* Yes / No.
(`admin_controller.php:539, 569–572`)

**Max. number of tickets** (`lottery_max_tickets` in `phpbb_points_values`) — The
maximum number of tickets a single user may hold in one draw period. Only enforced
when `lottery_multi_ticket_enable` is Yes. *Default:* 10. *Valid range:*
non-negative integer.
(`admin_controller.php:548, 598`)

### Display and notifications

**Display next draw time on index page** (`display_lottery_stats` in
`phpbb_points_config`) — When set to Yes, the board index shows the countdown to the
next draw alongside the most recent winner. *Default:* Yes (1). *Valid range:* Yes /
No.
(`admin_controller.php:540, 573–576`)

**Sender ID** (`lottery_pm_from` in `phpbb_points_values`) — The user ID used as
the PM sender when notifying the winner. Set to 0 to use the winner's own user ID
(the PM appears to come from the winner themselves). Any non-zero value is validated
against `phpbb_users`; saving an ID that does not exist triggers `NO_USER`. *Default:*
0. *Valid range:* 0 or any valid `user_id`.
(`admin_controller.php:549, 600–623`)

### mChat integration

**Enable posting in mChat for lottery** (`lottery_mchat_enable` in `phpbb_config`)
— When set to Yes, ticket purchases and jackpot wins are posted as mChat messages.
The setting is only displayed if the mChat extension is installed. *Default:* 0 (No,
from migration `ultimatepoints_1_2_2.php:27`). *Valid range:* Yes / No.
(`admin_controller.php:580, 693`)

## The lottery draw

### Inline-trigger pattern

The extension does not register a dedicated phpBB cron task class; there is no
`cron/task/` directory. Instead, the draw check is performed inline in two places:

- `event/listener.php:320–327` — inside the `core.index_modify_page_title` event
  handler, fired on every board index page load.
- `core/points_lottery.php:170–173` — inside the lottery page controller `main()`,
  on every load of the in-forum lottery page.

Both locations use the identical cadence guard:

```php
if ($points_values['lottery_draw_period'] != 0 && $points_values['lottery_last_draw_time'] + $points_values['lottery_draw_period'] - time() < 0)
{
    $this->functions_points->run_lottery();
}
```

`lottery_last_draw_time` (in `phpbb_points_values`) holds the Unix timestamp of the
last draw or the timestamp used to initialise the clock. `lottery_draw_period` is
stored in seconds. When their sum is in the past and at least one page load occurs,
`run_lottery()` fires. The draw is therefore driven by user activity, not by a
system cron job.

The index-page path additionally requires that `send_pm()` be available, including
`includes/functions_privmsgs.php` if not already loaded
(`event/listener.php:322–325`).

Setting `lottery_draw_period` to 0 disables both guards. Both conditions check
`!= 0` first, so a zero period never triggers a draw regardless of timestamps.

### What `run_lottery()` does (`core/functions_points.php:649–882`)

1. Counts the total number of tickets in `phpbb_points_lottery_tickets`
   (`lines 656–666`).
2. Selects one ticket at random using a database-specific `ORDER BY RAND()` /
   `RANDOM()` / `NEWID()` clause (`lines 668–696`).
3. **When at least one ticket exists** (`line 699`):
   - Generates `rand(0, 100)` and compares it to `lottery_chance`. If the roll
     succeeds (`rand <= lottery_chance`, `line 706`):
     - Records the winner's username and user ID to `lottery_prev_winner` and
       `lottery_prev_winner_id` (`lines 744–745`).
     - Sends a PM to the winner if `user_allow_pm = 1`
       (`lines 748–789`).
     - Inserts a row into `phpbb_points_lottery_history` with the winner and the
       jackpot amount (`lines 792–799`).
     - Posts a mChat message if mChat is installed and enabled (`lines 801–808`).
     - Sends a phpBB notification to the winner (`lines 810–823`).
     - Increments `lottery_winners_total` (`line 826`).
     - Credits the jackpot to the winner's wallet via `add_points()` (`line 829`).
     - **bbAccounts dual-write** — immediately after `add_points()`, calls
       `post_to_ledger('lottery_pool', 'user_wallets', jackpot_amount, ...)` (`line
       831`). See [bbAccounts interaction](#bbaccounts-interaction) below.
     - Resets `lottery_jackpot` to `lottery_base_amount` (`line 834`).
   - If the roll fails (`line 836`):
     - Increases `lottery_jackpot` by `lottery_base_amount` (`line 838`).
     - Inserts a "no winner" row into history (`user_id = 0`, `amount = 0`,
       `lines 842–848`).
     - Sets `lottery_prev_winner` and `lottery_prev_winner_id` to 0 (`lines 851–852`).
4. **When no tickets exist** (`total_tickets == 0`, `line 699` branch is skipped):
   all ticket-related and winner-related steps are bypassed entirely. The jackpot is
   neither awarded nor incremented — the code falls through directly to the reset
   block below. No history row is inserted for a zero-ticket round.
5. Clears all tickets: `DELETE FROM phpbb_points_lottery_tickets` (`lines 858–860`).
6. Advances `lottery_last_draw_time` to the next aligned period boundary (`lines
   862–881`), preventing repeated fires on subsequent page loads within the same
   period.

`run_lottery()` does **not** write an admin log row. No `$this->log->add()` call
exists inside `run_lottery()` itself.

### bbAccounts interaction

The jackpot payout **is** mirrored to the bbAccounts ledger. At `line 829`,
`add_points()` credits the winner's wallet. At `line 831`, `post_to_ledger(
'lottery_pool', 'user_wallets', $jackpot, ...)` is called unconditionally. Inside
`post_to_ledger()` (`functions_points.php:274–365`), the call is a no-op if
`$this->bbaccounts_ledger === null` (bbAccounts absent) or if either role account
ID is unmapped (config key `ultimatepoints_acct_lottery_pool` or
`ultimatepoints_acct_user_wallets` is 0). When both are mapped and bbAccounts is
present, a double-entry ledger record is created: debit `lottery_pool`, credit
`user_wallets`.

The rollover path (no winner — jackpot growth) is **not** mirrored to the ledger.
There is no `post_to_ledger` call in the no-winner branch (`lines 836–853`). Ticket
purchases are similarly not dual-written from `run_lottery()`; whether ticket
purchase itself posts to the ledger would need to be verified in `core/points_lottery.php`.

## History reset

The **Reset the Lottery history** action appears on the Lottery Settings page. It is
submitted via the hidden field `action_lottery_history` and is handled at
`admin_controller.php:643–680`. When confirmed, it executes:

```sql
DELETE FROM phpbb_points_lottery_history
```

The action is gated by the `a_points` permission (`admin_controller.php:650`). A
confirmation dialog is shown before the delete executes. On completion an admin log
row is written (`LOG_RESYNC_LOTTERY_HISTORY`).

What the reset **does**:
- Removes all rows from `phpbb_points_lottery_history` (past draw records and winner
  names).

What the reset **does not** do:
- It does not alter `lottery_jackpot`, `lottery_prev_winner`, `lottery_last_draw_time`,
  or any ticket rows. The current jackpot pool and active tickets are preserved.

## Disabling the lottery cleanly

1. Set **Enable Lottery Module** to No. This blocks the lottery page and prevents
   any new ticket purchases. If `lottery_enable` is not available to users via
   the `u_use_lottery` permission, you can also remove that permission as an
   additional guard.
2. Let the draw period elapse naturally. The inline trigger will fire once on the
   next board index or lottery page load, awarding the jackpot if any tickets exist
   and the chance roll succeeds (or rolling over if not). If you want to avoid this,
   set **Draw period** to 0 before disabling — this disables the draw clock and
   preserves the current tickets and jackpot.
3. (Optional) Use **Reset the Lottery history** to clear the history table once the
   final draw has settled.

Note: there is no built-in mechanism to refund outstanding tickets to their purchasers
when the lottery is disabled mid-period. Tickets in `phpbb_points_lottery_tickets`
are deleted by `run_lottery()` at the end of each draw, but they are not automatically
refunded if the draw is suppressed by disabling the module.

## Things to know

- **Jackpot is stored in `phpbb_points_values.lottery_jackpot`** as a single global
  value. It is not per-user and is not stored in `phpbb_config`.

- **The winning payout goes through `add_points()`** (`functions_points.php:829`).
  This means it is subject to the canonical-mode early-return: in Phase E
  (bbAccounts canonical mode), `add_points()` is a no-op and the jackpot is
  credited solely via `post_to_ledger()` → `refresh_legacy_cache_columns()`. In
  legacy mode, `add_points()` issues the direct `UPDATE phpbb_users SET user_points`
  and `post_to_ledger()` is a no-op (bbAccounts absent or roles unmapped).

- **The chance roll introduces non-determinism.** A round can produce no winner even
  when tickets exist, if `rand(0, 100) > lottery_chance`. In that case the jackpot
  grows by `lottery_base_amount` and tickets are still cleared. Users do not receive
  refunds. Repeat no-winner rounds are normal and expected.

- **When no tickets are sold in a period**, the draw fires but the jackpot is
  neither awarded nor incremented. No history row is written for a zero-ticket round.

- **Draw period is entered in hours, stored in seconds.** The ACP field
  `lottery_draw_period` accepts hours. The controller multiplies by 3600
  (`admin_controller.php:544`) before saving. The template variable
  `LOTTERY_DRAW_PERIOD` divides back by 3600 for display
  (`admin_controller.php:690`). If you edit the database row directly, use seconds.

- **`lottery_last_draw_time = 0` is used as a sentinel.** On save, if the stored
  `lottery_last_draw_time` is 0 and the new `lottery_draw_period` is non-zero, the
  controller sets `lottery_last_draw_time` to `time()` to start the clock
  (`admin_controller.php:627–630`). If `lottery_draw_period` is set to 0, the
  controller resets `lottery_last_draw_time` to 0 (`admin_controller.php:633–636`).

- **mChat posting requires the mChat extension.** The `LOTTERY_MCHAT_OPTIONS`
  section and the `lottery_mchat_enable` toggle are only rendered in the ACP if
  `dmzx.mchat.settings` is registered in the container
  (`admin_controller.php:682–685`).

---

Previous: [03-bank.md](03-bank.md) | Next: [05-robbery.md](05-robbery.md)
