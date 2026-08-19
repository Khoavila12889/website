# GoldenFarm Catalog Only + Perf (MU-Plugin)

MU-plugin (Must-Use) biến WooCommerce thành **catalog-only engine** và tối ưu tốc độ front-end. Luôn chạy ngầm, không bị theme hay plugin khác ghi đè.

**File:** `wp-content/mu-plugins/catalog-only-perf.php` · **Version:** 1.0.0 · **License:** GPLv2 or later

> Vì là mu-plugin nên **không cần kích hoạt** — chỉ cần tồn tại trong `wp-content/mu-plugins/` là tự động load. Xóa file để tắt.

---

## Tính năng

### 1. Catalog Only (chỉ xem sản phẩm, không mua hàng)

| Hành động | Xử lý |
|-----------|-------|
| `/cart/`, `/checkout/`, `/my-account/`, mọi endpoint | Redirect 301 về trang chủ |
| Thêm giỏ hàng (`?add-to-cart=`, AJAX) | Chặn + redirect 302, `is_purchasable = false` |
| Nút thêm giỏ (loop + single + variable) | Gỡ hook render + CSS ẩn |
| Coupon, tính phí vận chuyển, thanh toán | Tắt |
| Đăng ký tài khoản, review, comments trên sản phẩm | Tắt |
| REST `/wc/store/` (giỏ, checkout, payment) | Gỡ endpoint + chặn guest |

### 2. Tối ưu tốc độ (front-end)

- Gỡ JS/CSS không cần: `wc-cart-fragments`, `js-cookie`, `wc-add-to-cart`, `wc-single-product`, `wc-checkout`, `jquery-blockui`, `selectWoo/select2`, `wc-blocks.css`, `woocommerce-layout/smallscreen`, `wp-block-library`, `dashicons`, zoom/flexslider/photoswipe...
- Tắt emoji, oEmbed, feeds, XML-RPC, pingback, heartbeat (cho khách).
- Xóa `<meta generator>`, RSD, WLW manifest khỏi `<head>`.

## Kết quả đo (trang chủ, trước → sau)

| | Trước | Sau |
|---|---|---|
| CSS files | 9 | 6 |
| JS files | 16 | 14 |
| HTML | ~114 KB | ~104 KB |

Trang sản phẩm / danh mục còn giảm mạnh hơn nhờ bỏ `wc-single-product`, zoom, photoswipe...

## Lưu ý khi deploy

- Sau khi thay đổi file, **purge LiteSpeed Cache** để áp dụng ngay.
- Xóa nút "Add to cart" hoàn toàn có thể dùng filter `woocommerce_is_purchasable` (đã có sẵn) hoặc bật plugin như `woocommerce-call-for-price` nếu muốn hiện "Gọi giá".
- Các handle asset được gỡ ở **nhiều tầng** (dequeue + `wp_default_*` + strip dependency + filter `print_*_array`) để chống bị enqueue lại sau.