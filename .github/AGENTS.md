# 🤖 SIGO Specialized Agents

## Overview

SIGO leverages **specialized AI agents** to handle different aspects of development. Each agent is optimized for a specific domain and brings focused expertise to your tasks.

This document helps you **choose the right agent** for your current work.

---

## Quick Reference

| Agent | Use When | Key Skills |
|-------|----------|-----------|
| **database-architect** | Database design, migrations, query optimization | SQL Server, Eloquent, audit schemas, presupuestación |
| **backend-developer** | Feature implementation, API design, business logic | Laravel 11, PHP, services, transactions, Google Drive |
| **legal-compliance-expert** | Regulatory requirements, LGPDP, audit trail design | LFTAIPG, LFPRH, LGPDP, audit compliance |
| **frontend-ux-expert** | UI design, Blade components, styling, accessibility | Tailwind, Alpine.js, Blade, WCAG compliance |
| **devops-engineer** | Infrastructure, deployment, monitoring, security | Azure, CI/CD, Docker, backups, monitoring |
| **qa-tester** | Test planning, bug verification, quality assurance | PHPUnit, test strategy, regression testing |

---

## Detailed Agent Selection

### 1. Database Architect

**When to invoke:**
- 📊 Designing new database tables or modifying schemas
- ✏️ Creating or reviewing Laravel Eloquent migrations
- 🔍 Optimizing slow queries or adding missing indexes
- 🔗 Setting up complex relationships between entities
- 📋 Designing audit trail tables (auditorias_*, consentimientos_*)
- 💰 Building presupuestación models or budget tracking
- 🔐 Implementing LGPDP data retention policies

**Example queries:**
> "Add a table to track material inventory with audit trail for SIGO"
> "Optimize the solicitudes list query - it's too slow with 50K+ records"
> "Design the database schema for the new cold load (carga fría) feature"
> "Create migration to add soft delete to usuarios table"

**What you'll get:**
- Migration file (ready to run)
- Eloquent Model code with relationships
- Database design diagram (conceptual)
- Index recommendations
- Retention policy documentation

---

### 2. Backend Developer

**When to invoke:**
- 🚀 Implementing new features or business logic
- 🔨 Creating controllers, services, or models
- 🛠️ Fixing bugs in application code
- 🔄 Building complex workflows (solicitud lifecycle, verification process)
- 📧 Creating email notifications and queue jobs
- 🔗 Integrating with external APIs (Google Drive, payment systems)
- 💾 Handling transactions and error scenarios

**Example queries:**
> "Build the solicitud creation workflow with transaction handling"
> "Create a service to validate presupuestación availability"
> "Fix the bug where budget isn't being deducted from category"
> "Implement Google Drive file picker integration"
> "Create email notification for admin when documents are rejected"

**What you'll get:**
- Controller/Service class code
- Eloquent Model relationships
- Business logic implementation
- Error handling examples
- Transaction management patterns

---

### 3. Legal Compliance Expert

**When to invoke:**
- ⚖️ Understanding LGPDP, LFTAIPG, or LFPRH requirements
- 📋 Mapping system features to Mexican regulations
- 🔐 Designing audit trail requirements for ASF (Auditoría Superior de Federación)
- 📑 Creating data classification (public/confidential/sensitive)
- 🗑️ Planning data retention and destruction procedures
- ✍️ Implementing ARCO workflows (Acceso, Rectificación, Cancelación, Oposición)
- 📋 Documenting compliance evidence for audits
- 🛡️ Preventing conflicts of interest in approvals

**Example queries:**
> "What LGPDP requirements apply to document verification?"
> "Design audit trail for presupuestación to satisfy ASF requirements"
> "How should we handle data retention for closed solicitudes?"
> "Implement ARCO data access request (ACCESO) workflow"
> "Document compliance evidence for directivo authorizations"

**What you'll get:**
- Regulatory requirements (clear, specific)
- Compliance mapping matrix (laws → system features)
- Audit trail design
- Data retention policies
- ARCO workflow procedures
- Training materials for staff

---

### 4. Frontend/UX Expert

**When to invoke:**
- 🎨 Designing user interfaces or screens
- 🧩 Creating Blade components (reusable UI elements)
- 🎯 Styling with Tailwind CSS
- ⌨️ Adding interactivity with Alpine.js
- 📱 Making interfaces mobile-responsive
- ♿ Improving accessibility (WCAG compliance)
- 🚀 Optimizing page load performance
- 📝 Designing forms and error messages

**Example queries:**
> "Create a multi-step form for solicitud submission"
> "Design the document verification dashboard for admins"
> "Build a Blade component for displaying beneficiary status"
> "Make the page mobile-responsive with Tailwind CSS"
> "Add a confirmation modal with Alpine.js"
> "Improve accessibility - form fields not labeled properly"

