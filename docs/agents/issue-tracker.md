# Issue tracker: Local Markdown

Taiga remains the external planning system. Codex does not create or update Taiga items automatically in this repository. Specs and implementation tickets consumed by the engineering skills live as Markdown files under `.scratch/`.

## Conventions

- One feature per directory: `.scratch/<feature-slug>/`.
- The spec is `.scratch/<feature-slug>/spec.md`.
- Tickets are one file per ticket at `.scratch/<feature-slug>/issues/<NN>-<slug>.md`, numbered from `01` in dependency order.
- Preserve any Taiga task ID or URL supplied by the user in the spec and related tickets.
- Local ticket status uses `ready-for-agent`, `in-progress`, `ready-for-human`, and `done`.
- Conversation notes and implementation summaries are appended under `## Comments`.

## When a skill says "publish to the issue tracker"

Write the corresponding Markdown file under `.scratch/`. Do not use `gh`, create GitHub issues, create pull requests, or modify a remote tracker.

## When a skill says "fetch the relevant ticket"

Read the referenced file under `.scratch/`. If only a Taiga ID or URL is provided, preserve it as a reference and ask the user for the local file path only when the local ticket cannot be identified safely.

## Wayfinding operations

- **Map:** `.scratch/<effort>/map.md`.
- **Child ticket:** `.scratch/<effort>/issues/NN-<slug>.md`.
- **Blocking:** record `Blocked by: NN, NN` near the top of the child ticket.
- **Frontier:** tickets whose blockers are all `done` and which are not claimed.
- **Claim:** set `Status: in-progress` before implementation.
- **Resolve:** after implementation and automated checks, set `Status: ready-for-human` and append the result under `## Comments`. Set `done` only after human validation.
