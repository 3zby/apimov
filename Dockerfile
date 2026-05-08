# استخدم صورة PHP مع Apache
FROM php:8.2-apache

# =========================
# تثبيت المكتبات المطلوبة
# =========================
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =========================
# تفعيل Apache modules
# =========================
RUN a2enmod rewrite headers

# =========================
# نسخ المشروع
# =========================
COPY . /var/www/html/

# =========================
# صلاحيات
# =========================
RUN chown -R www-data:www-data /var/www/html

# =========================
# إخفاء Deprecated warnings (حل مشكلتك)
# =========================
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_NOTICE" > /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini

# =========================
# إعدادات PHP (اختياري)
# =========================
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/custom.ini && \
    echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/custom.ini

# =========================
# ENV (مهم: هذه مجرد placeholders)
# =========================
ENV DB_HOST=""
ENV DB_NAME=""
ENV DB_USER=""
ENV DB_PASS=""

# =========================
# المنفذ
# =========================
EXPOSE 80
