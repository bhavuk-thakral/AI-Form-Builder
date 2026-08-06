# Architectural Decisions Log (DECISIONS.md)

This log details the core assumptions, differentiator selections, and technical trade-offs accepted during the development of this AI-Powered Form Builder.

---

## 1. Core Assumptions
- **Single Host Deployment**: For out-of-the-box usability, the system assumes a unified server model. The queue worker handles jobs via the `database` driver, removing external Redis requirements for testing.
- **Database Driver**: For development and testing, SQLite/MySQL are used interchangeably. Scaled production instances should leverage a dedicated PostgreSQL or MySQL 8 instance with standard indexing.
- **Schema Resilience**: AI generation might occasionally yield malformed or partial JSON responses. The system assumes a defensive fallback parser that cleans, validates, and retries LLM API completions.
- **Client-Side Framework**: Vanilla ES6 JS is utilized rather than React or Vue to eliminate compiler overhead and keep dashboard performance responsive.

---

## 2. Part D Choices & Rationale

We selected and built the following three differentiators to elevate the application:

### A. Form Versioning & Rollback (Checkpoint Audits)
* **Problem**: In dynamic form builders, manual schema updates can accidentally delete fields, corrupting existing submission records.
* **Solution**: Every canvas save event creates a new version checkpoint in `form_versions`. Users can view version histories and rollback instantly, creating a new revision node.
* **Trade-Off**: Restoring a historical schema might orphan data answers submitted on columns that do not exist in the rolled-back version.

### B. Conditional Visibility Logic
* **Problem**: Standard forms display all fields, which degrades completion conversion rates.
* **Solution**: Fully integrated a rule engine inside the builder settings panel and a JavaScript executor on the public viewer that shows/hides elements in real time and toggles input `required` attributes dynamically.
* **Trade-Off**: Nested or complex rules (e.g. multiple "AND" conditions) are simplified to single field comparisons to keep the rules layout lightweight.

### C. Completion and Analytics Report Dashboard
* **Problem**: Owners lack visibility into form reach, submission drop-offs, or answers distributions.
* **Solution**: Integrated view logging, avg completion stopwatch times, conversion rate calculators, a Chart.js daily response volume graph, and choice distribution percentage charts.
* **Trade-Off**: Views are counted on page load without deduplicating IP page refreshes.

---

## 3. Technical Trade-Offs Accepted
- **Database Queue Engine**: Used `database` queue driver instead of Redis to keep the server setup simple and allow the application to run with zero dependencies out-of-the-box.
- **Synchronous Document Processing**: Imported docx/xlsx files are parsed synchronously on upload instead of queued, which is acceptable for documents under 5MB to show instantaneous preview maps.
- **Laravel Mix/Vite Asset Compiler**: Opted for CDN dependencies (Bootstrap, Bootstrap Icons, Chart.js) to avoid requiring NodeJS/npm compilers.

---

## 4. If We Had Two More Weeks...
1. **Multi-Tenant Isolation**: Implement Tenant scoped databases or multi-tenant database tables checking `tenant_id` scopes.
2. **Embeddable Widgets**: Render a tiny Javascript script that generates iframe forms for external websites.
3. **Webhooks System**: Add POST payloads sent to third-party endpoints (e.g. Zapier) on new submissions.
4. **Concurrent Edit Locking**: Integrate locks preventing two users from modifying the same form canvas simultaneously.
