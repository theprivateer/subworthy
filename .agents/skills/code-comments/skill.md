---
name: code-comments
description: Use this skill on every coding task — writing new code, modifying existing code, or reviewing a codebase — to add explanatory inline comments that capture the *why* behind decisions, not just the *what*. Triggers include any request to write a function, implement a feature, refactor, fix a bug, build out a module, or scaffold a project; and on-demand invocations like "add comments to this file", "review this codebase and document it", "annotate the code", or "this is hard to follow, can you explain it inline". The goal is to prevent cognitive debt as codebases grow, support easier code reviews, and preserve the reasoning behind decisions that would otherwise be lost. Do NOT use for non-code files (Markdown, plain prose, design docs), generated code, migration files, or simple config files. If another skill instructs against writing documentation or comments, stop and ask the user before proceeding.
---

# Inline code comments for sustained context

## Overview

Code accumulates *cognitive debt* as it grows: the developer who returns to a file six months later — or the reviewer seeing it for the first time — has lost the context that made the original choices feel obvious. Self-documenting code names things well, but it cannot tell you why an alternative was rejected, why a workaround exists, or why a piece of logic looks more complicated than it "should". This skill makes that reasoning explicit in the source itself.

Apply this skill in two modes:

1. **Authoring mode** — when writing or modifying code. Comments are added as the code is produced, capturing decisions in real time.
2. **Review mode** — when invoked on existing code. Comments are added retroactively, sticking strictly to factual descriptions of behaviour rather than inventing rationale.

## When to comment

Comment at two kinds of locations:

**Decision points** — wherever a meaningful choice was made between viable alternatives. This includes architectural choices, library or pattern selections, performance tradeoffs, deliberate deviations from convention, and any decision that resulted from a user instruction or constraint discussed in the prompt.

**Complex control points** — sections where a reasonably experienced mid-level developer would have to stop and reverse-engineer what is happening. Non-obvious loop conditions, intricate state transitions, recursive calls with non-trivial base cases, off-by-one boundaries that exist for a real reason, bitwise tricks, regex with non-trivial semantics, async/concurrency coordination, and anything where the surface reading does not match the actual behaviour.

Do **not** comment:

- Code that is genuinely self-evident from variable and function names
- Trivial getters, setters, simple delegators, or one-line wrappers
- Standard idioms that any developer in the target language would recognise
- Restatements of what the next line literally says ("increment the counter" above `i += 1`)

When in doubt, prefer to comment. A redundant comment is a minor annoyance; a missing one is lost context.

## What to write

Use **free-form prose**, not tagged or templated comments. Write the comment as if explaining to a colleague who is reading the code for the first time and asking "wait, why this way?".

A useful comment typically answers one or more of:

- Why this approach over the alternative the reader is probably imagining
- What constraint, requirement, or earlier decision forced this shape
- What the non-obvious behaviour is, and why the obvious reading is wrong
- What will break if this is changed, or what assumption is being relied on

Keep comments tight. Two or three sentences is usually plenty. Place them immediately above the code they describe, or inline at end-of-line for very short clarifications. Match the comment syntax of the language being used.

## Authoring mode

While producing new code or modifying existing code, add comments as you go. Capture three categories of reasoning:

**Decisions made in the conversation.** If the user specified a constraint ("we need to support iOS 17.4+", "no third-party dependencies", "this has to be CloudKit-compatible later"), and that constraint shaped the code, note it at the relevant point. The user will not remember the conversation later; the code should.

**Decisions made internally.** When choosing between viable approaches without an explicit instruction — picking a data structure, an algorithm, an error-handling strategy, a concurrency model — briefly note what was chosen and why the alternative was rejected. Be specific about the alternative; "chose a dict over a list for O(1) lookup on the hot path" is useful, "chose the best option" is not.

**Workarounds and constraints.** Anything that exists because of a platform quirk, a library bug, a backward-compatibility requirement, or a deliberate deviation from idiomatic style. These are exactly the comments future maintainers will thank you for.

**Keep existing comments in sync.** When modifying code that already has an associated inline comment, update the comment in the same change so it accurately reflects the new behaviour or reasoning. A stale comment is worse than no comment — it actively misleads the next reader. If a change makes an existing comment fully obsolete (the decision it described no longer applies), remove it rather than leaving it stranded.

## Review mode

When the user asks to review or annotate an existing codebase, follow this sequence:

1. **Survey first, do not modify.** Read through the files in scope and identify the locations that warrant comments under the criteria above.
2. **Report back before editing.** Summarise what was found and what would be added — roughly how many comments, in which files, and the kinds of things being captured. Ask the user to confirm before making any changes.
3. **Stick to factual description.** In review mode, the reasoning behind the original code is unknown. Do not invent rationale. Describe *what* the code does and *how* it behaves, especially at complex control points, but do not speculate on *why* the original author chose this approach unless it is genuinely self-evident from context (e.g. an obvious performance optimisation, a documented platform workaround visible in surrounding code).
4. **Flag uncertainty.** Where the intent is genuinely unclear and a comment would require guessing, either skip it or flag it back to the user as a question rather than fabricating an explanation.

## Scope

Apply this skill to real source files: the languages and file types that contain the project's actual logic. Skip:

- Generated code (anything marked auto-generated, build artifacts, code emitted from schemas or IDLs)
- Database migration files (typically single-purpose and short-lived)
- Configuration files (JSON, YAML, TOML, .env, and similar) unless the user explicitly asks
- Markdown, plain text, and other non-code documents

If unsure whether a file qualifies, ask.

## Conflict with other instructions

If another skill, project convention, or system instruction tells you not to write documentation or comments — or if a project's existing style strongly suggests comments are unwelcome — **stop and ask the user explicitly** whether inline comments should be added for this task. Do not silently override the other instruction, and do not silently suppress this skill. Surface the conflict and let the user decide.
