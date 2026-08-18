# URL Fix Testing Scenarios

Quick reference for testing the URL generation fixes.

---

## 🧪 Test Scenario 1: Brand Filter from Category Archive

**The Original Bug - Now Fixed**

### Steps:
1. Navigate to: `/danh-muc-san-pham/san-pham/mama-rosa/`
2. Locate the filter sidebar
3. Click on "**Vị Á**" brand filter

### ✅ Expected Result:
- URL redirects to: `/shop/?product_brand=vi-a`
- Page displays only "Vị Á" brand products
- Filter shows "Vị Á" as active (highlighted)
- Product count should be 8-10 products

### ❌ Before Fix (Bug):
- URL was: `/danh-muc-san-pham/san-pham/mama-rosa/?product_brand=vi-a`
- Result: "No products found" (0 results)

---

## 🧪 Test Scenario 2: Multiple Filters from Archive

### Steps:
1. Navigate to: `/thuong-hieu/golden-farm/` (brand archive)
2. Click on "**Sinh Tố**" category filter
3. Then click on "**Mama Rosa**" brand filter

### ✅ Expected Result:
- After step 2: `/shop/?product_cat=sinh-to-golden-farm`
- After step 3: `/shop/?product_brand=mama-rosa&product_cat=sinh-to-golden-farm`
- BUT since Mama Rosa doesn't have products in Golden Farm Sinh Tố category, this should show "No products found" (expected behavior)

### 🔄 Proper Flow:
1. Click "Mama Rosa" first
2. Then click "Sinh Tố" under Mama Rosa
3. URL: `/shop/?product_brand=mama-rosa&product_cat=sinh-to-mama-rosa`
4. Shows Mama Rosa Sinh Tố products (~10 products)

---

## 🧪 Test Scenario 3: Filter Preservation

**Testing cross-taxonomy preservation**

### Steps:
1. Navigate to: `/shop/`
2. Click "**Golden Farm**" brand
3. Verify URL: `/shop/?product_brand=golden-farm`
4. Click "**Bơ**" category (under Golden Farm)
5. Verify URL: `/shop/?product_brand=golden-farm&product_cat=bo-golden-farm`

### ✅ Expected Result:
- Both filters remain active
- Product count shows only "Golden Farm Bơ" products (5 products)
- Both filters are highlighted in the sidebar

### ❌ Before Fix:
- Step 4 would remove the brand filter
- URL would be: `/shop/?product_cat=bo-golden-farm` (brand lost)

---

## 🧪 Test Scenario 4: Reset from Archive Page

### Steps:
1. Navigate to: `/danh-muc-san-pham/mama-rosa/`
2. Click "**Golden Farm**" brand filter
3. URL should be: `/shop/?product_brand=golden-farm`
4. Click "**Xóa bộ lọc**" (Reset button in filter header)

### ✅ Expected Result:
- URL redirects to: `/shop/` (clean, no params)
- All products are displayed (~191 total products)
- No filters are active/highlighted

---

## 🧪 Test Scenario 5: Preserve Sorting

### Steps:
1. Navigate to: `/shop/?orderby=price`
2. Products should be sorted by price (low to high)
3. Click "**Mama Rosa**" brand filter

### ✅ Expected Result:
- URL: `/shop/?product_brand=mama-rosa&orderby=price`
- Products filtered by Mama Rosa brand
- **AND** still sorted by price
- Sort dropdown shows "Price: low to high" selected

### ❌ If orderby is lost:
- Products would revert to default sorting (menu order or date)

---

## 🧪 Test Scenario 6: Search + Filter

### Steps:
1. Use the shop search: `/shop/?s=sinh+to`
2. Search results show all "Sinh Tố" products across brands
3. Click "**Mama Rosa**" brand filter

### ✅ Expected Result:
- URL: `/shop/?product_brand=mama-rosa&s=sinh+to`
- Shows only Mama Rosa products matching "sinh to"
- Search term remains in search box
- Expected products: ~10 (Mama Rosa Sinh Tố products)

---

## 🧪 Test Scenario 7: Toggle Filter On/Off

### Steps:
1. Navigate to: `/shop/`
2. Click "**Golden Farm**" brand
3. URL: `/shop/?product_brand=golden-farm`
4. Click "**Golden Farm**" again (toggle off)

### ✅ Expected Result:
- URL returns to: `/shop/` (clean)
- All products displayed again (~191 products)
- Golden Farm filter no longer highlighted

---

## 🧪 Test Scenario 8: Multiple Categories