**What you'll get:**
- Blade template code
- Tailwind CSS classes
- Alpine.js interactivity code
- Accessibility checklist
- Responsive design patterns
- Component documentation

---

### 5. DevOps Engineer

**When to invoke:**
- ☁️ Deploying SIGO to Azure
- 🔧 Setting up CI/CD pipelines
- 📊 Configuring monitoring and alerting
- 🔐 Setting up SSL certificates and security
- 💾 Managing database backups and recovery
- 🚀 Optimizing performance and scaling
- 📈 Implementing disaster recovery procedures
- 🐳 Containerizing application with Docker

**Example queries:**
> "Set up GitHub Actions CI/CD pipeline for SIGO"
> "Deploy SIGO to Azure App Service + SQL Database"
> "Configure monitoring with Application Insights"
> "Create backup strategy for SQL Server (daily, geo-redundant)"
> "Optimize page load time - currently 5 seconds"
> "Set up staging environment for testing before production"

**What you'll get:**
- Infrastructure as Code (Bicep templates)
- CI/CD workflows (GitHub Actions)
- Deployment scripts
- Configuration checklist
- Monitoring dashboards
- Disaster recovery runbooks

---

### 6. QA Tester

**When to invoke:**
- ✅ Planning tests for new features
- 🧪 Writing unit tests or feature tests
- 🐛 Reproducing and documenting bugs
- 📊 Testing edge cases and error scenarios
- 🔄 Regression testing before releases
- ⚡ Performance and load testing
- 🔐 Testing security and compliance requirements
- 📋 Creating test documentation

**Example queries:**
> "Create test plan for the cold load (carga fría) feature"
> "Write unit tests for budget validation logic"
> "Test the solicitud creation workflow end-to-end"
> "Reproduce this bug: documents showing wrong status"
> "Test LGPDP compliance - verify audit trail is complete"
> "Performance test: can the system handle 1000 concurrent users?"

**What you'll get:**
- Test cases (clear steps, expected results)
- PHPUnit test code
- Test data factories
- Regression test suite
- Performance test results
- Coverage report

---

## Decision Tree

```
┌─ What are you working on?
│
├─ 📊 DATABASE & DATA
│  └─ Use: database-architect
│     Tasks: schema design, migrations, queries, audit tables
│
├─ 🔨 APPLICATION CODE & FEATURES
│  └─ Use: backend-developer
│     Tasks: controllers, services, business logic, APIs
│
├─ ⚖️ LEGAL & COMPLIANCE
│  └─ Use: legal-compliance-expert
│     Tasks: regulations, audit trails, data retention, ARCO
│
├─ 🎨 USER INTERFACE & EXPERIENCE
│  └─ Use: frontend-ux-expert
│     Tasks: Blade components, Tailwind, Alpine.js, accessibility
│
├─ ☁️ INFRASTRUCTURE & DEPLOYMENT
│  └─ Use: devops-engineer
│     Tasks: Azure setup, CI/CD, monitoring, backups
│
└─ ✅ TESTING & QUALITY
   └─ Use: qa-tester
      Tasks: test planning, unit tests, bug verification
```

---

## Example Workflows

### Feature: "Carga Fría" (Cold Load for Non-Tech Beneficiaries)

**Phase 1: Design**
1. **Legal Compliance Expert** → Map LGPDP requirements (consent workflow, audit trail)
2. **Database Architect** → Design audit tables + consent tracking
3. **Frontend/UX Expert** → Sketch admin UI for beneficiary search + document upload

**Phase 2: Implementation**
1. **Backend Developer** → Build CargaFriaService, CargaFriaController
2. **Database Architect** → Create migrations
3. **Frontend/UX Expert** → Build Blade components for multi-step form

**Phase 3: Testing & Deployment**
1. **QA Tester** → Write tests for cold load workflow
2. **QA Tester** → Test LGPDP compliance (consentimiento timing)
3. **DevOps Engineer** → Deploy to staging environment
4. **QA Tester** → Regression testing in staging

**Phase 4: Production**
1. **DevOps Engineer** → Deploy to production with zero-downtime
2. **QA Tester** → Smoke tests in production
3. **Legal Compliance Expert** → Verify audit trail is capturing data correctly

---

### Bug: "Budget Not Deducting from Category"

1. **QA Tester** → Reproduce bug, document steps to recreate
2. **Backend Developer** → Review presupuestación logic, find issue
3. **Database Architect** → Check if budget movements are being recorded correctly
4. **QA Tester** → Write tests to prevent regression
5. **DevOps Engineer** → Deploy fix to production

---

### Audit: "ASF Needs to Verify Presupuestación Traceability"

1. **Legal Compliance Expert** → Map LFPRH requirements to system features
2. **Database Architect** → Verify audit trail design is complete
3. **QA Tester** → Create test suite to verify every peso is traceable
4. **Backend Developer** → Generate audit report for ASF (if needed)
5. **DevOps Engineer** → Ensure Query performance for large datasets (5+ years of data)

