# User Management System

Sistem manajemen user untuk aplikasi Surat dengan fitur CRUD lengkap, reset password, dan validasi.

## Setup Database

1. Pastikan database `surat_app` sudah dibuat
2. Jalankan migration untuk membuat tabel users:
   ```
   http://localhost/surat/backend/database/run_migration.php
   ```

## Default Login

- **Username**: admin
- **Password**: password
- **Role**: admin

## Fitur

### 1. **Tambah User**
- Form validasi lengkap
- Username unik (minimal 3 karakter)
- Email unik dengan validasi format
- Password minimal 6 karakter
- Role: admin atau user

### 2. **Edit User**
- Update username, nama lengkap, email, dan role
- Validasi duplikasi username dan email
- Password tidak wajib diubah saat edit

### 3. **Reset Password**
- Admin dapat mereset password user lain
- Password baru minimal 6 karakter
- Konfirmasi password untuk keamanan

### 4. **Hapus User**
- Konfirmasi sebelum hapus
- Tidak bisa hapus akun sendiri
- Soft delete (opsional, bisa dikembangkan)

### 5. **Pencarian & Filter**
- Pencarian real-time berdasarkan nama, username, atau email
- Filter berdasarkan role
- Pagination untuk performa optimal

## File Structure

```
frontend/
├── pages/users/
│   └── user.php              # Halaman utama manajemen user
├── assets/js/
│   └── users.js              # JavaScript untuk CRUD operations
└── layouts/
    └── sidebar.php           # Updated dengan link user management

backend/
├── api/
│   ├── user.php              # API CRUD user
│   └── reset_password.php    # API reset password (updated)
├── config/
│   └── pdo_connection.php    # Database connection PDO
└── database/
    ├── migrations/
    │   └── 002_create_users_table.sql
    └── run_migration.php     # Migration runner
```

## API Endpoints

### User API (`/backend/api/user.php`)

- **GET** - List all users with pagination
  ```
  GET /api/user.php?page=1&limit=10&search=john
  ```

- **GET** - Get single user
  ```
  GET /api/user.php?id=1
  ```

- **POST** - Create new user
  ```json
  {
    "username": "newuser",
    "password": "password123",
    "nama_lengkap": "New User",
    "email": "newuser@example.com",
    "role": "user"
  }
  ```

- **PUT** - Update user
  ```json
  {
    "username": "updateduser",
    "nama_lengkap": "Updated Name",
    "email": "updated@example.com",
    "role": "admin"
  }
  ```

- **DELETE** - Delete user
  ```
  DELETE /api/user.php?id=1
  ```

### Reset Password API (`/backend/api/reset_password.php`)

- **POST** - Admin reset user password
  ```json
  {
    "action": "change_password",
    "user_id": 1,
    "new_password": "newpassword123"
  }
  ```

## Security Features

1. **Session-based Authentication**
   - Semua API endpoint memerlukan login session
   - Auto redirect ke login jika tidak terautentikasi

2. **Input Validation**
   - Server-side dan client-side validation
   - SQL injection protection dengan prepared statements
   - Password hashing dengan bcrypt

3. **Error Handling**
   - Detailed error messages untuk development
   - User-friendly error messages untuk UI
   - Proper HTTP status codes

## UI Features

1. **Responsive Design**
   - Mobile-friendly interface
   - Adaptive table layout
   - Touch-friendly buttons

2. **Real-time Features**
   - Live search with debouncing
   - Instant validation feedback
   - Loading indicators

3. **User Experience**
   - Modal dialogs for forms
   - Confirmation dialogs for destructive actions
   - Success/error notifications
   - Pagination for large datasets

## Usage Examples

### Mengakses Halaman User Management
1. Login sebagai admin
2. Klik menu "User" di sidebar
3. Halaman akan menampilkan daftar semua user

### Menambah User Baru
1. Klik tombol "Tambah User"
2. Isi form dengan data yang valid
3. Klik "Simpan"

### Reset Password User
1. Klik tombol reset password (icon kunci) pada user yang dipilih
2. Masukkan password baru
3. Konfirmasi password
4. Klik "Reset Password"

## Troubleshooting

### Database Connection Error
- Pastikan WAMP server berjalan
- Cek konfigurasi database di `pdo_connection.php`
- Pastikan port MySQL benar (default: 3308)

### Permission Error
- Pastikan user sudah login
- Cek session di browser developer tools

### Validation Error
- Cek format email
- Pastikan password minimal 6 karakter
- Username minimal 3 karakter dan unik

## Development Notes

- JavaScript menggunakan ES6+ features
- CSS menggunakan modern flexbox/grid
- PHP 8.1+ dengan strict typing
- MySQL dengan charset utf8mb4
- Responsive design dengan mobile-first approach