<h1 align="center">⚡ zFramework SSL Manager</h1>

<p align="center">
  <b>PHP 8+ destekli, otomatik ACME SSL yönetim sistemi</b><br>
  cPanel API entegrasyonu ile kolay SSL kurulumu 🚀
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8%2B-blue?style=for-the-badge&logo=php" />
  <img src="https://img.shields.io/badge/zFramework-Compatible-brightgreen?style=for-the-badge" />
  <img src="https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge" />
  <img src="https://img.shields.io/badge/Status-Stable-success?style=for-the-badge" />
</p>

---

## 🧠 Hakkında

**zFramework SSL Manager**, PHP 8+ sürümleriyle uyumlu, domainlerinizi yönetip otomatik olarak **Let's Encrypt (ACME)** üzerinden SSL sertifikalarını oluşturup yüklemenizi sağlayan bir araçtır.  
Paylaşımlı hostinglerde veya cPanel kullanan sunucularda, tek bir komutla kurulum yapabilirsiniz.

---

## 🚀 Özellikler

- ⚙️ **PHP 8+** tam uyumlu mimari  
- 🔐 **ACME Challenge (HTTP-01)** desteği  
- 🌍 **Domain ekleme ve yönetimi**  
- 📁 Domain bazlı `public_dir` tanımlama  
- 🧩 **cPanel API** ile otomatik sertifika yükleme  
- 🖥 Terminal komutlarıyla tam kontrol: `db migrate`, `run`  
- 🪄 zFramework CLI tabanlı sade kullanım  

---

## ⚡ Hızlı Başlangıç

### 1️⃣ Gereksinimler
- PHP **8.0 veya üzeri**
- `cURL` ve `OpenSSL` PHP uzantıları
- MySQL veritabanı
- cPanel API erişimi (SSL yükleme ve ACME Challenge için)

---

### 2️⃣ Kurulum Adımları

#### 🔸 Veritabanı oluşturma
Öncelikle aşağıdaki isimde bir veritabanı oluşturun:
```sql
CREATE DATABASE ssl_manager;
```

#### 🔸 Migrasyonları çalıştırın
```bash
php terminal db migrate
```

#### 🔸 Projeyi başlatın
```bash
php terminal run
```

Artık sistem çalışıyor ve domain eklemeye hazırsınız 🎉

---

## ⚙️ Yapılandırma

`App/Helpers/API.php` dosyasında **AutoSSL** sınıfı yapılandırmasını kendi sisteminize göre güncelleyin. Örnek Windows/XAMPP config satırı:

```php
// Windows örneği (XAMPP)
self::$autoSSL = new AutoSSL(AutoSSL::PROD, 'D:\xampp\apache\conf\openssl.cnf');
```

Linux/Mac veya farklı bir OpenSSL konumu kullanıyorsanız yolu uygun şekilde değiştirin, örneğin:

```php
// Linux örneği
self::$autoSSL = new AutoSSL(AutoSSL::PROD, '/etc/ssl/openssl.cnf');
```

> 🧩 **Önemli Notlar:**  
> - `PROD` gerçek (production) sertifika istemcisi içindir — canlı siteler için kullanın.  
> - `STAGING` test/deneme amaçlıdır ve Let's Encrypt'teki oran limitlerine takılmamak ya da test sertifikaları almak için tercih edilmelidir.  
> - `openssl.cnf` yolunu kendi sisteminizdeki OpenSSL konumuna göre **kesinlikle** güncelleyin.  
> - cPanel API bilgileri (`username`, `token`) ve diğer hassas değerleri çevresel değişkenlerde veya güvenli bir config dosyasında saklayın — asla doğrudan sürüm kontrolüne göndermeyin. (Tercihen Local bir proje olarak çalıştırın.)
> - `PROD` ve `STAGING` arasında geçiş yaparken zFramework/Caches/AutoSSL dosyasını tamamen kaldırın.

---

## 🌐 Domain Ekleme

Yeni bir domain eklerken sizden şu bilgiler istenir:
- **Domain adı:** (örn. `example.com`)
- **Public Directory:** ACME doğrulama dosyalarının oluşturulacağı dizin  
  (örn. `/home/user/public_html` veya `D:\xampp\htdocs`)

Ardından sistem otomatik olarak:
1. ACME challenge dosyalarını oluşturur,  
2. cPanel API üzerinden domaininize yükler,  
3. Sertifikayı üretip otomatik olarak kurar.  

---

## 💡 Kullanım Akışı Örneği

```bash
# Veritabanını migrate et
php terminal db migrate

# Projeyi başlat
php terminal run

# Domain ekleme sırasında girilecek örnek:
# Domain: example.com
# Public Dir: /home/example/public_html
```

Sistem challenge’ı oluşturur, doğrulama tamamlanır ve sertifika kurulur ✅

---

## 🔒 Güvenlik Notları

- cPanel API kimlik bilgilerinizi güvenli bir ortamda saklayın.  
- ACME doğrulama dosyalarına yalnızca doğrulama sürecinde dış erişim izni verin.  
- Herhangi bir sorun durumunda `storage/logs` dizinindeki kayıtları inceleyin.  
- Test ederken `STAGING` modunu kullanarak rate limit’lere takılmayı önleyin.

---

## 🧰 Geliştirici Notları

| Komut                     | Açıklama                         |
| ------------------------- | -------------------------------- |
| `php terminal db migrate` | Veritabanı tablolarını oluşturur |
| `php terminal run`        | Projeyi başlatır                 |

---

## 🧾 Lisans

Bu proje [MIT Lisansı](LICENSE) ile lisanslanmıştır.  
Özgürce kullanabilir, değiştirebilir ve geliştirebilirsiniz.

---

<p align="center">
  Made with ❤️ by <a href="https://mustafaomereser.com" target="_blank">Mustafa Ömer Eser</a><br>
  <i>zFramework • Simple, Powerful, and Clean PHP Framework</i>
</p>
