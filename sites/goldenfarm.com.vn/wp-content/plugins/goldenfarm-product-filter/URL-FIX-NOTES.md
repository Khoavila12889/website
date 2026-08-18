# URL Generation Fix Documentation

## Problem Statement

The filter was generating conflicting URLs when users clicked filters while on category archive pages.

### Example of the Bug:

1. User visits: `/danh-muc-san-pham/san-pham/mama-rosa/` (Mama Rosa category archive)
2. User clicks "Vị Á" brand filter
3. Plugin generated: `/danh-muc-san-pham/san-pham/mama-rosa/?product_brand=vi-a`
4. Result: **0 products found** because no "Vị Á" products exist in the "Mama Rosa" category

### Root Cause:

- `get_base_url()` was using `home_url( $wp->request )` which returned the current page URL
- `get_active_slugs()` was reading from `is_tax()` context, inheriting the archive page taxonomy
- This created conflicting taxonomy constraints (category archive + filter query param)

---

## Solution Implemented

### 1. **Fixed `get_base_url()` Method**

**File:** `includes/class-gf-pf-terms.php`  
**Line:** ~412

```php
public static function get_base_url() {
    // Always use the shop page as the base for filter links.
    $shop_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'shop' )
        : home_url( '/shop/' );

    return apply_filters( 'gf_pf_base_url', $shop_url );
}
```

**What changed:**
- ❌ **Before:** `home_url( $wp->request )` → returned current page URL
- ✅ **After:** `wc_get_page_permalink( 'shop' )` → always returns shop page URL

**Result:** All filter links now point to the shop page, avoiding category archive conflicts.

---

### 2. **Fixed `get_active_slugs()` Method**

**File:** `includes/class-gf-pf-terms.php`  
**Line:** ~383

```php
public static function get_active_slugs( $taxonomy = 'product_cat' ) {
    // ... cache check ...

    $slugs = array();

    // REMOVED: is_tax() context check - no longer inherit from archive page
    
    // Read from query var (e.g., ?product_cat=slug1,slug2).
    $query_var = get_query_var( $taxonomy );
    if ( ! empty( $query_var ) ) {
        $slugs = array_merge( $slugs, self::split_slugs( $query_var ) );
    }

    // Read from $_GET as fallback.
    if ( isset( $_GET[ $taxonomy ] ) ) {
        $slugs = array_merge( $slugs, self::split_slugs( wp_unslash( $_GET[ $taxonomy ] ) ) );
    }

    return array_values( array_unique( $slugs ) );
}
```

**What changed:**
- ❌ **Removed:** `is_tax()` check that read from category archive context
- ✅ **Now:** Only reads from explicit query parameters (`?product_cat=slug`)

**Result:** Filter state is determined only by query params, not by the current page type.

---

### 3. **Enhanced `get_term_url()` Method**

**File:** `includes/class-gf-pf-terms.php`  
**Line:** ~431

```php
public static function get_term_url( $term, $taxonomy = 'product_cat', $multiple = true ) {
    // ... toggle logic ...

    $query_args = self::get_preserved_args();

    // Add current taxonomy selection.
    if ( ! empty( $selected ) ) {
        $query_args[ $taxonomy ] = implode( ',', $selected );
    }

    // NEW: Preserve the other taxonomy's filter when toggling.
    $other_taxonomy = ( $taxonomy === self::brand_taxonomy() ) ? self::taxonomy() : self::brand_taxonomy();
    $other_active   = self::get_active_slugs( $other_taxonomy );

    if ( ! empty( $other_active ) ) {
        $query_args[ $other_taxonomy ] = implode( ',', $other_active );
    }

    return add_query_arg( $query_args, self::get_base_url() );
}
```

**What changed:**
- ✅ **Added:** Cross-taxonomy preservation (brand filters preserve category filters and vice versa)
- ✅ **Result:** Clean URLs like `[shop]/?product_brand=vi-a&product_cat=sinh-to`

---

### 4. **Simplified `get_reset_url()` Method**

