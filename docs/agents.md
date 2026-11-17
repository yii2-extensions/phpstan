# AI assistance

The package owns [yii2-phpstan-analysis](../skills/yii2-phpstan-analysis/SKILL.md), a skill for configuring this
extension, investigating inferred types and migrating an existing analysis setup.

## Install in an application

### Automatic Composer installation

Complete the
[one-time application setup](https://yii2-framework.github.io/docs/project/ai-agents/#composer-installation) to enable
`llm/skills` and set `auto-sync` to `true` in `skills.json`. Approve `yii2-extensions/phpstan` explicitly in
`dependencies.composer.trusted`. The installed package must include its skill bundle; its `extra.skills.source` declares
the directory.

```bash
composer install
composer skills:show
```

With `auto-sync` enabled, later dependency installs and updates synchronize the skill automatically; no per-skill
installer is required. If `auto-sync` is `false`, run `composer skills:update` after dependency installation or updates.

### Optional npx installation

Run the pinned installer from the application root using a source checkout that contains the skill:

```bash
npx skills@1.7.2 add /absolute/path/to/phpstan --list
npx skills@1.7.2 add /absolute/path/to/phpstan \
  --skill yii2-phpstan-analysis --agent claude-code codex opencode
```

You can also use `./vendor/yii2-extensions/phpstan` as the source. Composer installation alone does not expose its skill
to a client at the application root. Copy or link the complete skill directory and refresh copied skills when upgrading
the package. Omit `--global` for project scope.

## Native discovery and activation

| Client                                                | Project directory                       | Explicit activation                                      |
| ----------------------------------------------------- | --------------------------------------- | -------------------------------------------------------- |
| [Claude Code](https://code.claude.com/docs/en/skills) | `.claude/skills/yii2-phpstan-analysis/` | `/yii2-phpstan-analysis`                                 |
| [Codex](https://learn.chatgpt.com/docs/build-skills)  | `.agents/skills/yii2-phpstan-analysis/` | `$yii2-phpstan-analysis` or the skill picker             |
| [OpenCode](https://opencode.ai/docs/skills/)          | `.agents/skills/yii2-phpstan-analysis/` | Ask it to load the named skill through its `skill` tool. |

OpenCode also supports `.opencode/skills/` and `.claude/skills/`. The pinned installer's
[client definitions](https://github.com/vercel-labs/skills/blob/v1.7.2/src/agents.ts) use `.agents/skills/` for Codex
and OpenCode, and `.claude/skills/` for Claude Code. This checkout links both discovery paths to the canonical `skills/`
directory, without separate client copies.

Open a fresh session at the application root, check the client's available skills and invoke the skill. The installer's
`--list` checks source discovery; native discovery and activation are checked in the client.

## Verify the result

Ask the agent to configure separate Web/console analyses, resolve a typed factory or diagnose an unresolved service. Run
the selected PHPStan configuration and check the inferred types. See [testing](testing.md).

## Example prompts

Start a fresh client session at the application root. The explicit example uses Codex's `$skill-name` notation; use
`/yii2-phpstan-analysis` in Claude Code or the installed client's native skill mechanism. Do not paste the skill body
and count that as native activation. These are tasks to execute and verify, not claims that an evaluation has passed.

### Explicit invocation

```text
$yii2-phpstan-analysis
Review separate Web and console PHPStan configurations and diagnose an unresolved typed service using the installed
extension. Include an inferred-type assertion and run the relevant analysis without broadening ignored errors.
Inspect the installed revision first. Do not install dependencies or change files without authorization.
```

### Natural selection

```text
A Web application service is being inferred on an unrelated console application receiver. Which configuration and
receiver-type checks should reproduce the difference before changing runtime code?
```

### Verify the outcome

Run the relevant analysis and assert the actual inferred type or diagnostic. Configuration text and a successful skill
response are not analysis results. Record native loading evidence separately from configuration review, executable
checks and browser/runtime results. For a negative selection control, ask an unrelated question in a fresh session and
confirm this package skill is not loaded. Keep evaluation traces private; report unavailable checks rather than treating
discovery as execution.

[Back to documentation](../README.md#documentation)
