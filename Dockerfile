# Gunakan image PHP + Apache
FROM php:8.1-apache

# Salin semua file project ke dalam direktori kerja di container
COPY . /var/www/html/

# Aktifkan modul mod_rewrite (kalau pakai .htaccess, bisa skip kalau nggak)
RUN a2enmod rewrite

# Buka port 10000 buat Render (harus sama kayak yang nanti di setting Render)
EXPOSE 10000

# Ubah port default Apache dari 80 ke 10000
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Jalankan Apache
CMD ["apache2-foreground"]
