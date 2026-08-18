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

def rebuild_category_tree():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if not connection.is_connected():
            print("❌ Không thể kết nối tới MySQL Database!")
            return

        cursor = connection.cursor(dictionary=True)
        print("🟢 Đã kết nối thành công! Bắt đầu khôi phục cấu trúc Menu...\n")

        # -------------------------------------------------------------
        # BƯỚC 1: Kiểm tra xem danh mục 'san-pham' còn tồn tại không
        # -------------------------------------------------------------
        cursor.execute("SELECT term_id FROM wp_terms WHERE slug = 'san-pham'")
        san_pham_term = cursor.fetchone()
        
        san_pham_id = None

        if san_pham_term:
            san_pham_id = san_pham_term['term_id']
            print(f"✅ Đã tìm thấy danh mục 'Sản Phẩm' (ID: {san_pham_id}).")
        else:
            # Tạo mới wp_terms
            print("⚠️ Không tìm thấy 'Sản Phẩm', đang tạo lại...")
            cursor.execute("INSERT INTO wp_terms (name, slug, term_group) VALUES ('Sản Phẩm', 'san-pham', 0)")
            san_pham_id = cursor.lastrowid
            
            # Tạo mới wp_term_taxonomy
            cursor.execute(f"""
                INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) 
                VALUES ({san_pham_id}, 'product_cat', '', 0, 0)
            """)
            print(f"✅ Đã tạo lại thành công danh mục 'Sản Phẩm' với ID: {san_pham_id}.")

        # Đảm bảo 'san-pham' nằm ở root (parent = 0)
        cursor.execute(f"UPDATE wp_term_taxonomy SET parent = 0 WHERE term_id = {san_pham_id} AND taxonomy = 'product_cat'")

        # -------------------------------------------------------------
        # BƯỚC 2: Gom tất cả danh mục (trừ Trang chủ) nhét vào 'Sản Phẩm'
        # -------------------------------------------------------------
        # Tìm term_id của 'chuyen-muc-trang-chu' để loại trừ
        cursor.execute("SELECT term_id FROM wp_terms WHERE slug = 'chuyen-muc-trang-chu'")
        trang_chu_term = cursor.fetchone()
        trang_chu_id = trang_chu_term['term_id'] if trang_chu_term else 0

        # Cập nhật parent cho tất cả danh mục còn lại
        query_nest_categories = f"""
            UPDATE wp_term_taxonomy tt
            JOIN wp_terms t ON tt.term_id = t.term_id
            SET tt.parent = {san_pham_id}
            WHERE tt.taxonomy = 'product_cat' 
              AND t.term_id != {san_pham_id} 
              AND t.term_id != {trang_chu_id}
        """
        cursor.execute(query_nest_categories)
        print(f" └─ 🌟 Đã gom {cursor.rowcount} danh mục con vào trong 'Sản Phẩm' (Tạo menu Dropdown)!\n")

        # -------------------------------------------------------------
        # BƯỚC 3: Dọn dẹp Cache
        # -------------------------------------------------------------
        cursor.execute("""
            DELETE FROM wp_options 
            WHERE option_name LIKE '%%_transient_wc_term_counts%%' 
               OR option_name LIKE '%%_transient_wc_featured_products%%'
        """)
        print(" └─ ⚡ Đã xóa cache WooCommerce để menu nhận diện cấu trúc mới.\n")

        connection.commit()
        print("==========================================================")
        print("🎉 ĐÃ KHÔI PHỤC CẤU TRÚC DANH MỤC THÀNH CÔNG!")
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
    rebuild_category_tree()