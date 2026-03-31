# 📚 SIGO Specialized Agents - Quick Setup Guide

**Created:** March 28, 2026  
**For:** Instituto Nayarita de la Juventud (INJUVE)  
**Location:** `.github/agents/` directory in SIGO project

---

## ✅ Agents Created

### 1. **Database Architect** 
📁 `database-architect.agent.md`
- **Specialization:** SQL Server, Eloquent ORM, migrations, audit trails
- **Invoke when:** Database design, schema changes, query optimization
- **Key domains:** Presupuestación models, audit tables, LGPDP retention

### 2. **Backend Developer**
📁 `backend-developer.agent.md`
- **Specialization:** Laravel 11, PHP, business logic, services
- **Invoke when:** Feature implementation, API design, bug fixes
- **Key domains:** Solicitud workflows, Google Drive integration, transactions

### 3. **Legal Compliance Expert**
📁 `legal-compliance-expert.agent.md`
- **Specialization:** LGPDP, LFTAIPG, LFPRH, Mexican regulations
- **Invoke when:** Regulatory requirements, audit trails, compliance design
- **Key domains:** Data retention, ARCO workflows, firma digital validation

### 4. **Frontend/UX Expert**
📁 `frontend-ux-expert.agent.md`
- **Specialization:** Blade, Tailwind CSS, Alpine.js, accessibility
- **Invoke when:** UI design, components, styling, responsive layouts
- **Key domains:** Multi-step forms, document viewer, WCAG compliance

### 5. **DevOps Engineer**
📁 `devops-engineer.agent.md`
- **Specialization:** Azure, CI/CD, monitoring, infrastructure
- **Invoke when:** Deployments, infrastructure setup, monitoring
- **Key domains:** Azure App Services, SQL Database, disaster recovery

### 6. **QA Tester**
📁 `qa-tester.agent.md`
- **Specialization:** PHPUnit, test strategy, compliance testing
- **Invoke when:** Test planning, bug verification, quality assurance
- **Key domains:** Regression testing, LGPDP compliance testing

### 7. **Master Index**
📁 `AGENTS.md`
- Central documentation for all agents
- Decision tree for choosing agent
- Workflow examples
- Collaboration patterns

---

## 🎯 Quick Start: How to Use Agents

### In Your IDE (VS Code)

1. **Ask a question** about SIGO development
2. **Mention the agent** or **describe what you need**
3. **Agent loads automatically** based on context

**Examples:**

```
"Design a database table for material inventory"
→ database-architect loads

"Build the verification service for documents"  
→ backend-developer loads

"What are LGPDP requirements for solicitud data?"
→ legal-compliance-expert loads

"Create a multi-step form for beneficiary registration"
→ frontend-ux-expert loads

"Set up CI/CD pipeline for GitHub Actions"
→ devops-engineer loads

"Write tests for the presupuestación validation"
→ qa-tester loads
```

---

## 📊 Agent Specialization Matrix

| Domain | Expertise | Agent |
|--------|-----------|-------|
| **Data Layer** | SQL Server, Eloquent, migrations, audits | database-architect |
| **Business Logic** | Laravel services, controllers, APIs | backend-developer |
| **Compliance** | Laws, regulations, audit design | legal-compliance-expert |
| **User Interface** | UI/UX, Blade, Tailwind, accessibility | frontend-ux-expert |
| **Infrastructure** | Azure, CI/CD, monitoring, security | devops-engineer |
| **Quality** | Testing, validation, compliance checks | qa-tester |

---

## 🔄 Typical Workflow by Feature

### Example: "Implement Carga Fría (Cold Load) Feature"

```
1. Legal Compliance Expert
   ↓ "What are LGPDP requirements?"
   ↓ Gets: Consent workflow, audit requirements, training needs

2. Database Architect
   ↓ "Design audit tables for cold load"
   ↓ Gets: Migration files, table schemas, relationships

3. Backend Developer
   ↓ "Build CargaFriaService and controller"
   ↓ Gets: Service implementation, business logic, error handling

4. Frontend/UX Expert
   ↓ "Create multi-step form for admin interface"
   ↓ Gets: Blade components, Tailwind styling, Alpine interactivity

5. QA Tester
   ↓ "Write tests for cold load workflow"
   ↓ Gets: Test cases, PHPUnit tests, compliance validation

6. DevOps Engineer
   ↓ "Deploy to staging, verify database migrations"
   ↓ Gets: Deployment scripts, migration execution, monitoring
```

---

## 🏗️ Architecture Decisions by Agent

### Database Architect Handles
- ✅ Table schema design
- ✅ Eloquent relationships
- ✅ Index strategy
- ✅ Migration creation
- ✅ Audit trail patterns
- ✅ Data retention policies

### Backend Developer Handles
- ✅ Controller logic
- ✅ Service layer implementation
- ✅ Business rules enforcement
- ✅ Error handling
- ✅ External API integration
- ✅ Transaction management

### Legal Compliance Expert Handles
- ✅ Regulatory mapping (laws → features)
- ✅ Audit trail requirements
- ✅ Data classification
- ✅ Retention periods
- ✅ ARCO workflows
- ✅ Compliance evidence

### Frontend/UX Expert Handles
- ✅ Layout design
- ✅ Blade components
- ✅ CSS styling (Tailwind)
- ✅ Interactivity (Alpine.js)
- ✅ Accessibility (WCAG)
- ✅ Responsive design

### DevOps Engineer Handles
- ✅ Infrastructure provisioning
- ✅ CI/CD pipeline setup
- ✅ Database management
- ✅ Monitoring/alerting
- ✅ Security configuration
- ✅ Disaster recovery

