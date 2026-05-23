bbPoints for phpBB 3.3 (formerly Ultimate Points)
==================================================

[![Tests](https://github.com/avatharbe/bbPoints/actions/workflows/tests.yml/badge.svg)](https://github.com/avatharbe/bbPoints/actions/workflows/tests.yml)

A point-economy extension for phpBB. Users earn points for posting and registering, hold them in a wallet or interest-bearing bank, and spend them via lottery, robbery, peer-to-peer transfer, attachment downloads, or pay-to-post forums. Storage is backed by the [bbAccounts](https://github.com/avatharbe/bbAccounts) double-entry ledger — every point movement is an atomic, audited journal entry.

**Version:** 2.0.0 (23/05/2026)

#### Requirements
- phpBB 3.3.0 or higher (tested against 3.3.16)
- PHP 7.4 or higher
- [bbAccounts](https://github.com/avatharbe/bbAccounts) (`avathar/bbaccounts`) installed and enabled — **required**

#### Lineage

bbPoints v2.0 is the successor to `dmzx/ultimatepoints`. The v1.x line is preserved at the [`v1.3.4-final` tag](https://github.com/avatharbe/bbPoints/releases/tag/v1.3.4-final). v2.0 changes the PHP namespace, vendor directory, and service IDs to `avathar/bbpoints`, makes bbAccounts a required dependency, and removes the v1.3.x dual-write infrastructure — bbAccounts is the canonical store from day one. DB-stored identifiers (table names, config keys, ACL labels) are preserved verbatim. An existing v1.3.x install can upgrade in place by disabling v1.3.4, deleting the old `ext/dmzx/ultimatepoints/` directory, and installing v2.0 under the new vendor directory.

Originally by dmzx & posey. Fork maintained by Andy Vandenberghe (Sajaki). See `CHANGELOG.md` for the full v2.0 transition.

#### Features
- Per-user **wallet** balance, backed by a customer-subledger account in bbAccounts
- **Bank** account with configurable interest accrual and maintenance fees (also a customer-subledger account)
- **Lottery** with progressive jackpot (communal pool account)
- **Robbery** between users (success/failure with configurable penalty)
- **User-to-user transfer** of points
- **Pay-to-post** forums (configurable per-forum cost for topics, replies, attachment downloads)
- **Earning rules** per forum (points per post, per topic, per word; random bonuses)
- **Notifications** when points change hands
- **Registration bonus** for new users
- Admin tools: per-user adjust, group-wide add/subtract, "Resync caches from ledger" diagnostic

#### ACP Options
- **Points** — global enable/disable, registration bonus, points-per-word, random-bonus settings
- **Forum Points** — per-forum earning rules and pay-to-post costs
- **Bank** — interest rate, fee schedule, fee threshold
- **Lottery** — ticket cost, jackpot rules, draw cadence
- **Robbery** — success rate, max amount per attempt, penalty for failure
- **bbAccounts Mapping** — map bbPoints' 13 internal roles (User Wallets, Bank Holdings, Lottery Pool, Posting Rewards, …) to admin-created accounts in the bbAccounts chart of accounts. Required configuration: bbPoints will refuse to post entries for unmapped roles.

#### bbAccounts integration
bbAccounts (`avathar/bbaccounts`) is a **required dependency** in v2.0. Every point movement posts a balanced journal entry to the ledger; bbAccounts' trial balance and account-ledger reports are the full audit trail. The legacy `phpbb_users.user_points` and `phpbb_points_bank.holding` columns survive as denormalised read caches for template joins — refreshed on every ledger post. Run the ACP "Resync caches from ledger" action to recompute caches from the ledger at any time.

#### Languages
English

#### Installation
1. Install and enable [bbAccounts](https://github.com/avatharbe/bbAccounts) first (`avathar/bbaccounts`).
2. [Download the latest release](https://github.com/avatharbe/bbPoints/releases) and unzip it.
3. Copy the contents to `/ext/avathar/bbpoints/` (so that `ext.php` is at `/ext/avathar/bbpoints/ext.php`).
4. Navigate in the ACP to `Customise → Manage extensions`.
5. Find `bbPoints` under "Disabled Extensions" and click `Enable`. (Enable will refuse if bbAccounts is missing or disabled.)
6. After enable, open `ACP → Customise → bbPoints → bbAccounts Mapping` and map the 13 roles to bbAccounts accounts.

#### Uninstallation
1. Navigate in the ACP to `Customise → Manage extensions`.
2. Click the `Disable` link for `bbPoints`.
3. To permanently uninstall, click `Delete Data`, then delete the `bbpoints` folder from `/ext/avathar/`. bbAccounts journal entries posted by bbPoints remain in the ledger — they are managed via the bbAccounts extension.

#### Support
- [Issue tracker](https://github.com/avatharbe/bbPoints/issues)
- [v2.0 tracking issue](https://github.com/avatharbe/bbPoints/issues/2)

#### License
[![License](https://img.shields.io/github/license/avatharbe/bbPoints)](https://github.com/avatharbe/bbPoints/blob/main/license.txt)
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)
