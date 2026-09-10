# Origins Book

Origins Book keeps Book navigation caches accurate and prevents editors from
orphaning child pages by archiving or deleting their parent. Users with the
`override book parent protection` permission bypass the parent safeguards.

## Cache tags

Book navigation render arrays receive the current `bid:{bid}`, `node:{nid}`
and parent `node:{pid}` tags. Existing tags are retained. When an outline
position changes, the module invalidates the deduplicated tags represented by
both the old and new book links. A position consists of the book, parent and
weight, so adding, removing, reparenting, moving between books and reordering
a page all expire affected navigation.

## Cache invalidation trigger points

- `hook_entity_insert()` handles nodes first inserted with a persisted book
  link.
- `hook_entity_update()` compares the node's original book link with its
  persisted link after saving.
- `hook_entity_predelete()` invalidates the deleted node's current book,
  parent and node tags.
- `BookOutlineForm` records the persisted link before submission and compares
  it with the saved link after the outline submit handler runs.
- `BookRemoveForm` records the current link before submission and invalidates
  it after removal.
- `BookAdminEditForm` compares submitted parent and weight values with each
  row's original values, then invalidates all changed rows as one batch.
- `hook_entity_view_alter()` attaches tags directly to in-node Book navigation.
- `hook_preprocess_book_navigation()` attaches the same tags to every themed
  Book navigation render array. Both attachment points retain existing tags.

Entity and form paths overlap deliberately. Entity hooks cover programmatic
changes, while form handlers retain the before/after Book storage data that is
not reliably available from a saved node alone. Duplicate invalidation is safe.

## Parent-page protection

Any saved book page with children is protected unless the current user has the
override permission. Protection is applied at each available entry point:

- `hook_entity_access()` forbids deletion, including non-form deletion paths.
- `hook_node_presave()` rejects a new transition to the `archived` moderation
  state, including programmatic saves.
- The node form hides Archive and validates the submitted state.
- The moderation-sidebar quick-transition form removes transitions whose
  destination is Archived.
- Entity operation links and the rendered primary Archive action are removed.

The module runs its entity-view alteration after other implementations so an
Archive action added by Origins Workflow can still be removed.

Content Moderation is an optional integration, not a module dependency. The
presave safeguard acts only on nodes which have a `moderation_state` field, and
the moderation-sidebar alteration acts only when core's transition-validation
service is available. Moderation Sidebar itself depends on Content Moderation.

## Other Book behaviour

Book roots and Book-enabled content types are flagged to disable the generated
Origins table of contents. The core `BookOutline` validation constraint is
removed so editors can change the outline regardless of publication status.

## Tests

Run the focused unit tests from a Drupal project which installs this package:

```bash
vendor/bin/phpunit -c phpunit.xml web/modules/origins/origins_book/tests/src/Unit
```

`BookCacheInvalidatorTest` covers tag normalization, insertions, removals,
reparenting, cross-book moves, reordering and no-op changes.
`BookParentPageProtectionTest` covers child detection, override access,
archive decisions and workflow transition filtering.
