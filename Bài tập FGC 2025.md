# Bài tập: Ứng dụng quản lý ảnh cá nhân (Google Photos Clone)

Ghi chú: Những phần màu vàng là phần nâng cao, có thể thực hiện sau khi đã hoàn thành các chức năng cơ bản.

## Mục tiêu

Xây dựng một ứng dụng web cho phép người dùng **đăng nhập, tải lên, quản lý, tìm kiếm và chia sẻ ảnh** – tương tự Google Photos – sử dụng **Laravel \+ Inertia.js \+ React \+ Shadcn UI**.

## Yêu cầu công nghệ

- Backend: Laravel 12  ✅
- Frontend: React 19  ✅
- UI Library: Shadcn UI ✅
- Database: SQL (SQLite / MySQL ✅ / PostgreSQL)

## Yêu cầu chức năng

## Tài khoản

- Đăng ký, Đăng nhập, Quên mật khẩu  ✅
- Hồ sơ cá nhân: Ảnh, Tên  
- Bạn bè, kết bạn, xóa bạn, block

## Quản lý ảnh

- Upload ảnh đơn lẻ ✅, Upload nhiều ảnh cùng lúc bằng kéo thả / Paste  
- Xóa ảnh  ✅
- Thùng rác  ✅
- Hiển thị ảnh dạng lưới, sắp xếp, phân trang  ✅
- Nhóm ảnh theo ngày upload  ✅
- Trích xuất metadata từ ảnh và cho phép nhóm ảnh theo ngày chụp, địa điểm chụp  
- Tạo thumbnail cho ảnh  
- Xem từng ảnh bằng Modal
- Tạo Album  ✅
- Tạo Album tự động bằng metadata  
- Upload Video/Gif

## Thông báo

- Upload và xử lý thành công 
- Ảnh được chia sẻ từ bạn bè

## Chia sẻ

- Tạo Link chia sẻ công khai  
- Chia sẻ với bạn bè  
- Quản lý các phần đã chia sẻ  
- Tạo Link chia sẻ tự động hết hạn 

## Giao diện

- Lưới ảnh responsive theo màn hình  
- Chế độ tối  ✅
- Lazy loading và infinite scroll cho lưới ảnh ✅

## Hiệu năng

- Optimize ảnh trước khi lưu  
- Giới hạn dung lượng upload mỗi người dùng ✅

## Triển khai

- Triển khai trên server thực tế ✅

## Yêu cầu kỹ thuật

- Sử dụng cấu trúc project theo chuẩn Laravel [https://laravel.com/docs/12.x/structure](https://laravel.com/docs/12.x/structure)  ✅
- Sử dụng Typescript  ✅
- Viết README.md chi tiết về ứng dụng, bao gồm mô tả chức năng, các bước cài đặt  
- Viết Unit Test  
- Sử dụng docker để triển khai
