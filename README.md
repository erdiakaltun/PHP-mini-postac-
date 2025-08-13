# 📮 Mini PHP Postacı

Bu proje, PHP ile yazılmış basit ve hafif bir API test aracıdır. Postman benzeri temel istek gönderme işlevlerini destekler. İstekleri kaydetme, düzenleme, silme ve koleksiyon olarak yönetme imkanı sağlar.

## 🚀 Özellikler

- ✅ GET, POST, PUT, DELETE, PATCH istekleri desteklenir
- 📂 Klasör/koleksiyon yapısı ile isteklerinizi organize edin
- 💾 Koleksiyonları `collections.json` dosyasında saklar
- 🔐 IP tabanlı erişim kontrolü içerir
- 📬 curl ile istek gönderimi
- 🧾 Headers ve Body düzenleme
- 🌐 JSON yanıtları düzgün formatta gösterilir

## 📁 Kurulum

### Gereksinimler

- PHP 7.x veya üzeri
- Web sunucusu (Apache/Nginx) veya PHP built-in server

### Adımlar

1. Bu projeyi klonlayın veya dosyaları indirin:

   ```bash
   git clone https://github.com/erdiakaltun/mini-php-postaci.git
   cd mini-php-postaci
## 📦 Dosya Yapısı
mini-php-postaci/
├── collections.json     # Koleksiyonların saklandığı dosya
├── index.php            # Ana uygulama dosyası
└── README.md            # Bu dosya