### Steps:
1. Navigate to: `/shop/`
2. Click "**Golden Farm**" brand
3. Click "**Bơ**" category
4. URL: `/shop/?product_brand=golden-farm&product_cat=bo-golden-farm`
5. Click "**Sinh Tố**" category (multi-select)

### ✅ Expected Result:
- URL: `/shop/?product_brand=golden-farm&product_cat=bo-golden-farm,sinh-to-golden-farm`
- Shows products from BOTH Bơ AND Sinh Tố categories (5 + 22 = 27 products)
- Both categories highlighted in filter

### Note:
- If multiple categories are NOT supported in your theme, this may show 0 products
- Check if WooCommerce query handles comma-separated category slugs

---

## 🧪 Test Scenario 9: Direct URL Access

**Test that filter state is properly read from URL**

### Steps:
1. Manually type in browser: `/shop/?product_brand=vi-a&product_cat=tra-mat-ong-vi-a`
2. Press Enter

### ✅ Expected Result:
- Page loads with filters active
- Shows only "Vị Á Trà Mật Ong" products (~10 products)
- Filter sidebar shows:
  - "Vị Á" brand highlighted
  - "Trà Mật Ong" category highlighted under Vị Á

---

## 🧪 Test Scenario 10: Invalid/Deleted Term

**Edge case testing**

### Steps:
1. Manually type in browser: `/shop/?product_brand=nonexistent-brand`
2. Press Enter

### ✅ Expected Result:
- Page loads normally (no PHP errors)
- Shows "No products found" (0 results)
- Filter sidebar shows no active filters

---

## 📊 Success Metrics

After testing all scenarios, verify:

- ✅ All URLs start with `/shop/` (not category archives)
- ✅ No "No products found" errors when valid filters are applied
- ✅ Both taxonomies preserved when using combined filters
- ✅ Reset button always returns to clean `/shop/` URL
- ✅ Sorting and search parameters are preserved
- ✅ Filter toggle (on/off) works correctly
- ✅ No JavaScript console errors
- ✅ No PHP errors in debug.log

---

## 🐛 Common Issues to Check

### Issue: Filter doesn't highlight when active
**Check:** Is `get_active_slugs()` reading from query params?

### Issue: Products still showing from wrong brand
**Check:** Is WooCommerce query parsing the `product_brand` parameter?

### Issue: URL has weird characters (%2C instead of comma)
**Check:** This is normal URL encoding - browser will decode it

### Issue: Multiple selections not working
**Check:** Theme may not support comma-separated taxonomy values in WooCommerce query

### Issue: Reset button doesn't work
**Check:** JavaScript might be intercepting the click - check console

---

## 🔍 Browser DevTools Inspection

### Check Generated HTML:

```html
<!-- Brand filter link -->
<a href="/shop/?product_brand=golden-farm" class="term-label">Golden Farm</a>

<!-- Category filter link (with brand preserved) -->
<a href="/shop/?product_brand=golden-farm&product_cat=bo-golden-farm" class="term-label">Bơ</a>

<!-- Reset link -->
<a href="/shop/" class="gf-pf-reset">Xóa bộ lọc</a>
```

### Check Network Tab:

When clicking a filter:
1. Should navigate to `/shop/?product_brand=...`
2. Status: 200 OK
3. Content-Type: text/html
4. No 404 errors in console

---

## 📝 Testing Checklist

Copy this into a testing document:

```
[Test Date: ___________] [Tester: ___________]

✅ Test 1: Brand filter from category archive
   Result: _________________ Issues: _________________

✅ Test 2: Multiple filters from archive
   Result: _________________ Issues: _________________

✅ Test 3: Filter preservation (brand + category)
   Result: _________________ Issues: _________________

✅ Test 4: Reset from archive page
   Result: _________________ Issues: _________________

✅ Test 5: Preserve sorting (orderby)
   Result: _________________ Issues: _________________

✅ Test 6: Search + filter
   Result: _________________ Issues: _________________

✅ Test 7: Toggle filter on/off
   Result: _________________ Issues: _________________

✅ Test 8: Multiple categories (if supported)
   Result: _________________ Issues: _________________

✅ Test 9: Direct URL access
   Result: _________________ Issues: _________________

✅ Test 10: Invalid/deleted term
   Result: _________________ Issues: _________________

Overall Status: PASS / FAIL
Notes: _________________________________________________
______________________________________________________
______________________________________________________
```

---

**Document Status:** ✅ Ready for QA Testing  
**Last Updated:** 2026-08-14  
**Related Files:** `URL-FIX-NOTES.md`, `TESTING-CHECKLIST.md`
