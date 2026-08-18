import sys
import locale
from PyQt5.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                             QHBoxLayout, QLabel, QPushButton, QTreeWidget, 
                             QTreeWidgetItem, QTreeWidgetItemIterator)
from PyQt5.QtCore import Qt
from PyQt5.QtGui import QColor, QFont

# Cấu hình Locale tiếng Việt để sắp xếp chuẩn A-Z
try:
    locale.setlocale(locale.LC_ALL, 'vi_VN.UTF-8')
except Exception:
    locale.setlocale(locale.LC_ALL, '')

class FilterDemo(QMainWindow):
    def __init__(self):
        super().__init__()
        self.initUI()

    def initUI(self):
        # 1. THIẾT LẬP CỬA SỔ CHÍNH
        self.setWindowTitle('Demo Giao diện Filter - GoldenFarm (Dark Mode)')
        self.setGeometry(300, 150, 420, 650)
        
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        main_layout = QVBoxLayout(central_widget)
        main_layout.setContentsMargins(16, 16, 16, 16)
        main_layout.setSpacing(12)
        
        # 2. TOOLBAR BAR (Tiêu đề + Nút Xóa)
        toolbar_layout = QHBoxLayout()
        
        title_label = QLabel('THƯƠNG HIỆU')
        title_label.setStyleSheet("font-weight: 800; font-size: 15px; color: #36bd4f; letter-spacing: 1px;")
        
        self.reset_btn = QPushButton('Xóa bộ lọc')
        self.reset_btn.setCursor(Qt.PointingHandCursor)
        self.reset_btn.clicked.connect(self.reset_filters)
        
        toolbar_layout.addWidget(title_label)
        toolbar_layout.addStretch()
        toolbar_layout.addWidget(self.reset_btn)
        
        main_layout.addLayout(toolbar_layout)
        
        # 3. KHU VỰC CÂY BỘ LỌC (TREE MENU)
        self.tree = QTreeWidget()
        self.tree.setHeaderHidden(True)
        self.tree.setAnimated(True)
        self.tree.setFocusPolicy(Qt.NoFocus)
        
        # Bắt sự kiện khi người dùng click Checkbox
        self.tree.itemChanged.connect(self.on_item_changed)
        
        # Giao diện QSS Dark Mode đồng bộ với Web
        self.setStyleSheet("""
            QMainWindow, QWidget {
                background-color: #121212;
                color: #ffffff;
                font-family: 'Segoe UI', Arial, sans-serif;
            }
            QPushButton {
                background-color: #1e1e1e;
                border: 1px solid #333333;
                border-radius: 14px;
                padding: 4px 12px;
                color: #cccccc;
                font-size: 12px;
                font-weight: 600;
            }
            QPushButton:hover {
                background-color: #009973;
                border-color: #009973;
                color: #ffffff;
            }
            QTreeWidget {
                background-color: #121212;
                border: 1px solid #282828;
                border-radius: 8px;
                padding: 6px;
                outline: 0;
            }
            QTreeWidget::item {
                padding: 6px 4px;
                border-bottom: 1px solid #1a1a1a;
            }
            QTreeWidget::item:hover {
                background-color: rgba(0, 153, 115, 0.12);
                border-radius: 4px;
            }
            QTreeWidget::item:selected {
                background-color: rgba(0, 153, 115, 0.2);
                color: #36bd4f;
            }
            QScrollBar:vertical {
                border: none;
                background: #121212;
                width: 6px;
                margin: 0px;
            }
            QScrollBar::handle:vertical {
                background: #333333;
                min-height: 20px;
                border-radius: 3px;
            }
            QScrollBar::handle:vertical:hover {
                background: #009973;
            }
        """)
        
        self.populate_tree()
        main_layout.addWidget(self.tree)
        self.show()

    def populate_tree(self):
        # Dữ liệu phân cấp thực tế 3 tầng lấy từ Database
        raw_data = {
            "GOLDEN FARM (133)": {
                "Chuyên mục Trang Chủ (1)": {},
                "Nguyên pha chế & làm bánh (2)": {
                    "Mứt Filling (9)": {}
                },
                "Thực phẩm (19)": {
                    "Bơ (6)": {},
                    "Mì Spaghetti (2)": {},
                    "Mù Tạt Kiểu Mỹ (1)": {},
                    "Mứt Sệt (5)": {},
                    "Mứt Trái Cây (7)": {},
                    "Nước Cốt (6)": {},
                    "Sinh Tố (22)": {},
                    "Sirô (39)": {
                        "Sirô (2)": {}
                    },
                    "Sirô Đậm Đặc (20)": {},
                    "Trà Mật Ong (1)": {},
                    "Trà Trái Cây (1)": {},
                    "Xốt BBQ (1)": {},
                    "Xốt Chấm (1)": {},
                    "Xốt Mayonnaise (1)": {},
                    "Xốt Salad (4)": {},
                    "Xốt Spaghetti (4)": {},
                    "Xốt Topping (6)": {},
                    "Xốt Trái Cây (6)": {}
                }
            },
            "MAMA ROSA (52)": {
                "Thực phẩm (52)": {
                    "Bơ (2)": {},
                    "Mứt Trái Cây (2)": {},
                    "Sinh Tố (10)": {},
                    "Sirô (38)": {},
                    "Xốt Salad (1)": {},
                    "Xốt Spaghetti (1)": {}
                }
            },
            "VỊ Á (8)": {
                "Thực phẩm (8)": {
                    "Trà Mật Ong (10)": {}
                }
            }
        }

        # Tắt phát tín hiệu tạm thời khi thêm dữ liệu
        self.tree.blockSignals(True)

        # Đệ quy dựng cây menu & Sắp xếp A-Z
        def add_nodes(parent_item, data_dict, level):
            sorted_keys = sorted(data_dict.keys(), key=locale.strxfrm)
            
            for key in sorted_keys:
                if parent_item is None:
                    item = QTreeWidgetItem(self.tree)
                else:
                    item = QTreeWidgetItem(parent_item)
                
                item.setText(0, key)
                item.setFlags(item.flags() | Qt.ItemIsUserCheckable | Qt.ItemIsAutoTristate)
                item.setCheckState(0, Qt.Unchecked)

                # Định dạng font & màu sắc theo từng Cấp (Level)
                font = QFont()
                if level == 0:
                    font.setBold(True)
                    font.setPointSize(10)
                    item.setFont(0, font)
                    item.setForeground(0, QColor('#36bd4f')) # Neon green cho Brand
                elif level == 1:
                    font.setBold(True)
                    font.setPointSize(9)
                    item.setFont(0, font)
                    item.setForeground(0, QColor('#ffffff')) # Trắng cho Nhóm
                else:
                    font.setWeight(50)
                    font.setPointSize(9)
                    item.setFont(0, font)
                    item.setForeground(0, QColor('#dddddd')) # Xám sáng cho Danh mục con

                # Gọi đệ quy cho cấp tiếp theo
                children_dict = data_dict[key]
                if children_dict:
                    add_nodes(item, children_dict, level + 1)

        add_nodes(None, raw_data, level=0)

        # Mở rộng cấp 0 và cấp 1 mặc định
        self.tree.expandAll()
        self.tree.blockSignals(False)

    # Đồng bộ Checkbox: Tick cha tự động tick con
    def on_item_changed(self, item, column):
        self.tree.blockSignals(True)
        state = item.checkState(column)
        
        def set_children_state(parent, check_state):
            for i in range(parent.childCount()):
                child = parent.child(i)
                child.setCheckState(0, check_state)
                set_children_state(child, check_state)

        set_children_state(item, state)
        self.tree.blockSignals(False)

    # Nút Xóa bộ lọc
    def reset_filters(self):
        self.tree.blockSignals(True)
        iterator = QTreeWidgetItemIterator(self.tree)
        while iterator.value():
            item = iterator.value()
            item.setCheckState(0, Qt.Unchecked)
            iterator += 1
        self.tree.blockSignals(False)

if __name__ == '__main__':
    app = QApplication(sys.argv)
    ex = FilterDemo()
    sys.exit(app.exec_())