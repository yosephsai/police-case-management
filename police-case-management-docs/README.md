# Project Documentation Pack

These documents are the working reference for the existing Laravel application.

## Install into the project

Extract or copy these Markdown files into the root of:

`~/police-case-management`

For example, after downloading the ZIP:

```bash
cd ~/Downloads
unzip police-case-management-docs.zip
cp police-case-management-docs/*.md ~/police-case-management/
```

Review any existing `AGENTS.md` and `CLAUDE.md` before merging instructions. Do not overwrite useful existing project guidance blindly.

## Documents

- `project.md` — purpose, scope, users, workflow, and requirements.
- `state.md` — current verified project state and next actions.
- `design.md` — interface and Blade design principles.
- `security.md` — security, permissions, privacy, and integrity rules.
- `agent.md` — operating instructions for a local coding agent.
- `implementation_plan.md` — phased implementation plan.
- `decisions.md` — decisions and assumptions register.
- `testing.md` — verification strategy and test log.
- `MASTER_PROMPT.md` — initial audit and documentation prompt for the local AI.

## Grounding rule

The updated requirements document is the functional source of truth. These Markdown files are a practical project guide derived from it and from the developer's clarified learning and technology constraints. Where a detail is not settled by the source, it is marked as a proposal, assumption, or item requiring confirmation.

Do not claim that a requirement is implemented until it has been verified in the actual codebase and tested.
