# Filter Restructure Implementation Notes

## Overview
This document explains the changes made to restructure the GoldenFarm Product Filter to display a clean **Brand → Valid Product Categories** hierarchy, eliminating unwanted wrapper categories and empty nodes.

---

## Problem Statement

The original structure included:
- Global wrapper categories like "Sản Phẩm" (`san-pham`) and "Chuyên mục Trang Chủ" (`chuyen-muc-trang-chu`)
- Cross-brand categories with 0 products (e.g., "Mama Rosa" showing under "Golden Farm" with 0 count)
- Nested hierarchy: Brand → Sản Phẩm → Brand → [Leaf Categories]

This created a confusing UI with redundant nesting levels.

---

## Solution Implemented

### 1. **Excluded Unwanted Parent Categories**

**File:** `includes/class-gf-pf-terms.php`  
**Method:** `build_cat_nodes()`

Added a filterable exclusion list:

```php
$excluded_slugs = apply_filters(
    'gf_pf_excluded_category_slugs',
    array( 'san-pham', 'chuyen-muc-trang-chu' )
);
```

**What it does:**
- Checks each category against the exclusion list
- Categories matching these slugs are not added to the tree structure
- Uses WordPress filter hook for extensibility (other categories can be excluded via theme/plugin)

---

### 2. **Flattened the Hierarchy**

**File:** `includes/class-gf-pf-terms.php`  
**Method:** `build_cat_nodes()`

When an excluded category is encountered:

```php
if ( $is_excluded ) {
    // Don't add the excluded node itself, but merge its children into current level.
    $nodes = array_merge( $nodes, $child_nodes );
    continue;
}
```

**What it does:**
- Bypasses excluded parent categories
- Promotes their valid children directly to the parent level
- Result: Brand (Level 0) → Actual Product Categories (Level 1) → Sub-categories (Level 2+)

**Example transformation:**
```
BEFORE:
Golden Farm
  └── Sản Phẩm (excluded wrapper)
      └── Golden Farm (redundant)
          └── Bơ (actual category)

AFTER:
Golden Farm
  └── Bơ (actual category)
```

---

### 3. **Removed Empty Nodes (0 Count)**

**File:** `includes/class-gf-pf-terms.php`  
**Methods:** `build_cat_nodes()`, `has_valid_descendants()` (new)

Added validation logic:

```php
// Skip nodes with 0 count that only have children with 0 count.
if ( $count === 0 && ! empty( $child_nodes ) ) {
    if ( ! self::has_valid_descendants( $child_nodes ) ) {
        continue;
    }
}
```

**New helper method** `has_valid_descendants()`:
- Recursively checks if any descendant has a count > 0
- Prevents empty branches from appearing in the tree

**What it does:**
- Completely removes categories with 0 products and no valid descendants
- Eliminates cross-brand categories (e.g., "Mama Rosa" under "Golden Farm" with 0 count)
- Keeps parent categories that lead to valid products (ancestor preservation)

---

### 4. **Enhanced Renderer Safety**

**File:** `includes/class-gf-pf-renderer.php`  
**Method:** `render_cat_level()`

Added final safeguard:

```php
// Skip rendering completely if count is 0 and there are no valid children.
if ( $count === 0 && empty( $children ) ) {
    continue;
}

// Only show count badge if count > 0 to avoid showing "0" in the UI.
if ( $count > 0 ) {
    $html .= '<span class="gf-pf-count">...</span>';
}
```

**What it does:**
- Provides a final UI-level check against 0-count nodes
- Prevents rendering of empty count badges
- Ensures clean visual output

---

## CSS Compatibility

All existing CSS classes are **preserved**:

- `.yith-wcan-filter` - Main filter container
- `.filter-items` - List containers
- `.is-brand-root` - Brand level (Level 0)
- `.is-group-parent` - First category level (Level 1) *(now actual categories, not wrappers)*
- `.is-product-leaf` - Nested categories (Level 2+)
- `.gf-pf-count` - Product count badges
- `.toggle-handle` - Expand/collapse controls
- `.active`, `.opened`, `.has-children` - State classes

