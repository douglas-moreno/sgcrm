# User Stories — sgCrm

## Overview

This document contains user stories for **sgCrm**, a lightweight, sales-focused CRM for small to mid-sized businesses. The platform centers on a Kanban pipeline for lead and deal management, with deep WhatsApp integration via Evolution API v2 for direct customer communication.

**User Types:**
- **Visitor** — Unauthenticated user accessing public pages (landing, login, registration)
- **Business Owner** — First user of a company; full administrative control over the workspace
- **Salesperson** — Invited user with access restricted to leads and deals assigned to them

**Tech Context:** Laravel 13 · MySQL 8 · Livewire v4 · Wireui · Evolution API v2 · Mobile-first responsive UI.

---

## 1. Authentication & Account Setup

### US-1.1: Business Owner Registration (Company Creation)
**As a** Visitor
**I want to** register a new account and create my company
**So that** I can start managing leads and deals immediately

**Acceptance Criteria:**
- [ ] Registration form collects: name, email, password, password confirmation, company name
- [ ] Email must be globally unique
- [ ] Password must meet minimum security requirements (8+ characters)
- [ ] On submit, system creates: User + Company + assigns user as **Business Owner**
- [ ] Only the company name is required to create the company (no extra friction)
- [ ] User redirected to Kanban dashboard after registration
- [ ] Welcome email sent

**Expected Result:** New company workspace created with the registering user as Business Owner.

---

### US-1.2: User Login
**As a** Registered User
**I want to** log in with my email and password
**So that** I can access my workspace

**Acceptance Criteria:**
- [ ] Login form collects: email, password
- [ ] Authentication rate-limited to prevent brute force
- [ ] Session secured with CSRF and HTTP-only cookies
- [ ] On success, user redirected to role-specific landing page (Kanban for both roles)
- [ ] On failure, generic error message shown (no user enumeration)
- [ ] "Remember me" option supported

**Expected Result:** Authenticated session established.

---

### US-1.3: Password Reset
**As a** Registered User
**I want to** reset my password via email
**So that** I can regain access if I forget my credentials

**Acceptance Criteria:**
- [ ] "Forgot password" link visible on login screen
- [ ] User enters email; reset link sent if account exists (no enumeration)
- [ ] Reset link valid for limited time (60 minutes)
- [ ] User defines new password meeting security policy
- [ ] All active sessions invalidated after reset
- [ ] Confirmation email sent after successful reset

**Expected Result:** User regains account access securely.

---

### US-1.4: Logout
**As an** Authenticated User
**I want to** log out of the application
**So that** my session is terminated on shared devices

**Acceptance Criteria:**
- [ ] Logout action available from main navigation
- [ ] Session destroyed server-side
- [ ] User redirected to login page
- [ ] CSRF token rotated

**Expected Result:** Active session terminated.

---

## 2. Company & User Management

### US-2.1: Invite Salesperson via Email Link
**As a** Business Owner
**I want to** invite a Salesperson by email
**So that** they can register themselves and start working in my company

**Acceptance Criteria:**
- [ ] Owner enters: name, email
- [ ] System generates signed, expiring invite link (valid 7 days)
- [ ] Invite email sent to recipient with link
- [ ] Invitee opens link and sets their own password on first access
- [ ] User created with role = Salesperson, scoped to inviting company
- [ ] Invite status visible to Owner (pending / accepted / expired)
- [ ] Owner can resend or revoke pending invites

**Expected Result:** Salesperson onboarded via self-service flow.

---

### US-2.2: Create Salesperson Account Directly
**As a** Business Owner
**I want to** create a Salesperson account directly with a temporary password
**So that** I can onboard users immediately without waiting for them to act on an email

**Acceptance Criteria:**
- [ ] Owner enters: name, email, temporary password
- [ ] User created with role = Salesperson, scoped to the company
- [ ] User receives notification email with login instructions
- [ ] User forced to change password on first login
- [ ] Email must be unique system-wide

**Expected Result:** Salesperson account ready for immediate use.

---

### US-2.3: List & Manage Salespeople
**As a** Business Owner
**I want to** view and manage all Salespeople in my company
**So that** I can control workspace membership

**Acceptance Criteria:**
- [ ] List shows: name, email, status (active/inactive), date added
- [ ] Owner can deactivate a Salesperson (blocks login, preserves historical data)
- [ ] Owner can reactivate a previously deactivated Salesperson
- [ ] Search by name/email
- [ ] Cannot deactivate own account

**Expected Result:** Owner has full control over workspace user lifecycle.

---