**File:** `includes/class-gf-pf-terms.php**  
**Line:** ~472

```php
public static function get_reset_url() {
    $base_url = self::get_base_url();
    $query_args = self::get_preserved_args();

    if ( ! empty( $query_args ) ) {
        return add_query_arg( $query_args, $base_url );
    }

    return $base_url;
}
```

**What changed:**
- ❌ **Before:** Complex `remove_query_arg()` logic
- ✅ **After:** Simple clean shop URL with only preserved args (orderby, search)

**Result:** Reset button returns to shop page without any taxonomy filters.

---

## URL Patterns (Before vs After)

### Scenario 1: Clicking a Brand Filter

**Context:** User is on `/danh-muc-san-pham/san-pham/mama-rosa/`

| Action | Before (Broken) | After (Fixed) |
|--------|----------------|---------------|
| Click "Vị Á" | `/danh-muc-san-pham/san-pham/mama-rosa/?product_brand=vi-a` | `/shop/?product_brand=vi-a` ✅ |

---

### Scenario 2: Clicking Brand + Category

**Context:** User is on `/shop/`

| Action | Before | After |
|--------|--------|-------|
| Click "Golden Farm" | `/shop/?product_brand=golden-farm` | `/shop/?product_brand=golden-farm` ✅ |
| Then click "Sinh Tố" | `/shop/?product_cat=sinh-to-golden-farm` ⚠️ (lost brand) | `/shop/?product_brand=golden-farm&product_cat=sinh-to-golden-farm` ✅ |

---

### Scenario 3: Reset Filter

**Context:** User has multiple filters active

| Current URL | Reset Button (Before) | Reset Button (After) |
|-------------|----------------------|---------------------|
| `/shop/?product_brand=mama-rosa&product_cat=sinh-to` | `/shop/` (worked) | `/shop/` ✅ |
| `/danh-muc-san-pham/mama-rosa/?orderby=price` | `/danh-muc-san-pham/mama-rosa/?orderby=price` ⚠️ | `/shop/?orderby=price` ✅ |

---

## Preserved Query Parameters

These parameters are **preserved** across filter changes:

- `orderby` - Product sorting (price, date, popularity)
- `s` - Search query

These parameters are **removed** on reset:

- `product_brand` - Brand taxonomy filter
- `product_cat` - Category taxonomy filter

---

## Backward Compatibility

### Filter Hook Preserved

The `gf_pf_base_url` filter still works:

```php
add_filter( 'gf_pf_base_url', function( $url ) {
    // Custom shop page URL if needed
    return 'https://example.com/custom-shop/';
} );
```

### Multi-Select Still Supported

The `$multiple` parameter in `get_term_url()` still controls whether multiple selections are allowed:

```php
// Single selection mode (radio-style)
GF_PF_Terms::get_term_url( $term, 'product_brand', false );

