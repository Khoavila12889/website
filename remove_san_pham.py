import mysql.connector
from mysql.connector import Error

# --- CẤU HÌNH DATABASE VPS ---
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}
PREFIX = "wp_"

def remove_san_pham_relationships():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        if not conn.is_connected():
            print("❌ Không thể kết nối Database!")
            return

        cursor = conn.cursor(dictionary=True)
        print("🟢 Đã kết nối Database VPS. Đang tiến hành dọn dẹp liên kết 'san-pham'...\n")

        # 1. Tìm term_taxonomy_id của danh mục slug 'san-pham'
        query_san_pham = f"""
            SELECT tt.term_taxonomy_id
            FROM {PREFIX}term_taxonomy tt
            INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'product_cat' AND t.slug = 'san-pham'
        """
        cursor.execute(query_san_pham)
        res = cursor.fetchone()

        if not res:
            print("❌ Không tìm thấy danh mục 'san-pham' trong Database!")
            return

        sp_tt_id = res['term_taxonomy_id']

        # 2. Lấy danh sách sản phẩm vừa chứa 'san-pham' vừa có danh mục con khác
        query_find = f"""
            SELECT tr.object_id AS product_id, p.post_title
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}posts p ON tr.object_id = p.ID
            WHERE tr.term_taxonomy_id = %s
              AND p.post_type = 'product'
              AND EXISTS (
                  SELECT 1 FROM {PREFIX}term_relationships tr_other
                  INNER JOIN {PREFIX}term_taxonomy tt_other ON tr_other.term_taxonomy_id = tt_other.term_taxonomy_id
                  WHERE tr_other.object_id = tr.object_id
                    AND tr_other.term_taxonomy_id != %s
                    AND tt_other.taxonomy = 'product_cat'
              )
        """
        cursor.execute(query_find, (sp_tt_id, sp_tt_id))
        affected_products = cursor.fetchall()

        if not affected_products:
            print("✅ Không có sản phẩm nào cần gỡ danh mục 'san-pham'. Dữ liệu đã sạch 100%!")
            return

        print(f"🔍 Tìm thấy {len(affected_products)} sản phẩm dính danh mục 'san-pham' dư thừa:")
        product_ids = []
        for p in affected_products:
            product_ids.append(p['product_id'])
            print(f"  - ID: {p['product_id']:<6} | Tên: {p['post_title']}")

        # 3. Xóa liên kết trong bảng wp_term_relationships
        format_strings = ','.join(['%s'] * len(product_ids))
        delete_sql = f"""
            DELETE FROM {PREFIX}term_relationships
            WHERE term_taxonomy_id = %s AND object_id IN ({format_strings})
        """
        cursor.execute(delete_sql, [sp_tt_id] + product_ids)
        deleted_count = cursor.rowcount

        # 4. Cập nhật lại count số lượng sản phẩm của danh mục 'san-pham'
        recount_sql = f"""
            UPDATE {PREFIX}term_taxonomy
            SET count = (
                SELECT COUNT(DISTINCT object_id) 
                FROM {PREFIX}term_relationships 
                WHERE term_taxonomy_id = %s
            )
            WHERE term_taxonomy_id = %s
        """
        cursor.execute(recount_sql, (sp_tt_id, sp_tt_id))

        conn.commit()
        print(f"\n🎉 ĐÃ XÓA THÀNH CÔNG {deleted_count} LIÊN KẾT DƯ THỪA TRONG DATABASE!")
        print("⚡ Database đã sạch, website chạy tối ưu trực tiếp từ SQL mà không cần xử lý loại trừ bằng PHP.")

        cursor.close()
        conn.close()

    except Error as e:
        print(f"❌ Lỗi MySQL: {e}")

if __name__ == "__main__":
    remove_san_pham_relationships()