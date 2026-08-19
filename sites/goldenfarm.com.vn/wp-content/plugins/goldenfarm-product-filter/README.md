# GoldenFarm Product Filter

Plugin lọc sản phẩm **lightweight, native WooCommerce** thay thế YITH WooCommerce AJAX Product Filter — thân thiện SEO, URL cacheable, không AJAX, tốc độ cao.

**Version:** 1.3.1 · **Requires:** WooCommerce, WordPress 6.0+, PHP 7.4+ · **License:** GPLv2+

---

## Tính năng

- Lọc theo **2 taxonomy**: `product_brand` (Thương hiệu) và `product_cat` (Danh mục).
- Render **một block duy nhất** (THƯƠNG HIỆU) theo cây phân cấp: Brand → Nhóm ngành hàng → Loại sản phẩm (đệ quy nhiều cấp).
- Cây brand → category dựng từ **quan hệ sản phẩm thực tế trong DB** (1 câu SQL aggregate, không N+1), **prune nhánh rỗng**, hiển thị badge số lượng sản phẩm.
- URL **cacheable** (`?product_cat=slug1,slug2`) thay vì `?yith_wcan=...` — full-page navigation, không AJAX, không dependency ngoài (không jQuery UI, ion.range-slider, selectWoo).
- Tự invalidate cache khi taxonomy thay đổi (`created/edited/deleted_product_cat`, `set_object_terms`).
- Hỗ trợ shortcode `[goldenfarm_product_filter]` và hàm template `gf_pf_render_filters()`.

## Cài đặt

1. Copy thư mục vào `wp-content/plugins/goldenfarm-product-filter/`.
2. Kích hoạt plugin trong WordPress Admin.
3. Gọi filter trong theme (xem mục *Sử dụng*).

## Sử dụng

### Shortcode

```php
echo do_shortcode('[goldenfarm_product_filter title="Thương hiệu"]');
```

### Template function

```php
if ( function_exists( 'gf_pf_render_filters' ) ) {
    gf_pf_render_filters( array( 'title' => 'Danh mục sản phẩm' ) );
}
```

### Thay thế YITH

```diff
- echo do_shortcode('[yith_wcan_filters slug="draft-preset"]');
+ echo do_shortcode('[goldenfarm_product_filter title="Thương hiệu"]');
```

HTML markup giữ nguyên class `yith-wcan-*` để CSS theme vẫn hoạt động. Tắt/uninstall plugin YITH sau khi chuyển.

## Tài liệu liên quan

| File | Nội dung |
|------|----------|
| `PLUGIN-TECHNICAL.md` | Kiến trúc chi tiết: classes, hooks/filters, cache, performance, migration, troubleshooting |
| `STRUCTURE.md` | Cây phân cấp Brand → Category thực tế trong DB |
| `URL-FIX-NOTES.md` | Lịch sử sửa lỗi URL của filter |
| `URL-TEST-SCENARIOS.md` | Các kịch bản test URL |
| `RESTRUCTURE-NOTES.md` | Ghi chú tái cấu trúc danh mục |
| `TESTING-CHECKLIST.md` | Checklist kiểm thử |
| `CSS-DARK-MODE-SUMMARY.md`, `DARK-MODE-NOTES.md` | Ghi chú dark mode |

## Cấu trúc thư mục

```
goldenfarm-product-filter/
├── goldenfarm-product-filter.php    # Main plugin controller
├── includes/
│   ├── class-gf-pf-terms.php        # Term tree + URL building (DB-driven)
│   ├── class-gf-pf-renderer.php     # HTML renderer (recursive)
│   ├── class-gf-pf-assets.php       # CSS/JS enqueuer
│   └── class-gf-pf-admin.php        # Admin settings page
├── assets/
│   ├── css/gf-pf.css                # Base styles + hierarchy levels
│   └── js/gf-pf.js                  # Toggle handling (mọi cấp)
└── *.py                             # CLI tools (filter.py, clean_db.py, ...)
```

## Hooks & Filters chính

| Filter | Mô tả | Mặc định |
|--------|-------|----------|
| `gf_pf_taxonomy` | Taxonomy danh mục | `product_cat` |
| `gf_pf_brand_taxonomy` | Taxonomy thương hiệu | `product_brand` |
| `gf_pf_orderby` | Sắp xếp term | `name` |
| `gf_pf_order` | Chiều sắp xếp | `ASC` |
| `gf_pf_base_url` | Base URL của link lọc | `home_url( $wp->request )` |
| `gf_pf_enqueue` | Force enqueue assets | `false` |

## Cache

- Cache key: `gf_pf_brand_tree_v2` (group `gf_pf`, TTL `DAY_IN_SECONDS`), versioned qua `gf_pf_term_version_{taxonomy}`.
- Tự invalidate khi term thay đổi; khi deploy version mới flush thủ công `gf_pf_*` nếu cần.

## Changelog

- **v1.3.1** — Chặn load selectWoo/select2 khi không cần.
- **v1.3.0** — Cây brand → category DB-driven, badge số lượng, renderer đệ quy, nút "Xóa bộ lọc".
- **v1.2.1** — Single THƯƠNG HIỆU block, 3-level tree layout, refactor `product_cat`.

---

**Author:** GoldenFarm Dev