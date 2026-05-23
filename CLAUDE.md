# bbPoints — phpBB Points / Gamification Extension

## Overview

**bbPoints** (formerly `dmzx/ultimatepoints`) is a feature-rich point-economy extension for phpBB. Users earn points for posting / replying / registering, hold them in a wallet or savings bank, and spend them via lottery, robbery, peer-to-peer transfer, attachment downloads, or pay-to-post forums.

v2.0 is a coordinated rename + architectural simplification: the PHP-level identity moved from `dmzx/ultimatepoints` to `avathar/bbpoints`, the v1.3.x dual-write infrastructure was retired, and bbAccounts is now the canonical store (a hard dependency). The legacy denormalised columns `phpbb_users.user_points` and `phpbb_points_bank.holding` survive as read caches refreshed on every ledger post.

The v1.x line is preserved at the `v1.3.4-final` git tag.

- **Author (original):** dmzx & posey — last public CDB release v1.2.2, 2018-05-30. Credited in license/headers.
- **Maintainer (v2.0+):** Andy Vandenberghe (Sajaki)
- **Version (this working copy):** 2.0.0 (2026-05-23)
- **License:** GPL-2.0
- **Working copy:** `/Users/Andreas/Sites/avathar/forum/ext/avathar/bbpoints`
- **Git repo:** `/Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbpoints`
- **Remote:** `https://github.com/avatharbe/bbPoints.git` (default branch: `main`; tags `v1.3.4-final` and `v2.0.0`)
- **Composer name / namespace:** `avathar/bbpoints` and `avathar\bbpoints`. DB-stored identifiers (table names, config keys, ACL labels) preserved verbatim from v1.x — see "What stays unchanged" in `contrib/specs/2026-05-23-rename-to-avathar-bbpoints-design.md`.

## Sync workflow

Working copy at `/Users/Andreas/Sites/avathar/forum/ext/avathar/bbpoints` is the edit surface. Direction is **working copy → repo** (rsync excluding `.git`). Commit + push from the repo.

## Compatibility

- **phpBB 3.3.16:** Target. Modern Symfony 3 DI, EventSubscriberInterface listeners, typed `driver_interface` injection, 100% Twig templates. EPV-clean.
- **PHP:** ≥7.4 declared in composer.
- **bbAccounts:** required (`avathar/bbaccounts ^1.0.0-alpha`). The `ext.php` `is_enableable()` blocks enable if bbAccounts is missing or disabled.

## Storage model (v2.0)

| Concern | Where | Type | Authoritative? |
|---|---|---|---|
| Per-user wallet (truth) | bbAccounts ledger — `user_wallets` subledger account | Sum of journal lines per user | **Yes** |
| Per-user wallet (cache) | `phpbb_users.user_points` | DECIMAL(20) | No — refreshed on every ledger post |
| Per-user bank (truth) | bbAccounts ledger — `bank_holdings` subledger account | Sum of journal lines per user | **Yes** |
| Per-user bank (cache) | `phpbb_points_bank.holding` | DECIMAL(20) | No — refreshed on every ledger post |
| Lottery jackpot | `phpbb_points_values.lottery_jackpot` | DECIMAL(20) | Cache of `lottery_pool` ledger account net |
| Per-post denorm | `phpbb_posts.points_received` + variants | DECIMAL — display only | No |
| Transfer/robbery log | **Removed in v2.0** | — | Use bbAccounts journal entries instead |

Templates and SQL-join paths continue to read `phpbb_users.user_points` for display + leaderboard sort. The cache is reproducible from the ledger at any time (see the planned "Resync caches from ledger" ACP diagnostic — language key `RESYNC_CACHES_*` reserved, action not yet implemented; see `bbpoints/issues/2`).

## Mutation funnel (`core/functions_points.php`)

The mutation funnel was simplified in v2.0. There is now ONE writer of point movements: `post_to_ledger`. The legacy `add_points` / `substract_points` / `set_points` / `set_bank` functions are no-op stubs retained for source-compat with existing call sites that paired them with `post_to_ledger` calls during the v1.3.x dual-write era.

| Function | Behaviour in v2.0 |
|---|---|
| `post_to_ledger($debit_role, $credit_role, $amount, ...)` | Posts a balanced journal entry to bbAccounts AND refreshes the affected user's cache columns via `refresh_legacy_cache_columns()`. Throws on ledger rejection. Logs `LOG_BBPOINTS_ROLE_UNMAPPED` if either role is unmapped. |
| `refresh_legacy_cache_columns(...)` | Updates `phpbb_users.user_points` / `phpbb_points_bank.holding` / `phpbb_points_values.lottery_jackpot` by delta, based on the role legs. |
| `add_points`, `substract_points`, `set_points`, `set_bank` | **No-op stubs** — deprecated; pair callers with `post_to_ledger`. |
| `add_points_to_table($post_id, ...)` | UPDATE on `phpbb_posts` denorm cols. Unchanged from v1.x. |
| `run_bank()` | Per-user `post_to_ledger` for interest + fees. No bulk-SQL fallback. |
| `run_lottery()` | Single `post_to_ledger('lottery_pool', 'user_wallets', $jackpot, ...)` for the payout. |