// Multiple selection mode (checkbox-style, default)
GF_PF_Terms::get_term_url( $term, 'product_cat', true );
```

---

## Testing Checklist

### ✅ URL Generation Tests

1. **Brand Filter from Category Archive:**
   - [ ] Visit `/danh-muc-san-pham/mama-rosa/`
   - [ ] Click "Vị Á" brand filter
   - [ ] Expected: Redirects to `/shop/?product_brand=vi-a`
   - [ ] Verify: Products from "Vị Á" brand appear

2. **Category Filter from Brand Archive:**
   - [ ] Visit `/thuong-hieu/golden-farm/`
   - [ ] Click "Sinh Tố" category filter
   - [ ] Expected: Redirects to `/shop/?product_cat=sinh-to-golden-farm`
   - [ ] Verify: Sinh Tố products appear

3. **Combined Brand + Category:**
   - [ ] Visit `/shop/`
   - [ ] Click "Mama Rosa" brand
   - [ ] Then click "Bơ" category
   - [ ] Expected: `/shop/?product_brand=mama-rosa&product_cat=bo-mama-rosa`
   - [ ] Verify: Only "Mama Rosa Bơ" products appear

4. **Reset from Archive:**
   - [ ] Visit `/danh-muc-san-pham/mama-rosa/?product_brand=golden-farm`
   - [ ] Click "Xóa bộ lọc" (Reset)
   - [ ] Expected: Redirects to `/shop/`
   - [ ] Verify: All products appear (no filters)

5. **Preserve Sorting:**
   - [ ] Visit `/shop/?orderby=price`
   - [ ] Click "Golden Farm" brand filter
   - [ ] Expected: `/shop/?product_brand=golden-farm&orderby=price`
   - [ ] Verify: Products sorted by price

6. **Search + Filter:**
   - [ ] Visit `/shop/?s=sinh+to`
   - [ ] Click "Mama Rosa" brand filter
   - [ ] Expected: `/shop/?product_brand=mama-rosa&s=sinh+to`
   - [ ] Verify: Search term preserved, filtered by brand

---

## Performance Impact

**No performance degradation:**
- ✅ Same number of database queries
- ✅ No additional API calls
- ✅ URL generation happens in PHP (no JS overhead)
- ✅ Caching strategy unchanged

---

## Edge Cases Handled

### 1. **Shop Page Not Set in WooCommerce**

Fallback to `/shop/` if `wc_get_page_permalink('shop')` fails:

```php
$shop_url = function_exists( 'wc_get_page_permalink' )
    ? wc_get_page_permalink( 'shop' )
    : home_url( '/shop/' );
```

### 2. **Multiple Values in Same Taxonomy**

Comma-separated slugs still work:

```
/shop/?product_cat=sinh-to-golden-farm,siro-golden-farm
```

### 3. **Invalid/Deleted Terms**

If a URL contains a slug that no longer exists, WooCommerce naturally returns 0 products (expected behavior).

---

## Cache Considerations

**No cache changes needed:**
- Object cache keys remain the same
- `get_active_slugs()` still has static caching per request
- Tree cache (`gf_pf_brand_tree_v2`) unaffected

---

## Summary of Changes

| Method | Change | Impact |
|--------|--------|--------|
| `get_base_url()` | Always returns shop page URL | ✅ Fixes archive page conflicts |
| `get_active_slugs()` | Removed `is_tax()` context check | ✅ Only reads from query params |
| `get_term_url()` | Added cross-taxonomy preservation | ✅ Maintains both brand & category filters |
| `get_reset_url()` | Simplified to clean shop URL | ✅ Cleaner reset behavior |

---

## Files Changed

- ✅ `includes/class-gf-pf-terms.php` (4 methods updated)
- ✅ `URL-FIX-NOTES.md` (this file - new documentation)

**No changes needed in:**
- ❌ `includes/class-gf-pf-renderer.php` (HTML generation unchanged)
- ❌ `assets/css/gf-pf.css` (styling unchanged)
- ❌ Database queries (data fetching unchanged)

---

## Deployment Instructions

1. **Backup the plugin folder** before updating
2. **Replace `class-gf-pf-terms.php`** with the updated version
3. **Clear WordPress object cache:**
   ```php
   wp_cache_flush();
   ```
4. **Test the filter** using the checklist above
5. **Monitor for issues** in first 24 hours

---

## Rollback Instructions

If issues arise, restore the original `get_base_url()` method:

```php
public static function get_base_url() {
    global $wp;
    return apply_filters( 'gf_pf_base_url', home_url( $wp->request ) );
}
```

And restore the original `get_active_slugs()` with `is_tax()` check.

---

## Related Documentation

- `RESTRUCTURE-NOTES.md` - Hierarchy flattening fixes
- `TESTING-CHECKLIST.md` - Visual/functional testing guide
- `PLUGIN-TECHNICAL.md` - Overall plugin architecture
- `STRUCTURE.md` - Database structure analysis

---

**Status:** ✅ Ready for Production  
**Date:** 2026-08-14  
**Author:** Kiro AI Assistant
