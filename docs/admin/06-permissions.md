# Chapter 6 — User Permissions

> ACP path: `ACP → Permissions → Groups` (or `Users`) → Permissions tab → Ultimate Points category

## Overview

UltimatePoints defines twelve permissions across four tiers: global user (`u_`),
forum (`f_`), moderator (`m_`), and admin (`a_`). They are configured through
phpBB's standard permission editor — there is no dedicated UltimatePoints page
under `ACP → Customise` for this purpose. Permissions appear in a category
labelled **Ultimate Points** in the editor's permission tree.

Six of the global user permissions are seeded as `ALLOW` for the REGISTERED
group during installation. The three forum permissions default to `NEVER` and
must be explicitly granted per group and per forum if pay-to-use functionality is
required. The moderator and admin permissions default to `ALLOW` but are not
seeded to any group at install time, so they take effect only once a role or
explicit grant is applied.

## Permission catalogue

| Permission key | ACL label | What it gates | Default | Seeded for |
|---|---|---|---|---|
| `u_use_points` | Can use Ultimate Points | Access to the main points page (`controller/main.php:206`); controls `USER_LOCK` flag in post/profile templates (`event/listener.php:393, 494`). Does **not** gate award computation in the post listener — point earnings accumulate regardless of this permission. | Yes | REGISTERED |
| `u_use_bank` | Can use Bank Module | Entry to the bank deposit/withdrawal page (`core/points_bank.php:154`); also sets `USER_BANK_LOCK` in templates (`event/listener.php:394, 499`). | Yes | REGISTERED |
| `u_use_logs` | Can use Log Module | Access to the transfer/event log page (`core/points_logs.php:132`). | Yes | REGISTERED |
| `u_use_robbery` | Can use Robbery Module | Access to the robbery attempt page for both the initiator and target sides (`core/points_robbery.php:136`, `core/points_robbery_user.php:136`). | Yes | REGISTERED |
| `u_use_lottery` | Can use Lottery Module | Access to the lottery page and ticket purchase (`core/points_lottery.php:152`). | Yes | REGISTERED |
| `u_use_transfer` | Can use Transfer Module | Access to both the global transfer page and the per-user transfer page (`core/points_transfer.php:155`, `core/points_transfer_user.php:148`). | Yes | REGISTERED |
| `f_pay_attachment` | Has to pay for downloading attachments | Triggers the attachment-cost deduction when the forum's attachment cost field is non-zero (`event/listener.php:445, 629`). Must be granted **and** the per-forum attachment cost must be > 0 for the charge to apply. | No | — |
| `f_pay_topic` | Has to pay for making a new topic | Deducts the forum's topic cost on new-topic submission (`event/listener.php:834`) and blocks submission when the user cannot afford it (`event/listener.php:990`). Must be granted **and** `forum_cost_topic` > 0. | No | — |
| `f_pay_post` | Has to pay for making a new post | Deducts the forum's post cost on reply/quote submission (`event/listener.php:840`) and blocks submission when the user cannot afford it (`event/listener.php:996`). Must be granted **and** `forum_cost_post` > 0. | No | — |
| `m_chg_points` | Can change users points | Shows the wallet-edit form on a user's points page and saves the new balance (`core/points_points_edit.php:126`); also renders the moderator "Modify" link in post and profile views (`event/listener.php:398–399, 541–542`). | Yes | — |
| `m_chg_bank` | Can change users Bank points | Shows the bank-edit form and saves the new bank balance (`core/points_bank_edit.php:143`); renders the moderator "Modify" bank link in post/profile views (`event/listener.php:400, 543–544`). | Yes | — |
| `a_points` | Can administrate Ultimate Points | Guards destructive bulk actions inside the admin controller (reset logs, reset all balances) (`controller/admin_controller.php:231, 271, 650`). The ACP menu itself is gated by `acl_a_board`, not this permission; `a_points` is a second check on specific high-impact actions only. | Yes | — |

## Removing a permission — what happens

### Removing `u_use_points`

Removing this permission from a group hides the points UI for members of that
group (main page returns `NOT_AUTHORISED`, post/profile templates suppress the
balance display and the donate link). Existing wallet balances are **not**
cleared — the value in `phpbb_users.user_points` is unaffected.

Importantly, removing `u_use_points` does **not** stop the post listener from
awarding points. The listener's posting handler (`event/listener.php`) does not
check `u_use_points` before running its reward calculation, so points continue
to accumulate in the database even for users who cannot see their balance.

### Removing `u_use_bank`

Removing this permission blocks the user from visiting the deposit/withdrawal
page. Existing bank holdings stored in `phpbb_points_bank.holding` are not
touched — the record survives and the balance is intact. Access is restored
immediately if the permission is re-granted.

### Removing `u_use_transfer`

Removing this permission blocks the user from initiating outgoing transfers.
Incoming transfers sent by other users continue to land in the recipient's
wallet regardless, because the sender's transfer action is checked against the
**sender's** `u_use_transfer`, not the recipient's.

### Removing `f_pay_*` permissions

Removing a `f_pay_*` permission from a group exempts that group from the charge
entirely — members can post, reply, or download without spending points. The
forum-level cost fields in `phpbb_forums` are unaffected; the charge simply
stops being evaluated for the exempted group.

### Removing `m_chg_points` or `m_chg_bank`

Removes the "Modify" link from post and profile views for that moderator. The
edit form itself will also return `NOT_AUTHORISED` on direct access.

## Things to know

- Permissions are configured via the standard phpBB permission editor. There is
  no dedicated UltimatePoints permission ACP page.
- The `f_pay_*` permissions are forum permissions (prefix `f_`). For a
  pay-to-post charge to apply to a given forum, **both** conditions must be true:
  the `f_pay_*` permission must be granted to the relevant group/user **and** the
  corresponding per-forum cost field must be non-zero (see Chapter 2 for forum
  cost configuration).
- The ACP modules (`ACP → Customise → Ultimate Points → *`) are gated by
  `acl_a_board` (set in `acp/acp_ultimatepoints_info.php`), not by `a_points`.
  The `a_points` permission is an additional in-code check on the three most
  destructive bulk-admin actions only.
- The `m_chg_*` permissions allow moderators to edit another user's point or
  bank balance directly from that user's points page. This is distinct from the
  full ACP group-transfer functionality (which requires `a_points` / `a_board`).
- At install time, no explicit `permission.permission_set` call is made for
  `f_pay_attachment`, `f_pay_topic`, `f_pay_post`, `m_chg_points`, `m_chg_bank`,
  or `a_points`. Those permissions must be granted manually to the desired groups
  or roles after installation.
