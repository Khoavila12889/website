import mysql.connector
from mysql.connector import Error

# --- THÔNG TIN KẾT NỐI DATABASE ---
DB_CONFIG = {
    'host': '127.0.0.1', # IP VPS / Docker
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}

# Prefix bảng WordPress (thay đổi nếu cần)
PREFIX = "wp_"

def export_multi_taxonomy_to_md():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if not connection.is_connected():
            return

        cursor = connection.cursor(dictionary=True)
        print("🟢 Đã kết nối MySQL thành công!")

        # 1. Lấy tất cả Brands (product_brand)
        query_brands = f"""
            SELECT t.term_id, t.name, t.slug, tt.count
            FROM {PREFIX}terms t
            INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_brand'
            ORDER BY t.name ASC
        """
        cursor.execute(query_brands)
        brands = cursor.fetchall()

        # 2. Lấy toàn bộ danh mục product_cat để tạo map phân cấp Cha - Con
        query_cats = f"""
            SELECT t.term_id, t.name, t.slug, tt.parent
            FROM {PREFIX}terms t
            INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'product_cat'
            ORDER BY t.name ASC
        """
        cursor.execute(query_cats)
        all_cats = cursor.fetchall()

        # Tạo dictionary phân cấp danh mục
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

        # Query tối ưu JOIN: Lấy trực tiếp danh mục & số lượng sản phẩm của 1 Brand
        query_brand_cats = f"""
            SELECT tt_cat.term_id, COUNT(DISTINCT tr_brand.object_id) as sp_count
            FROM {PREFIX}term_relationships tr_brand
            INNER JOIN {PREFIX}term_taxonomy tt_brand ON tr_brand.term_taxonomy_id = tt_brand.term_taxonomy_id
            INNER JOIN {PREFIX}term_relationships tr_cat ON tr_brand.object_id = tr_cat.object_id
            INNER JOIN {PREFIX}term_taxonomy tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
            WHERE tt_brand.term_id = %s 
              AND tt_brand.taxonomy = 'product_brand'
              AND tt_cat.taxonomy = 'product_cat'
            GROUP BY tt_cat.term_id
        """

        for brand in brands:
            brand_id = brand['term_id']
            brand_name = brand['name']
            brand_slug = brand['slug']
            brand_count = brand['count']

            md_content += f"## 🏷️ THƯƠNG HIỆU: **{brand_name.upper()}** (`{brand_slug}`) — *{brand_count} sản phẩm*\n\n"

            # Thực thi query JOIN lấy danh mục có chứa SP của Brand này
            cursor.execute(query_brand_cats, (brand_id,))
            cat_counts = {row['term_id']: row['sp_count'] for row in cursor.fetchall()}

            active_cat_ids = set(cat_counts.keys())

            if not active_cat_ids:
                md_content += "  * ⚠️ *Không có sản phẩm nào thuộc thương hiệu này được gán Danh mục (product_cat).*\n\n"
                md_content += "---\n\n"
                continue

            # Kiểm tra đệ quy xem một node hoặc bất kỳ node con/cháu nào của nó có nằm trong active_cat_ids hay không
            def is_node_or_descendant_active(cat_id):
                if cat_id in active_cat_ids:
                    return True
                for child in cats_parent_map.get(cat_id, []):
                    if is_node_or_descendant_active(child['term_id']):
                        return True
                return False

            # Render đệ quy cây danh mục thuộc brand
            def render_cat_tree(parent_id, depth=0):
                tree_text = ""
                children = cats_parent_map.get(parent_id, [])

                for cat in children:
                    c_id = cat['term_id']

                    # Chỉ render node này nếu chính nó hoặc các cấp con của nó chứa sản phẩm thuộc Brand
                    if is_node_or_descendant_active(c_id):
                        sp_count = cat_counts.get(c_id, 0)
                        indent = "    " * depth
                        
                        icon = "📂" if depth == 0 else "📦"
                        level_label = f"Cấp {depth + 1}"
                        
                        count_text = f" ➔ **{sp_count} SP**" if sp_count > 0 else ""
                        tree_text += f"{indent}* {icon} **[{level_label}] {cat['name']}** (`{cat['slug']}`){count_text}\n"

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