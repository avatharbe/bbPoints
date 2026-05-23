# Chapter 5 — Robbery Settings

> ACP path: `ACP → Customise → Ultimate Points → Robbery Settings`

## What robbery does

When the robbery module is enabled, a logged-in user with the `u_use_robbery`
permission may target another user by name, specify an amount to steal, and
submit the attempt. The handler draws `rand(0, 100)` and compares it against
`robbery_chance`; if the random value is ≤ `robbery_chance` the attempt
succeeds and the specified amount is moved from the target's wallet to the
attacker's wallet. If the attempt fails and `robbery_loose` is greater than
zero, the attacker immediately loses a penalty equal to `attacked_amount / 100 *
robbery_loose` from their own wallet. Only `phpbb_users.user_points` (the
wallet) is ever touched; bank holdings stored in `phpbb_points_bank.holding`
are not affected by robbery in either outcome (see [Bank
protection](#bank-protection) below).

## Fields

The Robbery Settings screen has one main fieldset. A second fieldset for mChat
integration appears only when the dmzx mChat extension is installed.

**Enable Robbery Module** (`robbery_enable` in `phpbb_points_config`) — Master
switch. When set to No, all robbery pages return a "Robbery Disabled" error and
no attempt can be submitted. Existing points are not affected. *Default:* Yes
(1). *Valid range:* Yes / No.
(`admin_controller.php:825, 870–873`)

**Send a Notification to the robbed user** (`robbery_notify` in
`phpbb_points_config`) — When set to Yes, a phpBB notification is dispatched to
the target on a successful robbery, and to the attacker on a failed one. *Default:*
Yes (1). *Valid range:* Yes / No.
(`admin_controller.php:826, 874–877`)

**Chance to make a successful robbery** (`robbery_chance` in
`phpbb_points_values`) — Integer or decimal percentage used as the upper bound
of the success roll. The handler generates `rand(0, 100)` and considers the
attempt successful if the result is ≤ this value. The controller rejects values
≤ 0 (`ROBBERY_CHANCE_MINIMUM`) and values > 100 (`ROBBERY_CHANCE_ERROR`).
*Default:* 50 (50 %). *Valid range:* 0.01–100.00.
(`admin_controller.php:829, 834–843, 882`)

**Penalty on failed robbery** (`robbery_loose` in `phpbb_points_values`) —
Percentage of the attempted theft amount that the attacker forfeits when the
roll fails. For example, at 50 % a failed attempt to steal 200 points costs the
attacker 100 points. The controller rejects values ≤ 0 (`ROBBERY_LOOSE_MINIMUM`)
and values > 100 (`ROBBERY_LOOSE_ERROR`). Set to 0 to disable penalties; the
controller's minimum check prevents saving 0 directly — admins who genuinely
want no penalty must do so by disabling the module or adjusting via direct DB
edit. *Default:* 50 (50 %). *Valid range:* 0.01–100.00.
(`admin_controller.php:830, 846–855, 883`)

**Percentage of maximum robbery** (`robbery_max_rob` in `phpbb_points_values`)
— Caps the amount a user may attempt to steal in a single attempt, expressed as
a percentage of the target's current wallet balance. An attempt requesting more
than `target_wallet / 100 * robbery_max_rob` is rejected with
`ROBBERY_MAX_ROB`. The controller rejects values ≤ 0 (`ROBBERY_MAX_ROB_MINIMUM`)
and values > 100 (`ROBBERY_MAX_ROB_ERROR`). *Default:* 10.00 (10 %). *Valid
range:* 0.01–100.00.
(`admin_controller.php:831, 858–867, 884`)

### mChat integration (conditional)

The following field is only displayed if the dmzx mChat extension is installed
and registered in the container (`admin_controller.php:891–893`).

**Enable posting in mChat for robbery** (`robbery_mchat_enable` in
`phpbb_config`) — When set to Yes, a mChat message is posted on both successful
and failed robbery attempts. *Default:* 0 (No, set in migration
`ultimatepoints_1_2_2.php:28`). *Valid range:* Yes / No.
(`admin_controller.php:879, 902`)

## Bank protection

Only the target's wallet (`phpbb_users.user_points`) is at risk. Neither
handler — `core/points_robbery.php` nor `core/points_robbery_user.php` —
queries or updates `phpbb_points_bank` or any bank-related table at any point
in the request. A target who holds their balance in the bank rather than their
wallet exposes only the wallet portion to theft. This distinction is worth
communicating clearly to users: depositing funds into the bank is an effective
way to protect them from robbery.

## Known bug (v1.3.4) — non-atomic update

On a successful robbery the transfer is performed by two sequential calls with
no enclosing database transaction:

```
core/points_robbery.php:261  add_points(attacker, amount)
core/points_robbery.php:262  substract_points(target, amount)
```

```
core/points_robbery_user.php:259  add_points(attacker, amount)
core/points_robbery_user.php:260  substract_points(target, amount)
```

If the PHP process or the database connection is terminated between the two
statements, points are created or destroyed without a trace. The same
structural bug affects the peer-to-peer transfer flow.

Note: CLAUDE.md quotes `points_robbery.php:261/262` and
`points_robbery_user.php:259/260` — these are accurate for the current working
copy.

The consolidated list of known bugs is in [08-troubleshooting.md](08-troubleshooting.md).

**Mitigation:** enabling the bbAccounts integration (see
[07-bbaccounts-integration.md](07-bbaccounts-integration.md)) replaces the two
separate UPDATEs with a single double-entry ledger record, which is atomic by
construction. Until that integration is active, the two-UPDATE sequence remains
in place.

## Things to know

- **Bank holdings are protected.** Neither `phpbb_points_bank.holding` nor the
  `phpbb_points_bank` table is touched by the robbery handlers. Only wallet
  balances are at risk.

- **bbAccounts is notified on robbery.** Both handlers call `post_to_ledger(
  'user_wallets', 'user_wallets', amount, 'Robbery', ...)` immediately after the
  successful transfer (`points_robbery.php:265`,
  `points_robbery_user.php:262`). A failed-robbery penalty is also posted:
  `post_to_ledger('user_wallets', 'rev_penalty', lose, 'Failed robbery
  penalty', ...)` (`points_robbery.php:317`, `points_robbery_user.php:314`).
  Both calls are no-ops when bbAccounts is absent or its role accounts are
  unmapped.

- **Two entry points exist.** `core/points_robbery.php` serves the
  free-text-username form (`mode=robbery`); `core/points_robbery_user.php`
  serves the pre-targeted form (`mode=robbery_user&user_id=N`), typically
  reached from a user's profile. Both implement identical validation and
  payout logic.

- **The attacker must be able to cover the penalty.** Before submitting, the
  handler checks that the attacker's current wallet is ≥ `attacked_amount /
  100 * robbery_loose`. If not, the form is rejected with `ROBBERY_TO_MUCH`.
  This prevents users from initiating attempts they cannot afford to lose.

- **The `u_use_robbery` permission gates access, not point awards.** The
  permission is checked at the top of `main()` in both handlers; it controls
  who can reach the robbery page. There is no separate permission check inside
  the payout logic itself.

- **Notifications require `robbery_notify` to be enabled.** Both success and
  failure notifications are guarded by `$points_config['robbery_notify']`.
  Disabling this setting suppresses all robbery-related phpBB notifications and
  mChat messages in a single toggle.

- **There is no cooldown or per-period limit.** The current codebase does not
  enforce any waiting period between attempts. A user with sufficient balance
  can submit repeated robberies against the same target in rapid succession.

---

Previous: [04-lottery.md](04-lottery.md) | Next: [06-permissions.md](06-permissions.md)
