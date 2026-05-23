# Chapter 7 — bbAccounts Integration

> ACP path: `ACP → Customise → Ultimate Points → bbAccounts Mapping`
> Introduced in UltimatePoints v1.3.4 (May 2026).

## What this does

When the bbAccounts extension is installed and the role mapping is configured,
every point movement in UltimatePoints also posts a balanced journal entry to the
double-entry ledger. Admins gain a full audit trail plus the bbAccounts trial
balance and account-ledger reports that answer "where did all the points come
from and go to?" UltimatePoints continues to work without bbAccounts; the
integration is a soft dependency — if bbAccounts is absent, disabled, or only
partially mapped, the legacy denormalised storage path is used unchanged.

## Prerequisites

1. **bbAccounts extension** (vendor `avathar`, package `bbaccounts`) installed
   and enabled. Version 1.3.2-alpha or higher is required. If it is not present,
   the bbAccounts Mapping page shows a notice and a link to the repository instead
   of the mapping form.

2. **Chart of accounts seeded.** Create the accounts in the bbAccounts ACP before
   returning here to map them. Subledger configuration is critical — the bbAccounts
   ledger service (`service/ledger.php`) validates each journal entry at post time
   and rejects entries whose subledger type does not match the account's
   configuration. Getting this wrong produces runtime failures, not install-time
   ones.

   The recommended accounts for a standard UltimatePoints setup, and their
   required subledger configuration:

   | Account code | Account name | Subledger type | Why |
   |---|---|---|---|
   | `2100` | User Wallets | `customer` | Each user's wallet is a per-user subledger account; UP posts `subledger_user_id` on every wallet leg. Non-customer configuration will fail validation at runtime. |
   | `2110` | Bank Holdings | `customer` | The bank deposit and withdrawal paths post `subledger_user_id` on **both** legs. Non-customer configuration will fail at validation. |
   | `2200` | Lottery Pool | **non-subledger** | The jackpot is a single communal pool — no individual user owns it. Configuring this as a subledger account will fail validation when a lottery ticket purchase or jackpot payout is posted. |
   | `4000` | Revenue | non-subledger | Receives the credit leg of all five UP revenue roles in a simple setup. Can be split into separate accounts for finer granularity. |
   | `5010` | Posting Rewards | non-subledger | Expense account for posting/topic activity rewards (`exp_posting`). |
   | `5020` | Registration Bonus | non-subledger | Reserved for the registration-bonus role (`exp_registration`) — not yet wired to a `post_to_ledger` call (see [Role mapping table](#the-13-role-mappings) note). |
   | `5030` | Random Bonus | non-subledger | Expense account for random on-post bonuses (`exp_random`). |
   | `5040` | Admin Award | non-subledger | Expense account for admin-add adjustments and group-transfer adds (`exp_admin_award`). |
   | `5050` | Bank Interest | non-subledger | Expense account for bank interest accrual (`exp_bank_int`). |

   The default bbAccounts seed also includes `3010 Opening Balances` (equity
   type), which is the conventional contra account for the one-shot backfill
   described below.

## The 13 role mappings

The mapping page shows one dropdown per role. Each dropdown is pre-filtered to
accounts of the recommended type and subledger shape, though the filter is
advisory — any account can be selected. Roles left unmapped (set to "—
(unmapped)") silently skip the bbAccounts posting for that role and continue
using the legacy denormalised path. A "Mapping status" counter at the top of the
form shows how many of the 13 roles are mapped.

Config keys are stored as `ultimatepoints_acct_<role>` in `phpbb_config`.

| UP role | Config key suffix | Type | Subledger | Recommended account | What posts hit it |
|---|---|---|---|---|---|
| `user_wallets` | `user_wallets` | liability | customer | `2100 User Wallets` | CR on posting reward, registration bonus, random bonus, bank withdrawal, bank interest, admin award, group transfer (add), robbery success (robber leg), transfer received. DR on bank deposit, lottery ticket purchase, pay-to-post charge, attachment charge, warning penalty, robbery penalty, admin deduction, group transfer (subtract), robbery success (victim leg), transfer sent. |
| `bank_holdings` | `bank_holdings` | liability | customer | `2110 Bank Holdings` | CR on bank deposit. DR on bank withdrawal, bank fees cron (per user). |
| `lottery_pool` | `lottery_pool` | liability | — | `2200 Lottery Pool` | CR on lottery ticket purchase. DR on jackpot payout. |
| `rev_post_costs` | `rev_post_costs` | revenue | — | `4000 Revenue` (or a dedicated sub) | CR on pay-to-post topic charge, pay-to-post reply charge, transfer fee. |
| `rev_attach_costs` | `rev_attach_costs` | revenue | — | `4000 Revenue` (or a dedicated sub) | CR on attachment download charge. |
| `rev_penalty` | `rev_penalty` | revenue | — | `4000 Revenue` (or a dedicated sub) | CR on warning point deduction, failed robbery penalty. |
| `rev_bank_fees` | `rev_bank_fees` | revenue | — | `4000 Revenue` (or a dedicated sub) | CR on bank maintenance fee (cron, per user). |
| `rev_admin_down` | `rev_admin_down` | revenue | — | `4000 Revenue` (or a dedicated sub) | CR on admin manual deduction, admin group transfer (subtract). |
| `exp_posting` | `exp_posting` | expense | — | `5010 Posting Rewards` | DR on new topic posted, new post, topic edit reward (positive diff only), post edit reward (positive diff only). |
| `exp_registration` | `exp_registration` | expense | — | `5020 Registration Bonus` | **Not yet wired.** The role exists in the mapping and the config key is seeded, but the registration-bonus path (`user_add_modify_data` event, `event/listener.php:764`) injects the starting balance directly into the SQL insert and does not call `post_to_ledger`. Opening balances for existing users are covered by the backfill. New-user bonus ledger posting is a known gap (TODO in source). |
| `exp_random` | `exp_random` | expense | — | `5030 Random Bonus` | DR on random post bonus (`functions_points.php:1016`). |
| `exp_bank_int` | `exp_bank_int` | expense | — | `5050 Bank Interest` | DR on bank interest accrual cron, per user (`functions_points.php:599`). |
| `exp_admin_award` | `exp_admin_award` | expense | — | `5040 Admin Award` | DR on admin manual award (`points_points_edit.php:148`), admin group transfer add (`admin_controller.php:380`). |

### Notes on specific roles

**`user_wallets` — two-subledger transfer and robbery entries.** When a
peer-to-peer transfer or a successful robbery posts, both legs of the journal
entry reference `user_wallets`, but each leg carries a different
`subledger_user_id`: the sender/victim on the debit side and the
recipient/robber on the credit side. This is the standard bbAccounts pattern
for an intra-account movement between two subledger owners; filter the account
ledger for a specific user to see only their movements.

**`rev_post_costs` — transfer fee.** The transfer fee is booked as revenue via
`rev_post_costs`, not a dedicated fee role. This keeps the chart compact by
default. If you want to distinguish pay-to-post charges from transfer fees, map
`rev_post_costs` to a separate account.

**`exp_registration` — not yet posting.** You can map this role now so the
mapping is ready when the wiring lands, but it will not produce journal entries
for new registrations until the event listener is updated.

## The opening-balance backfill

The backfill is a one-shot, on-demand operation that creates opening journal
entries in bbAccounts for every user whose current legacy balance differs from
what the bbAccounts subledger already shows (the diff approach means it is safe
to run even if dual-write has already been running for a while — only the gap
is posted).

### When to run it

Run the backfill after you have:
1. Created the accounts in the bbAccounts ACP.
2. Mapped the roles on this page and saved.
3. Verified the chart of accounts is set up with the correct subledger types.

The backfill button is visible only when the `user_wallets` role is mapped and
at least one active equity-type account exists in bbAccounts. The button
disappears once the backfill has been run.

### What to do

1. Map all roles via the form at the top of this page and click **Save
   mappings**.
2. In the **Backfill from existing balances** section, select the equity contra
   account from the **Equity contra account** dropdown. The bbAccounts default
   seed includes `3010 Opening Balances`, which is the conventional choice.
3. Click **Run backfill now**.

### What the backfill does

The backfill (`admin_controller.php:1222`) queries every user with a non-zero
`user_points` value or a non-zero `phpbb_points_bank.holding` row via a LEFT
JOIN, and for each user:

- If the `user_wallets` role is mapped and the user has a non-zero wallet
  balance, it computes the diff between `user_points` and the current bbAccounts
  subledger balance for that user, and adds a credit leg to the opening entry if
  the diff is non-zero.
- If the `bank_holdings` role is mapped and the user has a non-zero bank
  holding, the same diff logic applies for the bank account.
- A balancing debit leg against the chosen equity account is appended so the
  entry is perfectly balanced.
- If both wallet and bank diffs are zero for a user, that user is skipped
  ("already reconciled").

After the user loop, if the `lottery_pool` role is mapped, a separate entry is
posted for the current jackpot balance (again computing the diff against the
existing bbAccounts account balance).

Any entry that bbAccounts rejects at post time is logged to the admin log as
`LOG_BBACCOUNTS_POST_FAILED` with the context `backfill/user_<id>` or
`backfill/jackpot`. The backfill does not abort on a single failure — it
continues processing remaining users. Check the admin log after running it if
you suspect any entries were rejected.

### After completion

- The config flag `ultimatepoints_bbaccounts_backfilled` is set to `1`.
- The backfill section disappears from the page.
- The **bbAccounts as source of truth** section becomes visible.
- An admin log entry `LOG_BBACCOUNTS_BACKFILL_DONE` is written with the counts
  of users processed and the net wallet, bank, and jackpot totals posted.
- From this point on, every UltimatePoints mutation on a mapped role
  dual-writes: the legacy `user_points`/`bank.holding` column is updated as
  before, and a journal entry is also posted.

### What happens if some roles are not mapped when backfill runs

The backfill only posts legs for the roles that are mapped at the time it runs:
`user_wallets` (required), `bank_holdings` (optional), and `lottery_pool`
(optional). If `bank_holdings` is not mapped, bank balances are simply not
included in the opening entries — the bbAccounts bank account will start at
zero and fill in from dual-write going forward, leaving a gap for historical
balances. Running the backfill with all three balance roles mapped is strongly
recommended.

## Source-of-truth toggle (canonical flip)

After the backfill is complete, the **bbAccounts as source of truth** section
becomes visible. This section controls the `ultimatepoints_bbaccounts_canonical`
config flag (default `0`).

**`0` — Legacy column (dual-write).** This is the default state after backfill.
Both the legacy columns (`phpbb_users.user_points`,
`phpbb_points_bank.holding`, `phpbb_points_values.lottery_jackpot`) and the
bbAccounts ledger are written on every mutation. The legacy column is still
the authoritative figure read by leaderboards, profile pages, and all other
display paths. The ledger mirrors it. The bbAccounts trial balance and account
ledgers are available for audit and reporting.

**`1` — bbAccounts ledger (single-write).** The bbAccounts journal becomes the
sole writer. Every mutation is a single `create_entry()` call.
`post_to_ledger()` then refreshes the legacy columns as downstream caches so
that leaderboards, profile pages, and all other reads continue to work without
modification. The legacy mutator functions (`add_points`, `substract_points`,
`set_points`, `set_bank`) no-op when this flag is `1` — they early-return
without touching `user_points` or `holding` directly.

Flipping back to `0` is supported at any time. On the next mutation, the dual-
write resumes — both the journal and the legacy column are updated, and the
legacy column resumes authority. The toggle is logged in the admin log as
`LOG_BBACCOUNTS_CANONICAL_ON` or `LOG_BBACCOUNTS_CANONICAL_OFF`.

### Recommended pre-flight before flipping to `1`

- All 13 role mappings are complete (or you have accepted the gaps for any
  unmapped roles).
- The **bank-deposit path** has been smoke-tested in dual-write mode (`0`).
  This path posts `subledger_user_id` on both legs; a subledger mis-config on
  `2110 Bank Holdings` would have failed here rather than silently.
- Ideally, the lottery ticket purchase and jackpot payout paths have also been
  smoke-tested.
- The admin log shows no `LOG_BBACCOUNTS_POST_FAILED` entries from the dual-
  write period.
- The bbAccounts trial balance grouped by `currency_code = POINTS` reconciles
  to the sum of `user_points` across all users plus bank holdings plus the
  jackpot (within rounding).

## Reading a UP-posted journal entry

### Posting reward (5 points for a new post)

```
DR 5010 Posting Rewards                                    5.00
CR 2100 User Wallets  (subledger_user_id: <poster>)        5.00
```

`subledger_user_id` is the phpBB `user_id` of the member who earned the points.
In the bbAccounts account-ledger view, filter account `2100` by
`subledger_user_id = <user_id>` to see only that member's wallet history. The
unfiltered `2100` ledger shows all wallet movements for all users; the
`subledger_user_id` column lets you drill into one person's balance.

### Bank deposit (100 points moved from wallet to bank)

```
DR 2100 User Wallets  (subledger_user_id: <user>)        100.00
CR 2110 Bank Holdings (subledger_user_id: <user>)        100.00
```

Both legs carry the same `subledger_user_id`. This is an intra-liability
movement — no revenue or expense account is involved, because no points were
created or destroyed, only moved between the two balance pools that belong to
the same user. The trial balance is unchanged by a deposit or withdrawal.

### Peer-to-peer transfer (sender → recipient, 50 points, no fee)

```
DR 2100 User Wallets  (subledger_user_id: <sender>)       50.00
CR 2100 User Wallets  (subledger_user_id: <recipient>)    50.00
```

Both legs hit account `2100` but with different `subledger_user_id` values. The
account's total balance is unchanged; only the distribution between subledger
owners shifts. When a transfer fee applies, a second entry is posted:

```
DR 2100 User Wallets  (subledger_user_id: <sender>)        5.00  ← fee
CR 4000 Revenue                                            5.00
```

(The fee leg uses the `rev_post_costs` role, which maps to the configured
revenue account — not a dedicated transfer-fee account unless you have split
the mapping.)

## When journal posts fail

When bbAccounts rejects a journal entry at runtime, UltimatePoints logs an
admin log row keyed `LOG_BBACCOUNTS_POST_FAILED`. The log message includes
the role pair that failed (e.g. `user_wallets/bank_holdings`) and the exception
message from the bbAccounts ledger service. In dual-write mode the legacy
`user_points` update has already happened (or is about to), so user-visible
behaviour is unaffected — the failure is a bookkeeping shortfall, not a points
loss. Check `ACP → Logs → Admin log` and filter for "bbAccounts" after any
suspected failure.

**Most common cause:** subledger type mismatch. The bbAccounts ledger service
validates that if an entry leg carries a non-zero `subledger_user_id`, the
target account must be configured as a subledger account, and vice versa. The
two likeliest mis-configurations are:

- `2110 Bank Holdings` created as a non-subledger account — the bank deposit
  and withdrawal paths both pass `subledger_user_id` on that leg, which
  bbAccounts rejects.
- `2200 Lottery Pool` created as a subledger account — the lottery ticket
  purchase and jackpot payout pass `subledger_user_id = 0` on the pool leg,
  which a subledger account rejects.

**Unmapped role — silently skipped, not failed.** If either the debit or credit
role in a pair is unmapped (account ID `= 0`), `post_to_ledger()` returns
immediately without posting anything and without writing a log entry
(`functions_points.php:292`). This is a silent no-op, not an error. If you
un-map a role after backfill, subsequent movements for that role will stop
producing journal entries with no warning.

For recovery steps see [Chapter 8 — Troubleshooting](08-troubleshooting.md).

## Things to know

- **The integration is purely additive.** UltimatePoints' own logic, balances,
  and UI are unchanged regardless of whether bbAccounts is installed. All points
  features continue to work at full capacity when bbAccounts is absent.

- **Bank interest and bank fees** are posted by the cron job
  (`run_bank()`, `functions_points.php:572`). Bank interest is posted per user
  as `DR exp_bank_int / CR bank_holdings`; bank fees per user as `DR bank_holdings
  / CR rev_bank_fees`. The legacy bulk SQL UPDATE runs in the same pass, keeping
  `holding` in sync in dual-write mode. See [Chapter 3](03-bank.md) for cron
  scheduling details.

- **Lottery** posts a `DR user_wallets / CR lottery_pool` entry on each ticket
  purchase, and a `DR lottery_pool / CR user_wallets` entry on a jackpot payout.
  In canonical mode (`1`), `post_to_ledger` also updates `phpbb_points_values.lottery_jackpot`
  as the cache column. See [Chapter 4](04-lottery.md).

- **Robbery posts on both outcomes.** A successful robbery posts
  `DR user_wallets[victim] / CR user_wallets[robber]`. A failed robbery posts
  `DR user_wallets[attacker] / CR rev_penalty` if `robbery_loose > 0`. See
  [Chapter 5](05-robbery.md).

- **Admin group transfer — "set" mode does not post a ledger entry.** The add
  and subtract modes loop over group members and post per-user journal entries.
  The set mode (which resets every member's balance to a fixed amount) requires
  a per-user diff calculation to determine the direction and magnitude of each
  entry; this is not yet implemented and is marked as a TODO in
  `admin_controller.php:404`. The legacy bulk SQL UPDATE runs as normal.

- **Admin reset-all** (`admin_controller.php:276`) zeroes all `user_points` via
  a bulk UPDATE. It does not call `post_to_ledger`. If you are using bbAccounts,
  issue the reset with care — the ledger will not reflect the zeroing until the
  next dual-write mutation per user (or until you run a manual journal entry in
  bbAccounts to write down the balances).

- **`exp_registration` is reserved but not yet wired.** The registration-bonus
  event (`user_add_modify_data`) injects the starting balance directly into the
  phpBB user-insert SQL. There is no `post_to_ledger` call for new-user bonus
  points. The backfill covers existing users' opening balances; new registrations
  after backfill will have their wallet balance in the legacy column but no
  corresponding opening entry in bbAccounts until this path is wired.

- **If you un-map a role after backfill**, subsequent mutations involving that
  role will silently skip the ledger post. The legacy column continues to update,
  so user balances are unaffected, but the ledger will diverge from the legacy
  totals. Re-map the role and post manual corrective entries in bbAccounts to
  reconcile.

- **bbAccounts version required:** 1.3.2-alpha or higher. Earlier versions lack
  the `list_accounts()` method on the ledger service, which the mapping page
  dropdown depends on.