### US-2.4: Reassign Lead Ownership
**As a** Business Owner
**I want to** reassign a lead from one Salesperson to another
**So that** I can rebalance workload or handle absences

**Acceptance Criteria:**
- [ ] Reassign action available from lead/deal detail view
- [ ] Owner selects target Salesperson from list
- [ ] All deals associated with that lead inherit the new owner
- [ ] Activity log records the reassignment (from, to, by, when)
- [ ] Previous owner loses access immediately

**Expected Result:** Lead and its deals now owned by the new Salesperson.

---

## 3. Kanban Pipeline

### US-3.1: View Kanban Board
**As a** Salesperson
**I want to** see my deals organized as cards in a Kanban board by pipeline stage
**So that** I can visualize my sales pipeline at a glance

**Acceptance Criteria:**
- [ ] Board shows fixed columns: New Lead → Contacted → Proposal Sent → Negotiation → Won → Lost
- [ ] Each card displays: deal title, monetary value, lead name, last activity
- [ ] Salesperson sees only deals assigned to them
- [ ] Business Owner sees all deals across all Salespeople
- [ ] Column header shows count and total value per stage
- [ ] Board fully responsive; usable on mobile via touch
- [ ] Built with native Livewire v4 reactivity (no external drag-drop libraries)

**Expected Result:** User sees real-time pipeline state scoped by role.

---

### US-3.2: Move Deal Across Stages
**As a** Salesperson
**I want to** move a deal card to a different pipeline stage
**So that** I can update the deal's progress

**Acceptance Criteria:**
- [ ] Card movable via drag-and-drop on desktop and touch on mobile
- [ ] Stage change persisted instantly via Livewire
- [ ] Activity history records: from stage, to stage, user, timestamp
- [ ] If destination is "Lost", a modal forces capture of loss reason before commit (see US-5.4)
- [ ] If destination is "Won", deal locked from further edits except notes
- [ ] Authorization checked server-side: only the deal owner (or Business Owner) may move it

**Expected Result:** Deal reflects current stage; history preserved.

---

### US-3.3: Filter Kanban (Business Owner)
**As a** Business Owner
**I want to** filter the Kanban board by Salesperson
**So that** I can focus on a specific person's pipeline

**Acceptance Criteria:**
- [ ] Filter dropdown lists all active Salespeople + "All"
- [ ] Selection re-renders board scoped to that Salesperson
- [ ] Filter state persists in URL query string
- [ ] Counts and totals recalculated per filter

**Expected Result:** Owner can drill into individual pipelines.

---

## 4. Lead Management

### US-4.1: Create Lead Inline from Kanban
**As a** Salesperson
**I want to** create a new lead directly from the Kanban board
**So that** I avoid context switching during fast-paced data entry

**Acceptance Criteria:**
- [ ] "New Lead" action available on the Kanban board
- [ ] Form collects: lead name, email, phone, optional notes
- [ ] As email is typed, real-time lookup checks for existing lead in the company
- [ ] If existing lead found by email, user prompted to reuse it (see US-4.2)
- [ ] If not found, lead is created and a new deal is automatically associated (see US-5.1)
- [ ] Email uniqueness enforced at database level (unique constraint per company)
- [ ] Lead ownership set to the creating Salesperson (or selected Salesperson if Owner is creating)

**Expected Result:** Lead and initial deal created in a single workflow.

---

### US-4.2: Reuse Existing Lead on Duplicate Email
**As a** User creating a lead
**I want to** be notified when a matching lead already exists
**So that** I can attach a new deal to it instead of creating a duplicate

**Acceptance Criteria:**
- [ ] Real-time lookup triggers as the email field loses focus or matches a complete email
- [ ] Match shown with lead name, current owner, and existing deals count
- [ ] User can choose: "Use this lead" (skip to deal creation) or "Cancel"
- [ ] If the existing lead is owned by another Salesperson, a Salesperson user cannot reuse it (only the Owner can; reassignment may be required)
- [ ] System never creates a duplicate lead silently

**Expected Result:** Lead duplication prevented; new deal attached to canonical record.

---

### US-4.3: Edit Lead Information
**As a** Salesperson
**I want to** update a lead's contact details
**So that** the record stays accurate

**Acceptance Criteria:**
- [ ] Editable fields: name, phone, notes (email editable only by Business Owner)
- [ ] Email change re-runs uniqueness validation
- [ ] Changes saved instantly with optimistic UI feedback
- [ ] Activity history records field-level changes (old value, new value, user, timestamp)
- [ ] Salesperson can only edit leads assigned to them

**Expected Result:** Lead metadata updated and audited.

