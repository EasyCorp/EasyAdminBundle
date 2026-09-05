# Manual evaluation of the agent skill

The unit tests in `tests/Unit/Skill/` prove that every symbol the skill names
exists. They cannot prove that an AI coding agent produces working code after
reading it. This evaluation does, and it runs before every release (see
`.github/RELEASE_CHECKLIST.md`). It takes about 30 minutes.

## Setup

1. Create a throwaway Symfony application and install this checkout of the
   bundle through a `path` repository, so the evaluated skill is the one about
   to be released:

   ```bash
   symfony new eval-app --webapp
   cd eval-app
   composer config repositories.easyadmin path /path/to/EasyAdminBundle
   composer require easycorp/easyadmin-bundle:@dev
   php bin/console easyadmin:ai:install --all --no-interaction
   ```

2. Create the entities that the tasks mention (`Product`, `Category`, `Tag`,
   `Shop`, `User`) with `make:entity`, or copy them from a previous run. The
   tasks describe every property they need.

## Arms

Run each task with two arms and the same model:

- **Without skill**: delete `.claude/skills/easyadmin/`, `.agents/skills/easyadmin/`
  and the `<easyadmin-guidelines>` block from `CLAUDE.md`/`AGENTS.md`.
- **With skill**: the files installed by `easyadmin:ai:install`.

Use at least one agent whose model is the most capable one available to you
and one smaller model, and repeat each cell at least three times: agents are
not deterministic and a single run proves nothing.

## Procedure

For every run:

1. Give the agent the task file verbatim (`task-a.md` or `task-b.md`).
2. Save the generated files.
3. Run the verifier prompt in `verifier.md` against the generated files, with
   a second agent that has read access to this repository (the ground truth is
   `src/`, `doc/` and `UPGRADE.md`).
4. Record the counts of `fatal`, `wrong` and `old_version` verdicts, and for
   each one the `file:line` in this repository that the verifier cites.

## Pass criteria

The "with skill" arm passes when, across all repetitions and models:

- zero `fatal` verdicts (a symbol that does not exist),
- zero `wrong` verdicts on EasyAdmin symbols (wrong argument order, wrong
  semantics),
- zero `old_version` verdicts (EasyAdmin 4 API).

Invented Symfony route names, entity accessors or plain Symfony mistakes do
not count: the skill is about the EasyAdmin API.

When a verdict appears in every repetition of a cell, the skill is missing or
misstating a rule. Add or fix the rule in `skills/easyadmin/SKILL.md` with its
provenance comment, add a `source_contains` anchor to the trailer when the
rule describes behavior, and run the evaluation again.

The "without skill" arm is a baseline: record its counts so the effect of the
skill stays visible from release to release.
