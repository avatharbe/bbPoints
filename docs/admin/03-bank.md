# Chapter 3 — Bank Settings

> ACP path: `ACP → Customise → Ultimate Points → Bank Settings`

## What the bank does (for admins)

The bank is a separate, interest-bearing balance pool (`phpbb_points_bank.holding`) that is entirely distinct from a user's wallet (`phpbb_users.user_points`). Users opt in by opening an account from the in-forum bank page; once open, the account accrues interest and may be charged a periodic maintenance fee, both applied in bulk whenever the configured pay period has elapsed and a page request arrives (see [The interest accrual trigger](#the-interest-accrual-trigger) below). Users may deposit into and withdraw from their account subject to configurable minimums and a withdraw fee. The bank is also a target for the robbery feature; see [05-robbery.md](05-robbery.md) for that interaction.

## Fields

### Global

**Enable bank module** (`bank_enable` in `phpbb_points_config`) — Master switch for the bank feature. When set to No, the bank page is inaccessible and users cannot deposit, withdraw, or accrue interest. The stored bank balances in `phpbb_points_bank` are not affected. *Default:* Yes (1). *Valid range:* Yes / No. (`admin_controller.php:733, 758–761`)

**Name of your bank** (`bank_name` in `phpbb_points_values`) — Display name for the bank shown on the bank page, navigation breadcrumb, and notification messages. *Default:* `BANK NAME`. *Valid range:* free text, up to 100 characters. (`admin_controller.php:743, 773`)

**Select icon for bank** (`points_icon_bankicon` in `phpbb_config`) — Font Awesome 4.7 icon class rendered next to the bank name in navigation. Use the icon picker to select. *Default:* `fa-money`. *Valid range:* any valid Font Awesome 4.7 class. (`admin_controller.php:763, 794`)

### Interest

**Interest rate** (`bank_interest` in `phpbb_points_values`) — Percentage of a user's current holding credited as interest on each payout cycle. Stored as a decimal. The controller rejects values greater than 100. The interest amount per user is computed as `round(holding / 100 * bank_interest)`. *Default:* 0.00. *Valid range:* 0.00–100.00. (`admin_controller.php:736, 746–749, 766`)

**Interest payment time** (`bank_pay_period` in `phpbb_points_values`) — How often interest and the maintenance fee are applied, entered in **days** on the ACP form. The controller multiplies the entered value by 86400 before storing it as seconds. *Default:* 30 days (stored as 2592000 seconds). *Valid range:* any positive number of days; the bank page blocks access entirely if the stored value is less than 1 second. (`admin_controller.php:738, 768`)

**Disable interest at** (`bank_interestcut` in `phpbb_points_values`) — The holding threshold above which interest is not accrued. A user whose balance equals or exceeds this value receives no interest on that cycle. Set to 0 to apply interest regardless of holding size. *Default:* 0.00 (disabled). *Valid range:* non-negative decimal. (`admin_controller.php:741, 771`)

### Deposit and withdrawal

**Min. deposit** (`bank_min_deposit` in `phpbb_points_values`) — The minimum amount a user may deposit in a single transaction. Deposits below this threshold are rejected with an error. Set to 0 to allow any positive deposit. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places. (`admin_controller.php:740, 770`)

**Min. withdraw** (`bank_min_withdraw` in `phpbb_points_values`) — The minimum amount a user may withdraw in a single transaction. Withdrawals below this threshold are rejected with an error. Set to 0 to allow any positive withdrawal. *Default:* 0.00. *Valid range:* non-negative decimal, 2 decimal places. (`admin_controller.php:739, 769`)

**Withdraw fees** (`bank_fees` in `phpbb_points_values`) — Percentage of the holding deducted as a fee when a user withdraws. This is a withdrawal-time fee, not the periodic maintenance cost. The controller rejects values greater than 100. Set to 0 to disable withdrawal fees. *Default:* 0.00. *Valid range:* 0.00–100.00. (`admin_controller.php:737, 752–755, 767`)

### Maintenance cost

**The cost for maintaining a bank account** (`bank_cost` in `phpbb_points_values`) — A flat amount deducted from every account whose holding is at or above this cost, applied once per payout cycle at the same time as interest. Accounts whose holding is less than `bank_cost` are not charged. Set to 0 to disable the periodic maintenance fee. *Default:* 0.00. *Valid range:* non-negative integer (stored as integer in the SQL at runtime). (`admin_controller.php:742, 772`)

## The interest accrual trigger

The extension does not register a dedicated phpBB cron task class. There is no file in `cron/task/`. Instead, the payout check is performed inline in two places:

- `core/points_bank.php:169` — inside the bank page controller `main()`, on every page load of the in-forum bank view.
- `event/listener.php:330` — inside the `core.index_body_before` event handler, on every board index page load.

In both places the check is:

```php
if ((time() - $points_values['bank_last_restocked']) > $points_values['bank_pay_period'])
{
    $this->functions_points->run_bank();
}
```