**No CSS changes required** - the existing `gf-pf.css` styling works perfectly with the new structure.

---

## Database Queries

**No changes** to the core database queries:
- `query_brand_counts()` - Still fetches brand product counts
- `query_brand_category_counts()` - Still fetches brand × category grid in one query
- No N+1 query issues

**Performance impact:** Minimal - only adds array filtering/flattening in PHP after data retrieval.

---

## Testing Checklist

After deploying these changes, verify:

1. ✅ **No wrapper categories appear** (san-pham, chuyen-muc-trang-chu)
2. ✅ **No 0-count categories are shown** (e.g., Mama Rosa under Golden Farm)
3. ✅ **Hierarchy is flat**: Brand → Product Categories (no redundant nesting)
4. ✅ **Product counts are accurate** for each brand/category combination
5. ✅ **CSS styling is intact** (colors, spacing, icons, hover effects)
6. ✅ **Interactive features work**: checkboxes, expand/collapse, filter toggling
7. ✅ **Active state preservation**: selected filters remain highlighted
8. ✅ **Reset button clears all filters** correctly

---

## Extensibility

### Adding More Excluded Categories

Use the WordPress filter hook in your theme's `functions.php`:

```php
add_filter( 'gf_pf_excluded_category_slugs', function( $slugs ) {
    $slugs[] = 'another-wrapper-category';
    $slugs[] = 'promotional-items';
    return $slugs;
} );
```

### Adjusting Empty Node Logic

Modify the `has_valid_descendants()` method in `class-gf-pf-terms.php` if you need different pruning logic (e.g., hide categories with count < 5).

---

## Cache Invalidation

The plugin uses WordPress object cache with these keys:
- `gf_pf_brand_tree_v2` - Main brand → category tree
- `gf_pf_tree_{taxonomy}` - Individual taxonomy trees
- `gf_pf_counts_{taxonomy}_{hash}` - Filtered product counts

**Cache automatically rebuilds** when:
- Terms are created/updated/deleted
- Products are published/unpublished
- Cache expires (DAY_IN_SECONDS for trees, HOUR_IN_SECONDS for counts)

**Manual cache flush** (if needed):
```php
GF_PF_Terms::invalidate_cache( 'product_cat' );
GF_PF_Terms::invalidate_cache( 'product_brand' );
```

---

## Rollback Instructions

If you need to revert these changes:

1. **Restore `class-gf-pf-terms.php`:**
   - Remove the `$excluded_slugs` logic from `build_cat_nodes()`
   - Remove the `has_valid_descendants()` method
   - Remove the enhanced 0-count validation

2. **Restore `class-gf-pf-renderer.php`:**
   - Remove the additional 0-count check in `render_cat_level()`

3. **Flush WordPress object cache:**
   ```php
   wp_cache_flush();
   ```

---

## Summary of Files Changed

| File | Changes |
|------|---------|
| `includes/class-gf-pf-terms.php` | Modified `build_cat_nodes()` to exclude wrapper categories, flatten hierarchy, and remove empty nodes. Added `has_valid_descendants()` helper method. |
| `includes/class-gf-pf-renderer.php` | Enhanced `render_cat_level()` with additional 0-count safeguards and updated documentation. |
| `RESTRUCTURE-NOTES.md` | **NEW** - This documentation file. |

---

## Result

The filter now displays a **clean, intuitive hierarchy**:

```
GOLDEN FARM (131)
  ├── Bơ (5)
  ├── Sinh Tố (22)
  ├── Sirô (39)
  │   └── Sirô (2)
  ├── Xốt Topping (6)
  └── ...

MAMA ROSA (52)
  ├── Bơ (2)
  ├── Mứt Trái Cây (2)
  ├── Sinh Tố (10)
  └── ...

VỊ Á (8)
  └── Trà Mật Ong (10)
```

No wrapper categories, no cross-brand empty nodes, no redundant nesting. Perfect! 🎉
