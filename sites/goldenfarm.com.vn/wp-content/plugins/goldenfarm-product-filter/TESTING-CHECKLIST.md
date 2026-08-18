# Testing Checklist for Filter Restructure

## Quick Test Guide

### 🎯 Expected Behavior After Changes

The filter should now display:
```
GOLDEN FARM (131)
  ├── Bơ (5)
  ├── Sinh Tố (22)
  ├── Sirô (39)
  └── ... (other product categories)

MAMA ROSA (52)
  ├── Bơ (2)
  ├── Sinh Tố (10)
  └── ... (other product categories)

VỊ Á (8)
  └── Trà Mật Ong (10)
```

### ❌ What Should NOT Appear

1. **No wrapper categories:**
   - ❌ "Sản Phẩm" (`san-pham`)
   - ❌ "Chuyên mục Trang Chủ" (`chuyen-muc-trang-chu`)

2. **No cross-brand empty categories:**
   - ❌ "Mama Rosa" under Golden Farm (0 products)
   - ❌ "Golden Farm" under Mama Rosa (0 products)
   - ❌ "Vị Á" under Golden Farm (0 products)

3. **No 0-count nodes:**
   - ❌ Any category showing "(0)" count badge
   - ❌ Empty branches with no products

### ✅ Visual Testing Steps

1. **Navigate to the shop/archive page** where the filter appears

2. **Check hierarchy structure:**
   - [ ] Each brand should be at level 0 (bold, green, uppercase)
   - [ ] Product categories should be directly under brands (level 1)
   - [ ] No "Sản Phẩm" or "Chuyên mục Trang Chủ" categories visible
   - [ ] Sub-categories should nest properly under parent categories

3. **Check product counts:**
   - [ ] All displayed categories show count > 0
   - [ ] No "(0)" badges visible
   - [ ] Counts match actual products for each brand

4. **Check cross-brand filtering:**
   - [ ] Under "Golden Farm", only Golden Farm categories appear
   - [ ] Under "Mama Rosa", only Mama Rosa categories appear
   - [ ] Under "Vị Á", only Vị Á categories appear

5. **Test interactive features:**
   - [ ] Clicking a brand checkbox filters products
   - [ ] Clicking a category checkbox filters by brand + category
   - [ ] "Xóa bộ lọc" (Reset) button clears all selections
   - [ ] Expand/collapse toggles work smoothly
   - [ ] Active states are highlighted correctly

6. **Test CSS styling:**
   - [ ] Brand names are bold, green, uppercase
   - [ ] Category names have proper indentation
   - [ ] Count badges display correctly (rounded, gray background)
   - [ ] Hover effects work (background color change, padding shift)
   - [ ] Toggle arrows rotate when expanding/collapsing

### 🔍 Database Verification (Optional)

If you want to verify the data structure:

```sql
-- Check which products belong to each brand
SELECT 
    bt.name AS brand_name,
    COUNT(DISTINCT p.ID) AS product_count
FROM wp_posts AS p
INNER JOIN wp_term_relationships AS br ON br.object_id = p.ID
INNER JOIN wp_term_taxonomy AS bt ON bt.term_taxonomy_id = br.term_taxonomy_id 
    AND bt.taxonomy = 'product_brand'
WHERE p.post_type = 'product' 
    AND p.post_status = 'publish'
GROUP BY bt.term_id;

-- Check brand + category combinations
SELECT 
    bt.name AS brand_name,
    ct.name AS category_name,
    ct.slug AS category_slug,
    COUNT(DISTINCT p.ID) AS product_count
FROM wp_posts AS p
INNER JOIN wp_term_relationships AS br ON br.object_id = p.ID
INNER JOIN wp_term_taxonomy AS bt ON bt.term_taxonomy_id = br.term_taxonomy_id 
    AND bt.taxonomy = 'product_brand'
INNER JOIN wp_term_relationships AS cr ON cr.object_id = p.ID
INNER JOIN wp_term_taxonomy AS ct ON ct.term_taxonomy_id = cr.term_taxonomy_id 
    AND ct.taxonomy = 'product_cat'
WHERE p.post_type = 'product' 
    AND p.post_status = 'publish'
    AND ct.slug NOT IN ('san-pham', 'chuyen-muc-trang-chu')
GROUP BY bt.term_id, ct.term_id
ORDER BY bt.name, ct.name;
```

### 🔧 Cache Management

If the filter still shows old data after deployment:

**Option 1: Clear WordPress Object Cache**
```php
// Add to functions.php temporarily, visit site, then remove
wp_cache_flush();
```

**Option 2: Clear via Plugin Code**
```php
// Add to functions.php temporarily, visit site, then remove
GF_PF_Terms::invalidate_cache( 'product_cat' );
GF_PF_Terms::invalidate_cache( 'product_brand' );
```

**Option 3: Use Cache Plugin**
- If using W3 Total Cache, WP Super Cache, etc.
- Clear object cache from plugin settings

### 🐛 Troubleshooting

**Issue: Wrapper categories still appear**
- Solution: Clear WordPress object cache
- Verify: Check `$excluded_slugs` array in `build_cat_nodes()`

**Issue: Empty categories still visible**
- Solution: Clear cache and check `has_valid_descendants()` logic
- Verify: Inspect database queries to confirm 0-count categories exist

**Issue: CSS styling broken**
- Solution: Check browser console for CSS errors
- Verify: Ensure `gf-pf.css` is loaded and not overridden by theme

**Issue: Filter not working at all**
- Solution: Check PHP error logs for fatal errors
- Verify: Ensure both files were updated correctly

### 📝 Browser Testing

Test in multiple browsers:
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

### 📱 Responsive Testing

Test at different screen sizes:
- [ ] Desktop (1920px+)
- [ ] Tablet (768px - 1024px)
- [ ] Mobile (320px - 767px)

---

## Success Criteria

All tests pass when:
1. ✅ No wrapper categories visible
2. ✅ No 0-count categories visible
3. ✅ Clean Brand → Product Category hierarchy
4. ✅ All interactive features work
5. ✅ CSS styling intact
6. ✅ Product counts accurate

---

## Files Changed

Review these files were deployed correctly:
- ✅ `includes/class-gf-pf-terms.php` (updated `build_cat_nodes()`, added `has_valid_descendants()`)
- ✅ `includes/class-gf-pf-renderer.php` (updated `render_cat_level()` with safeguards)

---

## Need Help?

Refer to:
- `RESTRUCTURE-NOTES.md` - Detailed technical documentation
- `STRUCTURE.md` - Original database structure analysis
- `PLUGIN-TECHNICAL.md` - Plugin architecture overview
