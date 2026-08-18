import mysql.connector
import re

DB_CONFIG = {
    'host': '127.0.0.1', # Hoặc IP máy Docker của bạn
    'port': 3306,
    'user': 'wordpress',
    'password': 'password',
    'database': 'wordpress'
}

OLD_URLS = ['https://goldenfarm.local:8088', 'http://goldenfarm.local:8088']
NEW_URL = 'http://goldenfarm.test'

def replace_serialized(data, old_str, new_str):
    if not isinstance(data, str):
        return data
    # Đổi chuỗi thông thường
    data = data.replace(old_str, new_str)
    # Sửa độ dài chuỗi trong Serialized PHP (s:LENGTH:"VALUE")
    def fix_s(match):
        val = match.group(2).replace(old_str, new_str)
        return f's:{len(val)}:"{val}"'
    data = re.sub(r's:(\d+):"([^"]*)";', fix_s, data)
    return data

def run_replace():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("🟢 Đã kết nối MySQL! Đang thực hiện Replace toàn bộ URL...")

        # Lấy danh sách tất cả các bảng
        cursor.execute("SHOW TABLES")
        tables = [t[0] for t in cursor.fetchall()]

        for table in tables:
            cursor.execute(f"SHOW COLUMNS FROM `{table}`")
            columns = [c[0] for c in cursor.fetchall() if 'varchar' in c[1] or 'text' in c[1] or 'longtext' in c[1]]
            
            for col in columns:
                for old_url in OLD_URLS:
                    query = f"UPDATE `{table}` SET `{col}` = REPLACE(`{col}`, %s, %s) WHERE `{col}` LIKE %s"
                    cursor.execute(query, (old_url, NEW_URL, f"%{old_url}%"))
        
        conn.commit()
        print("🎉 TẤT CẢ URL ĐÃ ĐƯỢC REPLACE THÀNH CÔNG SANG http://goldenfarm.local:8088!")

    except Exception as e:
        print(f"❌ Lỗi: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    run_replace()