Inline triggers (no dedicated phpBB cron task class): `run_bank()` and `run_lottery()` fire from `event/listener.php` on board-index page loads when their cadence has elapsed, and from the relevant page handlers on user visits.

## bbAccounts integration

### Required setup (admin)

1. Install + enable `avathar/bbaccounts` first. `ext.php::is_enableable()` blocks bbPoints enable otherwise.
2. Seed the chart of accounts in bbAccounts with the 9 accounts listed in `contrib/specs/2026-05-23-rename-to-avathar-bbpoints-design.md`. Subledger configuration is load-bearing:
   - `2100 User Wallets` — **customer subledger** (UP posts wallet legs with subledger_user_id)
   - `2110 Bank Holdings` — **customer subledger** (bank deposit posts DR/CR with subledger_user_id on both legs)
   - `2200 Lottery Pool` — **non-subledger** (communal pool)
   - `4000 Revenue` + the five `rev_*` mapping accounts — non-subledger
   - `5010 Posting Rewards` + the other `exp_*` mapping accounts — non-subledger
3. In `ACP → Customise → Ultimate Points → bbAccounts Mapping`, map each of the 13 bbPoints roles to a bbAccounts account.

### Role list

The 13 roles (`migrations/bbpoints_install.php::BBACCOUNTS_ROLES`):
- Asset/liability subledger: `user_wallets`, `bank_holdings`, `lottery_pool`
- Expense (reward) roles: `exp_posting`, `exp_registration`, `exp_random`, `exp_bank_int`, `exp_admin_award`
- Revenue (cost/penalty/fee) roles: `rev_post_costs`, `rev_attach_costs`, `rev_penalty`, `rev_bank_fees`, `rev_admin_down`

### Known gaps (carried over from v1.3.4 → tracked in #2)

- Registration bonus path (`event/listener.php::user_add_modify_data`, around line 764) injects the starting balance directly into the phpBB user-insert SQL array, bypassing `add_points()`. The `exp_registration` role is mapped but never posts. Fix: replace the SQL injection with a `post_to_ledger` call after user creation.
- "Set" semantics (admin manual-set, admin group-transfer set) — no ledger entry posted. These become no-ops via `set_points` in v2.0. Fix: compute the delta vs current balance and call `post_to_ledger`.
- Negative diff drop on post edits (`event/listener.php`, around lines 911 and 944) — editing a post downward forfeits the difference rather than posting a reversal. Separate from the mutation-funnel refactor.

## Conventions

- Namespace: `avathar\bbpoints`
- Service-ID prefix: `avathar.bbpoints.*`
- Table parameter prefix: `%avathar.bbpoints.table.*%`
- Container parameters live in `config/tables.yml`; service definitions in `config/services.yml`
- ACP/UCP module classes: `acp_main_module` / `acp_main_info` / `ucp_main_module` / `ucp_main_info` (basename consolidated in v2.0)
- Language files: `acp_bbpoints.php` (ACP labels), `permissions_bbpoints.php`, `info_acp_main.php`, `info_ucp_main.php`, plus the v1.x-preserved `common.php`
- Templates live under `styles/all/template/points/` (single style; no per-style overrides)
- URL routes: `/bbpoints` (main controller), `/bbpointslist` (user-list controller)

## Migrations

- `migrations/bbpoints_install.php` — single squashed v2.0 install migration (replaces 16 v1.x files)
- `contrib/archive/migrations/` — historical reference, the v1.x chain preserved verbatim

The new install migration is `effectively_installed()`-gated on `$this->config['bbpoints_version'] === '2.0.0'` and uses idempotent ops throughout (`config.add`, `permission.add`, `module.add`). Defensive `config.remove` calls clean up the dropped `ultimatepoints_bbaccounts_backfilled` and `ultimatepoints_bbaccounts_canonical` keys on an in-place upgrade.

## Documentation status

- `README.md` — v2.0 rewrite with lineage section
- `CHANGELOG.md` — v2.0 entry on top, v1.x history preserved below
- `docs/ADMIN_MANUAL.md` + `docs/admin/01-*.md` … `07-*.md` — admin manual chapters written under v1.3.4 conventions; **need refresh for v2.0 namespace and the dropped backfill/canonical-flip UI** (tracked as Task R4 / "docs Task 18"). Chapter 08 + appendix + final link are still TODO.

## Testing

Upstream ships no automated tests. Manual UAT after a fresh install: enable the extension (verify bbAccounts precondition), map all 13 roles, make a test post, verify the journal entry + cache refresh.
