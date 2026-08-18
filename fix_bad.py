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

# Lấy term_taxonomy_id của các slug tương ứng
cursor.execute(f"""
    SELECT t.slug, tt.term_taxonomy_id 
    FROM {PREFIX}terms t 
    INNER JOIN {PREFIX}term_taxonomy tt ON t.term_id = tt.term_id 
    WHERE t.slug IN ('siro-golden-farm', 'siro-mama-rosa', 'siro-chung')
""")
terms = {row['slug']: row['term_taxonomy_id'] for row in cursor.fetchall()}

target_id = terms.get('siro-golden-farm')
bad_chung_id = terms.get('siro-chung')
bad_mama_id = terms.get('siro-mama-rosa')

if target_id:
    # Chuyển SP 9153 sang siro-golden-farm
    if bad_chung_id:
        cursor.execute(f"""
            UPDATE {PREFIX}term_relationships 
            SET term_taxonomy_id = %s 
            WHERE object_id = 9153 AND term_taxonomy_id = %s
        """, (target_id, bad_chung_id))
    
    # Chuyển SP 16734 sang siro-golden-farm
    if bad_mama_id:
        cursor.execute(f"""
            UPDATE {PREFIX}term_relationships 
            SET term_taxonomy_id = %s 
            WHERE object_id = 16734 AND term_taxonomy_id = %s
        """, (target_id, bad_mama_id))
    
    conn.commit()
    print("🎉 ĐÃ TỰ ĐỘNG SỬA THÀNH CÔNG 2 SẢN PHẨM VỀ 'siro-golden-farm'!")
else:
    print("❌ Không tìm thấy danh mục đích 'siro-golden-farm'.")

cursor.close()
conn.close()