# AI assistance

The package owns [yii2-phpstan-analysis](../skills/yii2-phpstan-analysis/SKILL.md), a skill for configuring this
extension, investigating inferred types and migrating an existing analysis setup. It does not install PHP
dependencies, attach behaviors or configure application runtime.

## Install in an application

Run the pinned installer from the application root using a source checkout that contains the skill:

```bash
npx skills@1.7.2 add /absolute/path/to/phpstan --list
npx skills@1.7.2 add /absolute/path/to/phpstan \
  --skill yii2-phpstan-analysis --agent claude-code codex opencode
```

A Composer-installed package can be a source at `./vendor/yii2-extensions/phpstan` when that release includes
`skills/`. Installing PHP dependencies alone does not expose a skill under `vendor/` to a client opened at the
application root. Retain the complete named skill directory when copying it; installation copies are snapshots
that must be refreshed when the package is upgraded.

For a public source, first run `npx skills@1.7.2 add yii2-extensions/phpstan --list`. Install from that source only
after the listing confirms the skill is on the default branch. Unpublished local work is not available there.
Keep project scope by omitting `--global`.

## Native discovery and activation

| Client                                                | Project directory                       | Explicit activation                                      |
| ----------------------------------------------------- | --------------------------------------- | -------------------------------------------------------- |
| [Claude Code](https://code.claude.com/docs/en/skills) | `.claude/skills/yii2-phpstan-analysis/` | `/yii2-phpstan-analysis`                                 |
| [Codex](https://learn.chatgpt.com/docs/build-skills)  | `.agents/skills/yii2-phpstan-analysis/` | `$yii2-phpstan-analysis` or the skill picker             |
| [OpenCode](https://opencode.ai/docs/skills/)          | `.agents/skills/yii2-phpstan-analysis/` | Ask it to load the named skill through its `skill` tool. |

OpenCode also supports `.opencode/skills/` and `.claude/skills/`. The pinned installer's
[client definitions](https://github.com/vercel-labs/skills/blob/v1.7.2/src/agents.ts) use `.agents/skills/` for Codex
and OpenCode, and `.claude/skills/` for Claude Code. This checkout links both discovery paths to the canonical
`skills/` directory. Avoid separately edited client copies and inspect duplicate entries if a client sees both paths.

Restart or open a fresh session at the application root, inspect its available skills, and explicitly invoke the
skill. `--list` checks installer discovery; reading the Markdown manually checks access. Neither proves that a
native client discovered or activated it. Automatic selection is model-dependent; also try a natural task prompt
without naming the skill.

## Verify the result

Ask the agent to configure separate Web/console analyses, resolve a typed factory and container alias, or diagnose
an `object` return from an untyped factory. Check the files and run the selected PHPStan configuration. Keep the
application's paths, constraints, registrations and other extension settings intact. Do not accept broad error
suppression or production bootstrap as evidence of a successful setup.

Record installation, native discovery, activation, selective documentation reading and functional checks
separately. A client's successful skill activation does not establish correct application inference. Verify the
actual inferred types and run the relevant application checks; see [testing](testing.md).