`bank_last_restocked` (in `phpbb_points_values`) holds the Unix timestamp of the last successful payout run. If more than `bank_pay_period` seconds have elapsed since that timestamp and a user loads either the board index or the bank page, `run_bank()` fires and `bank_last_restocked` is immediately updated to the current time (`functions_points.php:578`). The payout is therefore driven by user activity, not by a system cron job.

### What `run_bank()` does (`core/functions_points.php:571–644`)

1. Updates `bank_last_restocked` to `time()` immediately (`line 578`).
2. Builds a `WHERE` clause for the interest-eligible accounts: `holding < bank_interestcut OR bank_interestcut = 0` (`lines 586–587`).
3. **When bbAccounts is configured in dual-write mode** and the `exp_bank_int` and `bank_holdings` roles are mapped: iterates over eligible accounts row by row and calls `post_to_ledger('exp_bank_int', 'bank_holdings', interest_amount, ...)` for each user where the computed interest is greater than zero (`lines 588–603`). The `post_to_ledger` call updates `phpbb_points_bank.holding` directly via `refresh_legacy_cache_columns`.
4. **When bbAccounts is absent or not in canonical mode**: issues a single bulk `UPDATE phpbb_points_bank SET holding = holding + round((holding / 100) * bank_interest) WHERE <eligible>` (`lines 608–614`). This UPDATE bypasses `add_points()` entirely.
5. **If `bank_cost` is non-zero**: applies the same dual-write / bulk-UPDATE pattern to deduct the maintenance fee from every account whose holding is at or above `bank_cost` (`lines 617–643`).

`run_bank()` adds **no admin log row**. The language key `LOG_MOD_POINTS_BANK_PAYS` is defined in `language/en/info_acp_ultimatepoints.php:55` but is never called from `run_bank()` in v1.3.4. The log key `LOG_MOD_POINTS_BANK` (line 776 of `admin_controller.php`) is written when an admin saves the Bank Settings form, not when the payout fires.

## Things to know

- **Bank balances are stored separately from wallets.** The bank holding is in `phpbb_points_bank.holding`; the wallet is in `phpbb_users.user_points`. The bulk Reset action on the Point Settings screen (`ACP → Customise → Ultimate Points → Point Settings`) zeroes `user_points` only; it does not touch `phpbb_points_bank.holding`.

- **Interest accrual bypasses `add_points()`.** In the legacy (non-bbAccounts) path, interest is applied as a direct bulk SQL `UPDATE` on `phpbb_points_bank`. It leaves no transfer log row in `phpbb_points_log` and no admin log row. The language key `LOG_MOD_POINTS_BANK_PAYS` exists but is unused in the current codebase.

- **bbAccounts interaction.** When bbAccounts is installed and the `exp_bank_int` / `bank_holdings` roles are mapped, interest accrual IS mirrored to the ledger via `post_to_ledger()` calls inside `run_bank()` (`functions_points.php:599`). The fee deduction is similarly mirrored (`functions_points.php:629`). Deposit and withdrawal transactions are also dual-written in `core/points_bank.php:332` and `core/points_bank.php:413`. See [07-bbaccounts-integration.md](07-bbaccounts-integration.md) for role mapping setup. When bbAccounts is absent, none of these paths execute.

- **The maintenance fee threshold is the holding floor, not a ceiling.** A user is charged `bank_cost` per cycle only when their holding is **at or above** `bank_cost`. An account whose holding is below the fee amount is not charged. Setting `bank_cost` to 0 disables the fee entirely.

- **Opening an account.** When a user opens an account (`action == 'createaccount'`), an `INSERT` is made into `phpbb_points_bank` with `holding = NULL` (no column in the insert array), `opentime = time()`, and `fees = 'on'` (`core/points_bank.php:290–295`). The account opens with a zero holding; no cost is charged at account creation — `bank_cost` is only applied at payout time, not at opening. There is no account-deletion feature exposed on the bank page; a user cannot close their account from the forum front-end.

- **The pay period is entered in days, stored in seconds.** The ACP form field `bank_pay_period` accepts days. The controller multiplies by 86400 (`admin_controller.php:738`) before saving. The template variable `BANK_PAY_PERIOD` divides back by 86400 for display (`admin_controller.php:783`). If you edit the database row directly, use seconds.

- **`bank_pay_period` must be at least 1 second.** The bank page `main()` method checks `if (1 > $points_values['bank_pay_period'])` and blocks access with an error if true (`core/points_bank.php:142–146`). Setting the period to 0 via direct database edit effectively disables the bank page for all users.

- **Withdraw fee (`bank_fees`) applies to withdrawals, not to the periodic payout.** The percentage entered here is deducted from the user's holding at withdrawal time. It is separate from `bank_cost`, which is a flat periodic maintenance deduction.

---

Previous: [02-forum-points.md](02-forum-points.md) | Next: [04-lottery.md](04-lottery.md)
