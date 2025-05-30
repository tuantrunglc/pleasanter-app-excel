# Hướng dẫn sử dụng Docker cho Pleasanter App Excel

## Yêu cầu
- Docker và Docker Compose đã được cài đặt trên máy của bạn
- Git (để clone repository)
- Composer (để cập nhật dependencies)

## Cài đặt và chạy ứng dụng

### 1. Clone repository
```bash
git clone <repository-url>
cd pleasanter-app-excel
```

### 2. Cấu hình môi trường
Sao chép file `.env.example` thành `.env` và cấu hình các biến môi trường:
```bash
cp .env.example .env
```

### 3. Chạy script chuẩn bị môi trường
Chạy script chuẩn bị để tạo thư mục cần thiết, cấp quyền và cập nhật dependencies:
```bash
# Trên Windows
docker-prepare.bat

# Trên Linux/Mac
chmod +x docker-prepare.sh
./docker-prepare.sh
```

### 4. Build và chạy Docker containers
```bash
docker-compose up -d --build
```

### 5. Sửa quyền truy cập trong container
```bash
docker-compose exec -u root app bash /var/www/html/docker-fix-permissions.sh
```

### 6. Tạo khóa ứng dụng và chạy migrations
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

## Truy cập ứng dụng
Sau khi hoàn tất các bước trên, bạn có thể truy cập ứng dụng tại:
- http://localhost (hoặc cổng được cấu hình trong .env)

## Xử lý lỗi phổ biến

### Lỗi cổng đã được sử dụng
Nếu bạn gặp lỗi "Bind for 0.0.0.0:3306 failed: port is already allocated", điều này có nghĩa là cổng 3306 đã được sử dụng trên máy của bạn. Hãy thử các bước sau:

1. Thay đổi cổng trong file .env:
```
FORWARD_DB_PORT=3307
```

2. Nếu bạn không có biến FORWARD_DB_PORT trong file .env, hãy thêm nó vào:
```bash
echo "FORWARD_DB_PORT=3307" >> .env
```

3. Khởi động lại containers:
```bash
docker-compose down
docker-compose up -d
```

4. Nếu bạn cần kết nối đến MySQL từ bên ngoài container, hãy sử dụng cổng 3307:
```bash
mysql -h 127.0.0.1 -P 3307 -u sail -ppassword laravel
```

### Lỗi kết nối MySQL
Nếu bạn gặp lỗi "getaddrinfo for mysql failed: Temporary failure in name resolution", hãy thử các bước sau:

1. Kiểm tra xem container MySQL đã chạy chưa:
```bash
docker-compose ps
```

2. Nếu container MySQL chưa chạy, hãy khởi động lại tất cả các container:
```bash
docker-compose down
docker-compose up -d
```

3. Kiểm tra kết nối MySQL từ container Laravel:
```bash
docker-compose exec app bash /var/www/html/docker-check-mysql.sh
```

4. Nếu vẫn gặp vấn đề, hãy thử:
```bash
# Dừng containers
docker-compose down

# Xóa volumes
docker volume rm pleasanter-app-excel_dbdata

# Build lại containers
docker-compose up -d --build
```

5. Đảm bảo rằng thông tin kết nối MySQL trong file .env khớp với cấu hình trong docker-compose.yml:
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### Lỗi quyền truy cập
Nếu bạn gặp lỗi quyền truy cập, hãy thử các bước sau:

1. Sửa quyền truy cập trong container:
```bash
docker-compose exec app bash /var/www/html/docker-fix-permissions.sh
```

2. Nếu vẫn gặp vấn đề, hãy thử:
```bash
# Dừng containers
docker-compose down

# Xóa volumes
docker volume rm pleasanter-app-excel_app-storage pleasanter-app-excel_app-cache

# Chạy lại script chuẩn bị
# Trên Windows
docker-prepare.bat
# Trên Linux/Mac
./docker-prepare.sh

# Build lại containers
docker-compose up -d --build

# Sửa quyền truy cập
docker-compose exec app bash /var/www/html/docker-fix-permissions.sh
```

### Lỗi file_put_contents(.env): Failed to open stream
Nếu bạn gặp lỗi không thể ghi vào file .env, hãy thực hiện các bước sau:

1. Đảm bảo file .env có quyền ghi:
```bash
# Trên Windows
icacls .env /grant Everyone:F

# Trên Linux/Mac
chmod 666 .env
```

2. Sửa quyền truy cập trong container:
```bash
docker-compose exec app bash /var/www/html/docker-fix-permissions.sh
```

### Lỗi composer
Nếu bạn gặp lỗi liên quan đến composer như "Failed to open stream: Permission denied", hãy thử các bước sau:

1. Sửa quyền truy cập trong container:
```bash
docker-compose exec app bash /var/www/html/docker-fix-permissions.sh
```

2. Sử dụng script composer-www để chạy composer với user www:
```bash
docker-compose exec app composer-www install
```

3. Hoặc chạy composer trực tiếp với quyền root:
```bash
docker-compose exec app composer install
```

4. Nếu vẫn gặp vấn đề, hãy thử:
```bash
# Xóa thư mục vendor
docker-compose exec app rm -rf vendor

# Cài đặt lại dependencies
docker-compose exec app composer install
```

5. Cập nhật composer.lock:
```bash
docker-compose exec app composer update
```

## Các lệnh hữu ích

### Xem logs
```bash
docker-compose logs -f
```

### Truy cập container
```bash
docker-compose exec app bash
```

### Truy cập container với quyền root
```bash
docker-compose exec -u root app bash
```

### Dừng containers
```bash
docker-compose down
```

### Rebuild containers
```bash
docker-compose up -d --build
```

### Xem logs của container cụ thể
```bash
docker-compose logs -f app
docker-compose logs -f webserver
docker-compose logs -f db
```

## Cấu trúc Docker
- **app**: PHP-FPM container chạy ứng dụng Laravel
- **webserver**: Nginx container phục vụ ứng dụng
- **db**: MySQL container lưu trữ dữ liệu

## Volumes
- **dbdata**: Lưu trữ dữ liệu MySQL
- **app-storage**: Lưu trữ các file tạm thời và uploads của Laravel
- **app-cache**: Lưu trữ các file cache của Laravel

## Cấu hình nâng cao

### Tùy chỉnh PHP
Bạn có thể tùy chỉnh cấu hình PHP bằng cách chỉnh sửa file `php/local.ini`.

### Tùy chỉnh Nginx
Bạn có thể tùy chỉnh cấu hình Nginx bằng cách chỉnh sửa file `nginx/conf.d/app.conf`.

### Chạy với quyền root
Nếu bạn vẫn gặp vấn đề với quyền truy cập, bạn có thể chỉnh sửa docker-compose.yml để chạy container với quyền root bằng cách xóa dòng `user: "1000:1000"` trong phần cấu hình của service app.