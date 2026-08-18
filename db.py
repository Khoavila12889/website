import mysql.connector
from mysql.connector import Error
import pandas as pd

# --- CẤU HÌNH DATABASE VPS ---
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}

PREFIX = "wp_"  # Đổi lại nếu prefix bảng của bạn khác (ví dụ: wpx_)

def export_products_to_excel():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        if not conn.is_connected():
            print("❌ Không thể kết nối Database VPS!")
            return
        
        cursor = conn.cursor(dictionary=True)
        print("🟢 Đã kết nối Database thành công! Đang truy vấn toàn bộ dữ liệu...")

        # 1. Lấy tất cả Sản phẩm công khai (publish)
        query_products = f"""
            SELECT ID AS product_id, post_title AS product_name, post_name AS product_slug
            FROM {PREFIX}posts
            WHERE post_type = 'product' AND post_status = 'publish'
            ORDER BY ID DESC
        """
        cursor.execute(query_products)
        products = cursor.fetchall()

        # 2. Lấy toàn bộ quan hệ Taxonomy (product_brand & product_cat)
        query_terms = f"""
            SELECT 
                tr.object_id AS product_id,
                t.term_id,
                t.name AS term_name,
                t.slug AS term_slug,
                tt.taxonomy
            FROM {PREFIX}term_relationships tr
            INNER JOIN {PREFIX}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {PREFIX}terms t ON tt.term_id = t.term_id
            WHERE tt.taxonomy IN ('product_brand', 'product_cat')
        """
        cursor.execute(query_terms)
        terms_rel = cursor.fetchall()

        # Gom nhóm taxonomy theo product_id
        product_brands = {}
        product_cats = {}

        for rel in terms_rel:
            pid = rel['product_id']
            tax = rel['taxonomy']
            
            if tax == 'product_brand':
                if pid not in product_brands:
                    product_brands[pid] = []
                product_brands[pid].append(rel)
            elif tax == 'product_cat':
                if pid not in product_cats:
                    product_cats[pid] = []
                product_cats[pid].append(rel)

        # 3. Tổng hợp bảng dữ liệu
        data_rows = []
        for p in products:
            pid = p['product_id']
            pname = p['product_name']
            pslug = p['product_slug']

            brands = product_brands.get(pid, [])
            cats = product_cats.get(pid, [])

            # Ghép chuỗi thông tin Thương hiệu
            brand_ids = ", ".join([str(b['term_id']) for b in brands]) if brands else ""
            brand_names = ", ".join([b['term_name'] for b in brands]) if brands else ""
            brand_slugs = ", ".join([b['term_slug'] for b in brands]) if brands else ""

            # Ghép chuỗi thông tin Danh mục
            cat_ids = ", ".join([str(c['term_id']) for c in cats]) if cats else ""
            cat_names = ", ".join([c['term_name'] for c in cats]) if cats else ""
            cat_slugs = ", ".join([c['term_slug'] for c in cats]) if cats else ""

            # Tự động đánh giá trạng thái dữ liệu để dễ lọc trong Excel
            status_note = []
            if not brands:
                status_note.append("⚠️ Thiếu Brand")
            if len(brands) > 1:
                status_note.append("⚠️ Dính >1 Brand")
            if not cats:
                status_note.append("⚠️ Thiếu Cat")
            if "san-pham" in cat_slugs:
                status_note.append("⚠️ Dính cat 'san-pham'")

            status_str = " | ".join(status_note) if status_note else "OK"

            data_rows.append({
                'ID Sản Phẩm': pid,
                'Tên Sản Phẩm': pname,
                'Slug Sản Phẩm': pslug,
                'Brand ID': brand_ids,
                'Tên Brand': brand_names,
                'Slug Brand': brand_slugs,
                'Cat ID': cat_ids,
                'Tên Danh Mục': cat_names,
                'Slug Danh Mục': cat_slugs,
                'Trạng Thái Dữ Liệu': status_str
            })

        # 4. Xuất ra file Excel
        df = pd.DataFrame(data_rows)
        output_file = "danh_sach_san_pham_full.xlsx"
        
        df.to_excel(output_file, index=False, engine='openpyxl')
        print(f"🎉 ĐÃ XUẤT THÀNH CÔNG {len(df)} SẢN PHẨM RA FILE `{output_file}`!")

        cursor.close()
        conn.close()

    except Error as e:
        print(f"❌ Lỗi Database: {e}")
    except Exception as e:
        print(f"❌ Lỗi hệ thống: {e}")

if __name__ == "__main__":
    export_products_to_excel()