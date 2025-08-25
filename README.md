# Terminal++ - Gerçek Ping Özellikli Terminal Simülasyonu

Bu proje, gerçek ping özelliği olan interaktif bir terminal simülasyonu sağlar.

## Özellikler

- 🔥 Gerçek ICMP ping gönderme (PHP backend ile)
- ⚡ Cyberpunk temalı arayüz
- 🎨 Çılgın animasyonlar ve efektler
- 🔊 Ses efektleri
- 📱 Responsive tasarım
- ♿ Erişilebilirlik ayarları

## Kurulum

### PHP Server Gereksinimi

Gerçek ping özelliğinin çalışması için bir PHP server gereklidir.

### Seçenek 1: XAMPP (Önerilen)

1. [XAMPP](https://www.apachefriends.org/index.html) indirin ve kurun
2. XAMPP Control Panel'i açın
3. Apache'yi başlatın
4. Projeyi `C:/xampp/htdocs/terminal++/` klasörüne kopyalayın
5. Tarayıcıda `http://localhost/terminal++/` adresini açın

### Seçenek 2: PHP Built-in Server

```bash
# Proje klasöründe çalıştırın
php -S localhost:8000
```

Sonra tarayıcıda `http://localhost:8000` adresini açın.

### Seçenek 3: WAMP/MAMP

- Windows için WAMP Server
- macOS için MAMP
kurarak projeyi `www` klasörüne kopyalayın.

## Kullanım

### Ping Komutu

```
ping google.com
ping 8.8.8.8
ping github.com
```

### Diğer Komutlar

- `help` - Tüm komutları listeler
- `clear` - Ekranı temizler
- `status` - Sistem durumu
- `matrix` - Matrix efekti
- `hack` - Hacker simülasyonu
- `nuke` - Nükleer patlama efekti
- `disco` - Disco modu
- `weather` - Hava durumu
- `joke` - Rastgele şaka

### Gizli Komutlar

- Konami kodu: ↑↑↓↓←→←→BA
- `sudo rm -rf` - Tehlikeli!
- `xyzzy` - Gizli mesaj

### Kısayol Tuşları

- **M** - Ses açma/kapama
- **A** - Animasyon açma/kapama  
- **C** - Yüksek kontrast modu
- **Z** - Çılgın mod

## Dosyalar

- `index.html` - Ana terminal arayüzü
- `ping.php` - PHP backend (gerçek ping işlevi)
- `README.md` - Bu dosya

## Güvenlik

`ping.php` dosyası güvenlik önlemleri içerir:
- Host parametresi validasyonu
- Command injection koruması
- CORS başlıkları
- Sadece POST isteklerini kabul eder

## Sorun Giderme

**Ping çalışmıyor?**
- PHP server'ın çalıştığından emin olun
- `ping.php` dosyasının erişilebilir olduğunu kontrol edin
- Tarayıcı konsölünde hata mesajlarını kontrol edin

**Ses çalışmıyor?**
- Tarayıcı ses politikası nedeniyle ilk etkileşimden sonra çalışır
- Ses ayarını kontrol edin (M tuşu)

## Tarayıcı Uyumluluğu

- ✅ Chrome/Chromium
- ✅ Firefox  
- ✅ Safari
- ✅ Edge

## Lisans

Bu proje eğitim amaçlı oluşturulmuştur. Özgürce kullanabilirsiniz.