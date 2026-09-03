---
name: project-shortcode-id-resolution-coverage-gap
description: No Pest or Codeception test targets Item_Management::get_unique_id_from_shortcode_id, Layout_Shortcode/Post_Selection_Shortcode render_shortcode, or Cpt_Block/Layout_Block — flag this gap on future passes touching that path.
metadata:
  type: project
---

As of 2026-09-03, searching both `tests/php` (Pest) and `tests/lite`+`tests/pro` (Codeception/wpunit) turns up **no test
coverage at all** for:

- `Item_Management::get_unique_id_from_shortcode_id()` (backs the public `[acf-views]`/`[avf-layout]`/etc. shortcode
  `id` attribute resolution across the whole plugin, Lite and Pro)
- `Layout_Shortcode`/`Post_Selection_Shortcode` `render_shortcode()`
- `Cpt_Gutenberg_Block`/`Layout_Gutenberg_Block` (Gutenberg block rendering, style-tag injection, items-list REST route)
- Any `do_shortcode()`-driven rendering of `[acf-views]`/`[avf-layout]`/`[avf-post-selection]` tags

`tests/lite/wpunit/Cpt/Shortcode_Test.php` exists but only covers `is_shortcode_available_for_user()`'s role-gating
logic — it does not touch id resolution or rendering at all, despite the filename suggesting broader coverage.

**Why:** confirmed while reviewing a change that added a fast-path branch to `get_unique_id_from_shortcode_id()`
(recognizing an already-full unique id, e.g. from Gutenberg blocks storing full ids post-refactor) — a change explicitly
called out as high-reach (public shortcode path, both editions) with a request to verify existing test coverage still
passes. There was none to verify against; `php_vanilla` + the full `codeception` (wpunit) suite both stayed green (808
wpunit tests / 39 Pest tests), so the change itself checked out, but the gap itself is worth surfacing rather than
silently treating "tests pass" as "this path is covered."

**How to apply:** on any future change to shortcode id resolution, `Cpt_Gutenberg_Block`/`Layout_Gutenberg_Block`, or
`render_shortcode()`, don't assume a green suite run means this logic was exercised — call out to the user/parent agent
that this area still has no direct test coverage, so they can decide whether to add some rather than relying solely on
the guardian's after-the-fact search-and-verify pass.
