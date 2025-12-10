# AGENTS.md

## 🎯 Purpose
This project must always be handled with a focus on **Refactor**.  
Whenever new code or changes are requested, Codex should prioritize:
- **Clean Code practices**
- **Standardization**
- **Removing duplication (DRY)**
- **Eliminating unused code**
- **Improving readability and consistency**

---

## 🛠️ Core Rules
1. **Never provide incomplete code.** Output must always be ready-to-run.
2. **Do not remove or alter core business logic.** Only refactor and improve unless explicitly instructed otherwise.
3. **Always provide full files.** Do not shorten with `...` or omit sections of code.
4. **Comments must always be in English.**
5. **Maintain consistent coding style.** (PSR-12 for PHP, ESLint/Prettier for JS/TS).
6. When multiple approaches are possible, always choose the **most standard and maintainable** one.

---

## 🔄 Codex Responsibilities
- On every change request:
  1. Refactor the complete file or relevant section.
  2. Remove unused imports or dependencies.
  3. Extract repeated logic into shared methods/classes.
  4. Add type-hints and PHPDoc/TypeScript definitions.
  5. Keep folder structures and naming conventions consistent.
- If tests exist:
  - All tests must continue to pass after refactoring.
  - If no tests exist, code should be structured to be testable.

---

## 📦 Expected Output
- Each change must be delivered as a **full file**, not partial code snippets.
- If multiple files are related to the change, provide all of them in full.
- When possible, show **before/after (diff)** views for clarity.

---

## ⚠️ Special Scenarios
- If a part of the code is ambiguous or requires a decision, Codex must provide alternative suggestions (with pros/cons).
- If conflicts exist between versions, Codex must deliver a **unified, consistent, and working** final output.
