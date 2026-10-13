# Git workflow

- Work only on a task branch derived from the protected base branch.
- Protected branches are `main`, `master`, and `develop`, plus any branch explicitly marked protected by the repository owner.
- If the current branch is protected, create a task branch before editing code. Prefer `feature/<taiga-id>-<slug>` or `fix/<taiga-id>-<slug>` when a Taiga reference exists.
- Pushes to task branches are allowed after the relevant checks pass. Use `git push -u origin <task-branch>` when the remote is configured.
- Never push, merge into, or rewrite a protected branch. Do not force-push unless the user explicitly authorizes it.
- Keep commits focused and do not include unrelated pre-existing changes.
- Finish implementation with the task branch in `ready-for-human`; the human reviewer decides when to merge into the protected branch.
