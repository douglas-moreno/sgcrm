# Overview

This project consists of building a lightweight, sales-focused CRM designed for small to mid-sized businesses, emphasizing simplicity, speed of use, and direct integration with WhatsApp for communication.

The system enables a Business Owner to register an account and create a company entity with minimal friction (company name only). Upon registration, the initial user is automatically assigned as the Business Owner and gains full administrative control over the workspace, including inviting and managing Salespeople.

The CRM is centered around a Kanban-based pipeline where all lead and deal management occurs. Lead creation is intentionally embedded within the Kanban interface to minimize context switching and streamline the sales workflow. When adding a new lead, the system performs a real-time lookup to enforce uniqueness: if the lead already exists in the database, the user can select it and immediately create a new deal; otherwise, the lead is created inline and a deal is automatically associated.

Deals represent sales opportunities and are visualized as cards within a fixed-stage pipeline. A suggested default pipeline structure is:

1. **New Lead**
2. **Contacted**
4. **Proposal Sent**
5. **Negotiation**
6. **Won**
7. **Lost**

Each deal contains essential attributes such as title and monetary value, and supports lifecycle management through these stages. When a deal is marked as "Lost," capturing a loss reason is mandatory for analytical purposes.

Clicking on a deal opens a detailed view where users can:
- Add internal notes
- Update deal value and metadata
- Track activity history
- Initiate communication with the lead

Communication is a core feature of the platform. Each deal includes a “Chat” action that redirects the user to a dedicated conversation interface powered by WhatsApp integration via Evolution API v2. This enables real-time messaging directly within the CRM context.

Each Salesperson must individually connect their WhatsApp account via QR Code within the settings area. Once authenticated, they gain access to the chat interface for their assigned leads.

Access control follows a strict RBAC model:
- Business Owners have full visibility and control over all leads, deals, users, and reports.
- Salespeople can only access and manage leads and deals explicitly assigned to them.

Lead ownership is defined at creation time. When a lead is assigned to a Salesperson, all associated deals inherit the same ownership. The system enforces strong uniqueness constraints on leads at the database level to prevent duplication and maintain data integrity.

The user experience is designed with a mobile-first approach, ensuring that Salespeople can efficiently manage their pipeline, update deals, and communicate via WhatsApp directly from their mobile devices. The Kanban board, deal details, and chat interface are optimized for touch interaction and fast navigation.

A consistent design system must be followed as defined in the `@docs/design` directory to ensure UI/UX coherence across the platform.

---

# Tech Stack

- Laravel 13
- MySQL 8
- Livewire v4 (User Panel)
- Evolution API v2 (WhatsApp Integration)

---

## Users & Roles (RBAC)

**Business Owner:**  
Has full access to all company data and system features.  
Can manage leads, deals, pipeline stages (fixed), salespeople, assignment rules, and view company-wide performance dashboards.  
Can reassign leads between salespeople and oversee all operations.

**Salesperson:**  
Has restricted access limited to their assigned leads and deals.  
Can move deals across pipeline stages, update customer information, add notes, and interact via WhatsApp within their assigned scope.

---

## Core Workflows

- Leads must be unique, enforced via strong database constraints and validation.
- Company registration is minimal (name only), and the first user is automatically assigned as Business Owner.
- Business Owner can invite additional users (Salespeople).
- Leads are created exclusively through the Kanban interface.
- During lead creation, the system must check for existing records:
  - If found, reuse the lead and create a new deal.
  - If not found, create the lead and automatically create a deal.
- Deals must include:
  - Title
  - Monetary value
  - Status (pipeline stage)
- If a deal is marked as "Lost," a loss reason is required.
- Lead ownership defines deal ownership.
- Salespeople can only view and manage their own leads/deals.
- Business Owner can view and manage all leads/deals.
- Each Salesperson must connect their WhatsApp account via QR Code in settings.
- WhatsApp communication is accessible only after successful connection.

---

## Technical Requirements

- **Secure Authentication + Password Reset**
  - Production-grade authentication with rate limiting, session security, and password recovery flows.

- **Backend-Enforced RBAC**
  - All permissions must be enforced server-side (view/create/update/delete).
  - Strict data isolation for Salespeople.
  - Full access for Business Owners.

- **Mobile-Friendly Interface**
  - Fully responsive UI focused on Salesperson workflows (Kanban, deal details, WhatsApp chat).
  - Optimized for mobile usage with touch-friendly interactions and efficient navigation.

- **Kanban Implementation**
  - Must be built using native Livewire capabilities (v4).
  - No external drag-and-drop libraries; leverage Livewire reactivity for state management.

- **UI Library**
  -  For Ui components use Wireui library (already installed).
  -  For domumentation use Context7.