---

## 5. Deal Management

### US-5.1: Auto-Create Deal on Lead Creation
**As a** User creating a new lead
**I want to** have a deal automatically created and placed in the first pipeline stage
**So that** I do not need a separate step to start tracking the opportunity

**Acceptance Criteria:**
- [ ] On lead creation, a deal is created with: title (defaults to lead name), value = 0, stage = "New Lead"
- [ ] User can edit title and value immediately after creation
- [ ] Deal owner = lead owner

**Expected Result:** Every new lead has at least one active deal.

---

### US-5.2: Add Additional Deal to Existing Lead
**As a** Salesperson
**I want to** create another deal for an existing lead
**So that** I can track multiple opportunities with the same customer

**Acceptance Criteria:**
- [ ] From lead detail or Kanban (via "reuse existing lead" flow), user can create a new deal
- [ ] Form collects: title (required), monetary value (required, ≥ 0)
- [ ] New deal placed in "New Lead" stage by default
- [ ] Deal owner inherited from lead owner

**Expected Result:** Multiple deals tracked per lead.

---

### US-5.3: View & Edit Deal Details
**As a** Salesperson
**I want to** open a deal to view and edit all its information
**So that** I can manage the opportunity in depth

**Acceptance Criteria:**
- [ ] Click on Kanban card opens deal detail view
- [ ] Detail view shows: title, monetary value, current stage, lead info, owner, notes, activity history, chat shortcut
- [ ] Editable fields: title, monetary value, internal notes
- [ ] Changes persisted via Livewire with inline feedback
- [ ] Authorization enforced server-side per role
- [ ] Mobile-optimized layout

**Expected Result:** Deal fully manageable from a single screen.

---

### US-5.4: Mark Deal as Lost with Reason
**As a** Salesperson
**I want to** be required to record why a deal was lost
**So that** the company can analyze loss patterns

**Acceptance Criteria:**
- [ ] Moving a deal to "Lost" stage opens a modal
- [ ] Modal contains a required free-text "Loss reason" field
- [ ] Empty submission blocked (client + server validation)
- [ ] Reason saved on the deal and surfaced in the loss-reason report
- [ ] Activity history records the loss event with reason
- [ ] Modal cancellable; cancel reverts the stage move

**Expected Result:** No deal enters "Lost" without a documented reason.

---

### US-5.5: Add Internal Notes to a Deal
**As a** Salesperson
**I want to** add timestamped internal notes to a deal
**So that** I can record context that customers should not see

**Acceptance Criteria:**
- [ ] Notes section shown on deal detail
- [ ] Each note: free text, author, timestamp
- [ ] Notes are append-only from the UI (edits/deletes restricted to author or Owner within a short window)
- [ ] Notes never sent via WhatsApp or any external channel
- [ ] Notes counted in activity-volume metrics

**Expected Result:** Deal has private context trail visible only to authorized users.

---

### US-5.6: View Deal Activity History
**As a** User
**I want to** see a chronological log of all events on a deal
**So that** I understand its full history

**Acceptance Criteria:**
- [ ] Timeline shows: stage moves, value changes, ownership changes, notes added, messages sent/received, deal creation
- [ ] Each entry includes: actor, action, before/after where applicable, timestamp
- [ ] Read-only; cannot be edited or deleted
- [ ] Sorted newest-first with pagination

**Expected Result:** Full audit trail available for any deal.

---

## 6. WhatsApp Integration (Evolution API v2)

### US-6.1: Connect WhatsApp Account via QR Code
**As a** Salesperson
**I want to** connect my own WhatsApp account by scanning a QR Code
**So that** I can chat with leads from within the CRM

**Acceptance Criteria:**
- [ ] Settings page contains "WhatsApp Connection" section
- [ ] Clicking "Connect" calls Evolution API v2 to provision an instance for the Salesperson
- [ ] QR Code rendered in the UI; refreshes on expiry
- [ ] Connection status updates in real-time (disconnected → pending → connected)
- [ ] One WhatsApp instance per Salesperson; cannot be shared
- [ ] On successful connection: status = connected, phone number stored
- [ ] User can disconnect / reconnect

**Expected Result:** Salesperson's personal WhatsApp linked to the CRM.

---

### US-6.2: Open Chat from a Deal
**As a** Salesperson
**I want to** click "Chat" on a deal to open a conversation with the lead
**So that** I can communicate without copying phone numbers

