<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Email notifikasi pendaftaran

Alamat pengirim, nama pengirim, dan penerima notifikasi diatur melalui `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, dan `MAIL_ADMIN_ADDRESS` pada `.env`. Untuk Gmail SMTP, gunakan `smtp.gmail.com` port `587` dengan TLS dan Google App Password; jangan gunakan atau membagikan kata sandi akun biasa.

Jika SMTP tidak dapat diakses, aplikasi dapat mencoba Gmail API sebagai cadangan:

1. Aktifkan Gmail API dan buat OAuth client di Google Cloud Console.
2. Simpan file OAuth client sebagai `storage/app/google/credentials.json`.
3. Jalankan `php artisan gmail:setup`, lalu masuk menggunakan akun yang sama dengan `MAIL_FROM_ADDRESS` dan berikan izin mengirim email.

## Notifikasi WhatsApp

Notifikasi WhatsApp menggunakan tautan `wa.me` tanpa API atau biaya gateway. Setelah mengajukan pendaftaran, peserta dapat membuka tautan untuk mengirim pemberitahuan kepada admin. Setelah admin menerima peserta, admin dapat membuka tautan untuk mengirim pesan kepada peserta. Tautan hanya membuka WhatsApp dengan pesan terisi; pesan baru terkirim setelah pengguna menekan **Kirim**. Nomor admin diatur melalui `WHATSAPP_ADMIN_NUMBER` pada `.env` dan default-nya `6283196853453`.

## Data aktivitas landing page

Konten profil, alumni, aktivitas, dan ulasan landing page disimpan pada tabel database yang sudah ada dan dikelola melalui endpoint admin. Untuk menambahkan tiga aktivitas contoh pada environment lokal saja, jalankan `php artisan db:seed --class=LandingPageActivitySeeder`. Seeder ini idempotent dan menolak berjalan di luar environment `local` atau `testing`.

Profil landing page menyimpan akun pada kolom Instagram tersendiri. Admin dapat mengisi nama akun (misalnya `@pamtechno`) atau tautan profil; alamat website lama tetap tersimpan di database tetapi tidak ditampilkan.

Token akan disimpan lokal di `storage/app/google/token.json`. Jangan commit atau membagikan file kredensial maupun token; direktori tersebut diabaikan Git.

## Penandaan Tidak Hadir otomatis

Peserta magang aktif yang belum memiliki catatan absensi masuk akan otomatis dicatat sebagai **Tidak Hadir** setelah 15 menit dari jam masuk yang ditetapkan pembimbing (default pukul 08.15 WIB). Jalankan scheduler Laravel agar tugas ini berjalan:

- Windows/Laragon: dari folder `web-magang`, jalankan `php artisan schedule:work` dan biarkan proses tetap berjalan.
- Server dengan cron: tambahkan `* * * * * cd /path/to/web-magang && php artisan schedule:run >> /dev/null 2>&1` ke crontab.

Tugas ini tidak mengubah catatan absensi yang sudah ada, seperti Hadir atau Izin.

Pembimbing dapat mengatur waktu mulai absensi masuk dan pulang untuk peserta bimbingannya melalui menu **Jadwal Absensi**. Peserta memiliki waktu 15 menit sejak jam masuk yang ditentukan untuk melakukan absensi. Jika belum diatur, jam masuk menggunakan default pukul 08.00 WIB dan jam pulang pukul 17.00 WIB.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
