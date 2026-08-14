import mysql.connector
from mysql.connector import Error

# --- THÔNG TIN KẾT NỐI DATABASE ---
DB_CONFIG = {
    'host': '10.0.0.9', # IP VPS / Docker của bạn
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}

def export_multi_taxonomy_to_md():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if not connection.is_connected():
            return

        cursor = connection.cursor(dictionary=True)
        print("🟢 Đã kết nối MySQL thành công!")

        # 1. Lấy tất cả Brands (product_brand)
        query_brands = """
            SELECT t.term_id, t.name, t.slug, tt.count
            FROM wp_terms t
            INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_brand'
            ORDER BY t.name ASC
        """
        cursor.execute(query_brands)
        brands = cursor.fetchall()

        # 2. Lấy toàn bộ danh mục product_cat để tạo map phân cấp Cha - Con
        query_cats = """
            SELECT t.term_id, t.name, t.slug, tt.parent
            FROM wp_terms t
            INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat'
        """
        cursor.execute(query_cats)
        all_cats = cursor.fetchall()

        cat_by_id = {c['term_id']: c for c in all_cats}
        cats_parent_map = {}
        for c in all_cats:
            parent_id = c['parent']
            if parent_id not in cats_parent_map:
                cats_parent_map[parent_id] = []
            cats_parent_map[parent_id].append(c)

        # Bắt đầu dựng Markdown content
        md_content = "# 🌳 TREE STRUCTURE: BRAND (product_brand) ➔ CATEGORIES (product_cat)\n\n"
        md_content += "> **Báo cáo phân tích mối quan hệ giữa Thương hiệu và Danh mục sản phẩm thực tế trong Database.**\n\n"
        md_content += "---\n\n"

        for brand in brands:
            brand_id = brand['term_id']
            brand_name = brand['name']
            brand_slug = brand['slug']
            brand_count = brand['count']

            md_content += f"## 🏷️ THƯƠNG HIỆU: **{brand_name.upper()}** (`{brand_slug}`) — *{brand_count} sản phẩm*\n\n"

            # 3. Lấy tất cả ID sản phẩm thuộc Brand này
            query_products = """
                SELECT tr.object_id
                FROM wp_term_relationships tr
                INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.term_id = %s AND tt.taxonomy = 'product_brand'
            """
            cursor.execute(query_products, (brand_id,))
            product_ids = [p['object_id'] for p in cursor.fetchall()]

            if not product_ids:
                md_content += "  * ⚠️ *Không có sản phẩm nào được gán thương hiệu này.*\n\n"
                continue

            # 4. Lấy tất cả product_cat được gán cho danh sách sản phẩm trên
            format_strings = ','.join(['%s'] * len(product_ids))
            query_brand_cats = f"""
                SELECT DISTINCT tt.term_id, COUNT(DISTINCT tr.object_id) as sp_count
                FROM wp_term_relationships tr
                INNER JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id IN ({format_strings}) AND tt.taxonomy = 'product_cat'
                GROUP BY tt.term_id
            """
            cursor.execute(query_brand_cats, tuple(product_ids))
            cat_counts = {row['term_id']: row['sp_count'] for row in cursor.fetchall()}

            # 5. Lọc và xây dựng cây danh mục liên quan tới Brand này
            active_cat_ids = set(cat_counts.keys())

            # Xây dựng cây danh mục thuộc brand
            def render_cat_tree(parent_id, depth=0):
                tree_text = ""
                children = cats_parent_map.get(parent_id, [])
                
                for cat in children:
                    c_id = cat['term_id']
                    has_active_child = any(c_id == acid or c_id in cats_parent_map for acid in active_cat_ids)
                    
                    if c_id in active_cat_ids or has_active_child:
                        sp_count = cat_counts.get(c_id, 0)
                        indent = "    " * depth
                        
                        if depth == 0:
                            tree_text += f"{indent}* 📂 **[Group Cấp 1] {cat['name']}** (`{cat['slug']}`)"
                        else:
                            tree_text += f"{indent}* 📦 **[Loại Cấp 2] {cat['name']}** (`{cat['slug']}`) ➔ **{sp_count} SP**"
                        
                        tree_text += "\n"
                        # Đệ quy xuống cấp con
                        tree_text += render_cat_tree(c_id, depth + 1)
                        
                return tree_text

            rendered_tree = render_cat_tree(0)

            if rendered_tree.strip():
                md_content += rendered_tree + "\n"
            else:
                md_content += "  * ⚠️ *Sản phẩm của thương hiệu này chưa được gán vào Danh mục product_cat nào.*\n\n"

            md_content += "---\n\n"

        # Xuất ra file STRUCTURE.md
        output_file = "STRUCTURE.md"
        with open(output_file, "w", encoding="utf-8") as f:
            f.write(md_content)

        print(f"🎉 ĐÃ XUẤT THÀNH CÔNG RA FILE `{output_file}`!")

    except Error as e:
        print(f"❌ Lỗi MySQL: {e}")
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    export_multi_taxonomy_to_md()