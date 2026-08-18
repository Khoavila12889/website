import mysql.connector
from mysql.connector import Error

# --- THÔNG TIN KẾT NỐI DATABASE ---
DB_CONFIG = {
    'host': '10.0.0.9',  # Đổi IP VPS/Docker của bạn
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}

# 1. Danh sách các slug danh mục rác/trung gian cần XÓA BỎ khỏi product_cat
# Đã giữ lại 'chuyen-muc-trang-chu' theo yêu cầu của bạn
JUNKS_TO_DELETE = [
    'san-pham',
    'san-pham-khac'
]

# 2. Danh sách các slug trùng với Thương hiệu cần XÓA BỎ khỏi product_cat
# (Vì Thương hiệu đã được quản lý chuẩn bên taxonomy product_brand)
BRANDS_IN_CAT_TO_DELETE = [
    'golden-farm',
    'mama-rosa',
    'vi-a'
]

def clean_and_optimize_taxonomy():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if not connection.is_connected():
            print("❌ Không thể kết nối tới MySQL Database!")
            return

        cursor = connection.cursor(dictionary=True)
        print("🟢 Đã kết nối thành công tới MySQL Database!\n")

        all_slugs_to_remove = JUNKS_TO_DELETE + BRANDS_IN_CAT_TO_DELETE
        format_strings = ','.join(['%s'] * len(all_slugs_to_remove))

        # -------------------------------------------------------------
        # BƯỚC 1: Lấy thông tin các Term cần xóa trong product_cat
        # -------------------------------------------------------------
        query_find_terms = f"""
            SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id
            FROM wp_terms t
            INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat' AND t.slug IN ({format_strings})
        """
        cursor.execute(query_find_terms, tuple(all_slugs_to_remove))
        terms_to_delete = cursor.fetchall()

        if terms_to_delete:
            print(f"🔍 Tìm thấy {len(terms_to_delete)} danh mục rác/trùng tên thương hiệu cần xóa:")
            term_ids = []
            tt_ids = []
            for t in terms_to_delete:
                print(f"   - [{t['term_id']}] {t['name']} (slug: {t['slug']})")
                term_ids.append(t['term_id'])
                tt_ids.append(t['term_taxonomy_id'])

            # Xóa liên kết sản phẩm với các danh mục này trong wp_term_relationships
            format_tt_ids = ','.join(['%s'] * len(tt_ids))
            query_del_rel = f"DELETE FROM wp_term_relationships WHERE term_taxonomy_id IN ({format_tt_ids})"
            cursor.execute(query_del_rel, tuple(tt_ids))
            print(f"  └─ ✂️ Đã xóa liên kết sản phẩm thuộc các danh mục này.")

            # Xóa trong wp_term_taxonomy
            query_del_tt = f"DELETE FROM wp_term_taxonomy WHERE term_taxonomy_id IN ({format_tt_ids})"
            cursor.execute(query_del_tt, tuple(tt_ids))

            # Xóa trong wp_terms
            format_term_ids = ','.join(['%s'] * len(term_ids))
            query_del_terms = f"DELETE FROM wp_terms WHERE term_id IN ({format_term_ids})"
            cursor.execute(query_del_terms, tuple(term_ids))

            print("  └─ 🗑️ Đã xóa sạch dữ liệu danh mục rác khỏi wp_terms & wp_term_taxonomy.\n")
        else:
            print("✅ Không tìm thấy danh mục rác nào cần xóa trong product_cat.\n")

        # -------------------------------------------------------------
        # BƯỚC 2: Đưa tất cả danh mục product_cat còn lại về CẤP ROOT (parent = 0)
        # -------------------------------------------------------------
        print("🛠️ Đang chuẩn hóa lại cấp danh mục (Đưa tất cả Loại sản phẩm về Parent = 0)...")
        query_flatten_cats = """
            UPDATE wp_term_taxonomy 
            SET parent = 0 
            WHERE taxonomy = 'product_cat' AND parent != 0
        """
        cursor.execute(query_flatten_cats)
        print(f"  └─ 🌟 Đã cập nhật {cursor.rowcount} danh mục con về Cấp Root phẳng!\n")

        # -------------------------------------------------------------
        # BƯỚC 3: Dọn dẹp cache / transient của WooCommerce trong DB
        # -------------------------------------------------------------
        print("🧹 Đang dọn dẹp Cache Transient WooCommerce & WordPress...")
        query_clear_transients = """
            DELETE FROM wp_options 
            WHERE option_name LIKE '%%_transient_wc_term_counts%%' 
               OR option_name LIKE '%%_transient_wc_featured_products%%'
               OR option_name LIKE '%%_transient_gf_pf_%%'
        """
        cursor.execute(query_clear_transients)
        print("  └─ ⚡ Đã xóa sạch Cache Transient trong wp_options.\n")

        # Commit thay đổi
        connection.commit()
        print("==========================================================")
        print("🎉 TỰ ĐỘNG CHUẨN HÓA & DỌN DẸP DATABASE THÀNH CÔNG!")
        print("==========================================================")

    except Error as e:
        print(f"❌ Lỗi MySQL: {e}")
        if 'connection' in locals() and connection.is_connected():
            connection.rollback()
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    clean_and_optimize_taxonomy()