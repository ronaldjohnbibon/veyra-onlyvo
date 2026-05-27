# REFERENCE PROMPT

I want you to polish the prompt below.

Requirements:

- Make it one complete copy-paste format.
- Keep it clear and direct.
- Avoid nested code blocks so the output stays complete.

Prompt:

We are working on two folders:

- Reference only: C:\Users\lenovo\Desktop\projects\saas
- Target project: C:\Users\lenovo\Desktop\projects\veyra-onlyvo

Rules:

- Read C:\Users\lenovo\Desktop\projects\veyra-onlyvo\codex\codex.md.
- Use `saas` only as reference. Do not modify anything inside it.
- Apply all changes only inside `veyra-onlyvo`.
- Follow the existing `veyra-onlyvo` project structure, coding style, and patterns.
- When changing frontend code, also check and update related backend code if needed.
- When changing backend code, also check and update related frontend code if needed.
- Use existing shadcn UI components for fields, buttons, dialogs, tables, cards, and other UI elements when suitable.
- Keep the code simple, direct, and human-coded.
- Avoid unnecessary abstraction, over engineering, or unrelated refactoring.
- Make sure the final code is working.
- Do not create tests.
- Add short, direct comments for important logic and functions. Each comment should be simple and explain what the code does in one short sentence.
- Before creating a migration, check existing migrations first. If a related migration already exists, modify and extend it instead of creating a duplicate migration.

Task:
[Describe the task here]

# BASIC PROMPT

I want you to polish the prompt below.

Requirements:

- Make it one complete copy-paste format.
- Keep it clear and direct.
- Avoid nested code blocks so the output stays complete.

Prompt:

Rules:

- Read C:\Users\lenovo\Desktop\projects\veyra-onlyvo\codex\codex.md.
- Follow the existing project structure, coding style, and patterns.
- When changing frontend code, also check and update related backend code if needed.
- When changing backend code, also check and update related frontend code if needed.
- Use existing shadcn UI components for fields, buttons, dialogs, tables, cards, and other UI elements when suitable.
- Keep the code simple, direct, and human-coded.
- Avoid unnecessary abstraction, over engineering, or unrelated refactoring.
- Make sure the final code is working.
- Do not create tests.
- Add short, direct comments for important logic and functions. Each comment should be simple and explain what the code does in one short sentence.
- Before creating a migration, check existing migrations first. If a related migration already exists, modify and extend it instead of creating a duplicate migration.

Task:
[Describe the task here]

# NEW TEMPLATE PROMPT

Read `codex/codex.md`.

Study `C:\Users\lenovo\Desktop\projects\templates\2117_infinite_loop` and add it as a new configurable template in the existing Templates module.

Requirements:

- Follow existing project structure and patterns.
- Do not refactor the migration architecture.
- Add the Vue template under the current registry convention.
- Copy required assets into `public`.
- Add catalog metadata, field schema, and default content using the existing catalog/migration pattern.
- Make all meaningful content configurable through the current Templates flow.
- Update related frontend/backend mapping only if needed.
- Keep existing templates working.
- Do not create tests.
- Run PHP syntax checks, frontend lint/typecheck/build, and verify the catalog row/assets work.
