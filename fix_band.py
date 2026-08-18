import mysql.connector

DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}
PREFIX = "wp_"

conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor(dictionary=True)

# Query chỉ tìm các sản phẩm Golden Farm dính nhầm danh mục Mama Rosa hoặc danh mục chung
query = f"""
    SELECT p.ID, p.post_title, t_cat.name AS cat_name, t_cat.slug AS cat_slug
    FROM {PREFIX}posts p
    INNER JOIN {PREFIX}term_relationships tr_b ON p.ID = tr_b.object_id
    INNER JOIN {PREFIX}term_taxonomy tt_b ON tr_b.term_taxonomy_id = tt_b.term_taxonomy_id
    INNER JOIN {PREFIX}terms t_b ON tt_b.term_id = t_b.term_id AND t_b.slug = 'golden-farm'
    INNER JOIN {PREFIX}term_relationships tr_c ON p.ID = tr_c.object_id
    INNER JOIN {PREFIX}term_taxonomy tt_c ON tr_c.term_taxonomy_id = tt_c.term_taxonomy_id
    INNER JOIN {PREFIX}terms t_cat ON tt_c.term_id = t_cat.term_id 
    WHERE t_cat.slug IN ('siro-mama-rosa', 'siro-chung')
"""

cursor.execute(query)
results = cursor.fetchall()

print("\n🚨 DANH SÁCH SẢN PHẨM CẦN SỬA DANH MỤC:")
print("-" * 65)
if not results:
    print("✅ Không tìm thấy sản phẩm nào bị dính nhầm danh mục!")
else:
    for r in results:
        print(f"👉 ID: {r['ID']} | Tên: {r['post_title']}")
        print(f"   └─ Đang bị gán nhầm vào: {r['cat_name']} (slug: {r['cat_slug']})\n")

cursor.close()
conn.close()