### QA Tester Handles
- ✅ Test planning
- ✅ Test case creation
- ✅ Unit/feature testing
- ✅ Bug verification
- ✅ Regression testing
- ✅ Compliance validation

---

## 📋 SIGO-Specific Knowledge Embedded

Every agent understands:

### System Context
- **Framework:** Laravel 11 on PHP 8.2
- **Database:** SQL Server (IDENTITY not UNSIGNED)
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **UI Pattern:** Portal for beneficiaries, admin dashboard

### Business Domain
- **Beneficiaries:** Youth 12-29 years old, potential low literacy
- **Apoyos:** Economic ($) or In-kind (material)
- **Workflow:** 7-stage solicitud process with multiple verifications
- **Budget:** LFPRH compliance (3 phases: comprometido/devengado/pagado)

### Regulatory Framework
- **LGPDP:** Personal data protection, retention, ARCO rights
- **LFTAIPG:** Public transparency, folio generation, anonymization
- **LFPRH:** Budget execution, audit trails, document retention
- **Firma Digital:** Digital signatures (SEL 2012 compliance)

### Technical Patterns
- **Audit Logging:** Every action logged (user_id, timestamp, IP, navegador_agente)
- **Soft Deletes:** Mark inactive rather than delete
- **Role-Based Access:** Beneficiary(0), Admin L1/L2(1-2), Directivo(3)
- **Feature Flags:** Can enable/disable features without deployment

---

## 🚀 Getting Started

### Step 1: Familiarize Yourself with Agents
Read: `.github/AGENTS.md` (master decision tree)

### Step 2: Reference Individual Agent Docs
- `database-architect.agent.md` (if working on data layer)
- `backend-developer.agent.md` (if building features)
- `legal-compliance-expert.agent.md` (if needing compliance guidance)
- `frontend-ux-expert.agent.md` (if designing UI)
- `devops-engineer.agent.md` (if setting up infrastructure)
- `qa-tester.agent.md` (if testing)

### Step 3: Start Invoking Agents
Just ask your question, and the right agent will load automatically.

---

## 📞 When to Invoke Each Agent

### Database Architect
```
"I need to add a new table for..."
"How do I optimize this slow query?"
"Design the schema for presupuestación..."
"Create migration to add audit logging..."
"What's the best way to model..."
```

### Backend Developer
```
"Implement the solicitud creation feature..."
"Building Google Drive integration..."
"Fix bug where budget isn't deducting..."
"Create email notification service..."
"Handle error scenarios in transactions..."
```

### Legal Compliance Expert
```
"What LGPDP requirements apply?"
"Design audit trail for ASF compliance..."
"How long should we retain this data?"
"Implement ARCO data access workflow..."
"Document firma digital requirements..."
```

### Frontend/UX Expert
```
"Design the verification dashboard..."
"Create a multi-step form component..."
"Make this table mobile-responsive..."
"Add accessibility to form fields..."
"Build the document upload interface..."
```

### DevOps Engineer
```
"Deploy to Azure..."
"Set up CI/CD pipeline..."
"Configure monitoring/alerting..."
"Database backup strategy..."
"How do I handle scaling?"
```

### QA Tester
```
"Write tests for this feature..."
"How do I reproduce this bug?"
"Test LGPDP compliance..."
"Regression test suite for..."
"Performance test the system..."
```

---

## 💡 Best Practices

### ✅ DO
- ✅ Use agents for their domain expertise
- ✅ Follow SIGO conventions (audit logging, soft deletes, roles)
- ✅ Keep LGPDP compliance in mind (every agent knows this)
- ✅ Test before production (QA agent)
- ✅ Document compliance evidence (Legal agent helps)

### ❌ DON'T
- ❌ Ask backend-developer for database schema (use database-architect)
- ❌ Ask frontend-expert for business logic (use backend-developer)
- ❌ Skip legal review (use legal-compliance-expert)
- ❌ Deploy without tests (use qa-tester)
- ❌ Hardcode business rules (make them configurable)

---

## 📚 Documentation Generated

For each domain, the agent provides:
- **Code examples** (ready to use)
- **Architecture patterns** (best practices)
- **Compliance checklist** (ensure nothing missed)
- **Testing strategies** (quality assurance)
- **Performance guidelines** (optimization targets)

---

## 🎓 Learning Path

### Week 1: Foundation
- [ ] Read `.github/AGENTS.md` (understand all agents)
- [ ] Legal Compliance Expert → Learn SIGO regulations
- [ ] Database Architect → Understand data model

### Week 2: Development
- [ ] Backend Developer → Build first feature
- [ ] Frontend/UX Expert → Create UI components
- [ ] QA Tester → Write tests

### Week 3: Operations
- [ ] DevOps Engineer → Deploy to Azure
- [ ] DevOps Engineer → Set up CI/CD
- [ ] QA Tester → Run regression tests

### Ongoing
- [ ] Legal Compliance Expert → Quarterly compliance review
- [ ] QA Tester → Maintain regression test suite
- [ ] Database Architect → Optimize as data grows

---

## 📞 Support

### If an agent seems confused:
1. Provide more context (what you're building, why)
2. Reference `.github/AGENTS.md` for domain guidance
3. Examples help (show code, architecture diagrams)

### If you need multiple agents:
Just mention transitions naturally:
> "The database-architect helped me design the table. Now I need backend-developer to build the service..."

---

## 🏆 Success Indicators

You know the agents are working when:
- ✅ Code is consistent across modules
- ✅ Compliance built-in (not after-thought)
- ✅ Tests pass before pushing
- ✅ Dependencies properly tracked
- ✅ SIGO conventions followed everywhere
- ✅ Team ships faster with fewer bugs

---

**Ready to develop SIGO!** 🚀

Choose an agent based on your current task, and let specialized expertise guide you.