**Acceptance Criteria:**
- [ ] "Chat" action visible on deal detail
- [ ] Action disabled with explanation if WhatsApp not connected
- [ ] Action disabled if lead has no phone number
- [ ] Clicking opens dedicated conversation view bound to that lead's phone
- [ ] Conversation context (deal id, lead id) preserved for activity logging

**Expected Result:** User reaches the chat interface for the correct lead.

---

### US-6.3: Send & Receive WhatsApp Messages
**As a** Salesperson
**I want to** send and receive WhatsApp messages inside the CRM
**So that** all communication stays in one place

**Acceptance Criteria:**
- [ ] Conversation view shows full message history with the lead, sorted oldest-to-newest
- [ ] Outgoing messages dispatched via Evolution API v2
- [ ] Incoming messages received via Evolution webhook and persisted
- [ ] Messages display: direction, content, timestamp, delivery status
- [ ] New incoming messages appear in near real-time (Livewire polling or websocket)
- [ ] Unread message indicator shown on related deal card
- [ ] All messages logged in deal activity history
- [ ] Salesperson sees only conversations for leads assigned to them

**Expected Result:** In-app, near real-time WhatsApp messaging, scoped by ownership.

---

### US-6.4: Handle Disconnected WhatsApp Instance
**As a** Salesperson
**I want to** be notified if my WhatsApp instance disconnects
**So that** I can reconnect quickly and avoid missing messages

**Acceptance Criteria:**
- [ ] Disconnect events from Evolution API update the user's connection status
- [ ] In-app banner shown on chat / settings when disconnected
- [ ] Send action disabled while disconnected
- [ ] Reconnect flow returns user to QR scan (US-6.1)

**Expected Result:** User aware of connection state at all times.

---

## 7. Reports & Dashboards (Business Owner)

### US-7.1: Pipeline Overview Report
**As a** Business Owner
**I want to** see deal counts and total monetary value per pipeline stage
**So that** I can assess pipeline health at a glance

**Acceptance Criteria:**
- [ ] Report shows each stage with: deal count, sum of monetary value
- [ ] Aggregate totals for the whole pipeline
- [ ] Filter by date range (created at, last activity)
- [ ] Filter by Salesperson
- [ ] Visualization: bar or column chart per stage

**Expected Result:** Owner gauges pipeline volume and value distribution.

---

### US-7.2: Salesperson Performance Report
**As a** Business Owner
**I want to** compare Salesperson performance
**So that** I can identify top performers and coaching needs

**Acceptance Criteria:**
- [ ] Per Salesperson: deals won, deals lost, conversion rate (won / (won + lost)), total won value, average deal size
- [ ] Date range filter
- [ ] Sortable by any metric
- [ ] Tabular view + chart visualization

**Expected Result:** Owner has quantitative view of team performance.

---

### US-7.3: Loss Reason Analysis
**As a** Business Owner
**I want to** see why deals are being lost
**So that** I can address recurring objections

**Acceptance Criteria:**
- [ ] Report aggregates free-text loss reasons from lost deals
- [ ] Displays a frequency-ranked list (count per distinct reason text, normalized for case/whitespace)
- [ ] Date range filter
- [ ] Filter by Salesperson
- [ ] Click on a reason expands to the underlying deal list

**Expected Result:** Owner identifies common loss patterns from raw reasons.

---

### US-7.4: Activity Volume Report
**As a** Business Owner
**I want to** see engagement metrics per Salesperson and per deal
**So that** I can monitor work volume and responsiveness

**Acceptance Criteria:**
- [ ] Metrics tracked: messages sent, messages received, notes added, stage moves
- [ ] Aggregated per Salesperson and overall
- [ ] Date range filter
- [ ] Drill-down: per-Salesperson view shows their top deals by activity
- [ ] Time-series chart for activity over time

**Expected Result:** Owner tracks operational tempo across the team.

---

## 8. Access Control (RBAC) & Data Isolation

### US-8.1: Salesperson Data Isolation
**As a** Salesperson
**I want to** access only the leads and deals assigned to me
**So that** my view is focused and confidential data stays scoped

**Acceptance Criteria:**
- [ ] All queries (Kanban, lists, reports, chat) automatically filter by `owner_id = current_user`
- [ ] Direct URL access to a non-owned deal returns 403
- [ ] Deal/lead reuse flow blocks reuse of leads owned by another Salesperson
- [ ] Server-side authorization (Laravel Policies) enforces every action: view, create, update, delete, move stage, chat
- [ ] No client-side-only access decisions

**Expected Result:** Salespeople cannot see or affect data outside their scope.

---

### US-8.2: Business Owner Full Access
**As a** Business Owner
**I want to** access and manage every lead, deal, user, and conversation in my company
**So that** I can supervise operations end-to-end