---

## Collaboration Rules

### When Multiple Agents Needed

**Sequential (one after another):**
- 🧠 Legal → 📊 Database → 🔨 Backend → 🎨 Frontend → ✅ QA

**Parallel (independent work):**
- 🎨 Frontend & 📊 Database can work simultaneously
- ☁️ DevOps setup can happen while 🔨 Backend development ongoing

### Communication Patterns

Every agent understands:
- **SIGO context**: Role hierarchy, solicitud workflows, presupuestación concepts
- **Python/Laravel**: Use the existing technology stack
- **Audit trail patterns**: Every action logged (user_id, timestamp, IP, brwaser_agente)
- **LGPDP compliance**: Data protection requirements embedded in design
- **SQL Server specifics**: No UNSIGNED integers, IDENTITY for auto-increment

---

## Invoking an Agent

### Option 1: Direct Reference
> "Can you help me with database design?"

**Agent will auto-load based on query content.**

### Option 2: Explicit Request
> "I need the database-architect agent to design the inventory table schema"

**Agent is explicitly invoked.**

### Option 3: Sub-Task Trigger
During a conversation:

> "[Agent A] is working on the solicitud service. Now I need [Agent B] to test it."

**New agent invoked automatically.**

---

## Agent Limitations

### Each Agent Cannot Do

| Agent | ❌ Cannot | ✅ Can |
|-------|----------|--------|
| **database-architect** | Write UI code | Design schemas, optimize queries |
| **backend-developer** | Design databases | Write business logic, APIs |
| **legal-compliance-expert** | Write code | Map regulations, design compliance |
| **frontend-ux-expert** | Write backend code | Design UI, create components |
| **devops-engineer** | Write business logic | Deploy, monitor, scale |
| **qa-tester** | Fix bugs | Test, validate, document |

### When to Switch Agents

If you're working with **agent-X** and need **agent-Y's expertise**, say:

> "I need to switch to the [agent-y-name] for the next step."

---

## Training & Getting Started

### For Developers New to SIGO

**Recommended Agent Sequence:**
1. **Legal Compliance Expert** → Understand regulations + audit requirements
2. **Database Architect** → Learn data model
3. **Backend Developer** → Build features
4. **Frontend/UX Expert** → Create UI
5. **QA Tester** → Write tests

### Domain Knowledge Required by Each Agent

```
database-architect
├─ ✅ SQL Server syntax + Eloquent ORM
├─ ✅ LGPDP retention periods (3mo active/2yr archive)
├─ ✅ Presupuestación concept (3 phases: comprometido/devengado/pagado)
└─ ✅ Audit trail patterns

backend-developer
├─ ✅ Laravel 11 conventions
├─ ✅ Google Drive API integration
├─ ✅ Email notification patterns
└─ ✅ Role-based access (Beneficiary/Admin/Directivo)

legal-compliance-expert
├─ ✅ Mexican federal/state regulations applied to SIGO
├─ ✅ LGPDP Art. 6 (consent), 15-19 (ARCO), 33 (security)
├─ ✅ LFPRH Art. 9 (budget phases), 88 (documentation)
└─ ✅ Firmas digitales (SEL 2012)

frontend-ux-expert
├─ ✅ Tailwind CSS + Alpine.js
├─ ✅ Blade component syntax
├─ ✅ WCAG 2.1 accessibility standards
└─ ✅ Mobile-first responsive design

devops-engineer
├─ ✅ Azure services (App Service, SQL Database, Key Vault)
├─ ✅ GitHub Actions CI/CD syntax
├─ ✅ Infrastructure as Code (Bicep)
└─ ✅ Monitoring + alerting concepts

qa-tester
├─ ✅ PHPUnit testing framework
├─ ✅ SIGO business workflows
├─ ✅ Compliance testing (LGPDP, audit trail)
└─ ✅ Performance testing concepts
```

---

## Support & Feedback

### Reporting Issues with an Agent

If an agent:
- Misunderstands SIGO context
- Provides incorrect code
- Refers you to wrong agent
- Lacks specific domain knowledge

**Provide feedback to agent developers** with:
1. What you asked
2. What the agent said/did
3. Why it was incorrect
4. Expected alternative response

---

## Philosophy

> **"Right Agent for the Right Task"**

Each SIGO agent is **deeply specialized** in one domain. By routing your task to the correct agent, you get:
- ✅ **Faster solutions** (focused expertise)
- ✅ **Better code quality** (domain-specific patterns)
- ✅ **Compliance built-in** (regulatory knowledge embedded)
- ✅ **Consistent architecture** (shared SIGO conventions)

**The goal: Turn complex projects into manageable, high-quality sprints.**

---

**Last Updated:** March 28, 2026  
**Agents Maintained:** Technological National Institute - Tepic Campus  
**For INJUVE Nayarit**