**Acceptance Criteria:**
- [ ] Owner bypasses ownership filter (scoped to their company only — never cross-company)
- [ ] Owner can edit any deal, reassign any lead, deactivate any user
- [ ] Owner sees aggregated reports across all Salespeople
- [ ] All Owner actions logged in activity history with actor identification

**Expected Result:** Owner has full control bounded by their company boundary.

---

### US-8.3: Multi-Tenant Company Isolation
**As the** Platform
**I want to** strictly isolate data between companies
**So that** no user ever sees data from another company

**Acceptance Criteria:**
- [ ] Every domain table includes `company_id` and is filtered by it
- [ ] Database-level constraints prevent cross-company foreign keys
- [ ] Email uniqueness for leads scoped per company (same email allowed across different companies)
- [ ] Global scope on Eloquent models for `company_id`
- [ ] Tested with feature tests asserting cross-company access returns 403/404

**Expected Result:** Hard tenant boundary at the data layer.

---

## 9. Mobile Experience

### US-9.1: Mobile-First Kanban Usage
**As a** Salesperson on mobile
**I want to** view and update my pipeline efficiently from my phone
**So that** I can work while away from my desk

**Acceptance Criteria:**
- [ ] Kanban columns horizontally swipeable on mobile
- [ ] Card actions (move, open, chat) reachable via touch (≥44px hit targets)
- [ ] Forms (lead creation, deal edit, loss reason) optimized for mobile keyboards
- [ ] Chat interface usable one-handed
- [ ] No horizontal page scrolling outside the Kanban itself
- [ ] Tested across viewports: 360px, 414px, 768px, 1024px+

**Expected Result:** Full Salesperson workflow viable on mobile.

---

## 10. Notifications (Baseline)

### US-10.1: Transactional Emails
**As a** User
**I want to** receive emails for key account events
**So that** I stay informed about access and security

**Acceptance Criteria:**
- [ ] Welcome email on Business Owner registration
- [ ] Invite email with signed link (US-2.1)
- [ ] Account creation notification with login info (US-2.2)
- [ ] Password reset email (US-1.3)
- [ ] Password reset confirmation email
- [ ] All emails branded per design system in `docs/design`

**Expected Result:** Users receive timely, consistent transactional emails.

---

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Business Owner Registration | High | Pending |
| US-1.2 | User Login | High | Pending |
| US-1.3 | Password Reset | High | Pending |
| US-1.4 | Logout | High | Pending |
| US-2.1 | Invite Salesperson via Email Link | High | Pending |
| US-2.2 | Create Salesperson Account Directly | High | Pending |
| US-2.3 | List & Manage Salespeople | Medium | Pending |
| US-2.4 | Reassign Lead Ownership | Medium | Pending |
| US-3.1 | View Kanban Board | High | Pending |
| US-3.2 | Move Deal Across Stages | High | Pending |
| US-3.3 | Filter Kanban (Business Owner) | Medium | Pending |
| US-4.1 | Create Lead Inline from Kanban | High | Pending |
| US-4.2 | Reuse Existing Lead on Duplicate Email | High | Pending |
| US-4.3 | Edit Lead Information | Medium | Pending |
| US-5.1 | Auto-Create Deal on Lead Creation | High | Pending |
| US-5.2 | Add Additional Deal to Existing Lead | Medium | Pending |
| US-5.3 | View & Edit Deal Details | High | Pending |
| US-5.4 | Mark Deal as Lost with Reason | High | Pending |
| US-5.5 | Add Internal Notes to a Deal | Medium | Pending |
| US-5.6 | View Deal Activity History | Medium | Pending |
| US-6.1 | Connect WhatsApp via QR Code | High | Pending |
| US-6.2 | Open Chat from a Deal | High | Pending |
| US-6.3 | Send & Receive WhatsApp Messages | High | Pending |
| US-6.4 | Handle Disconnected WhatsApp Instance | Medium | Pending |
| US-7.1 | Pipeline Overview Report | High | Pending |
| US-7.2 | Salesperson Performance Report | High | Pending |
| US-7.3 | Loss Reason Analysis | Medium | Pending |
| US-7.4 | Activity Volume Report | Medium | Pending |
| US-8.1 | Salesperson Data Isolation | High | Pending |
| US-8.2 | Business Owner Full Access | High | Pending |
| US-8.3 | Multi-Tenant Company Isolation | High | Pending |
| US-9.1 | Mobile-First Kanban Usage | High | Pending |
| US-10.1 | Transactional Emails | Medium | Pending |
