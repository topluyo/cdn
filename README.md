# 🚀 Topluyo CDN Template

Modern, güvenli ve yapay zeka destekli dosya yükleme sistemi. Bu CDN template'i, gelişmiş NSFW (Not Safe For Work) filtreleme ve otomatik dosya optimizasyonu özellikleri ile birlikte gelir.

## ✨ Özellikler

### 🛡️ Gelişmiş Güvenlik
- **Bearer Token Tabanlı Kimlik Doğrulama**: AES-256-CBC şifrelemeli güvenli token sistemi
- **Kullanıcı Bazlı Yetkilendirme**: `user_id` ve `group_id` kontrolü
- **Dosya Boyutu Limitleri**: Dosya türüne göre akıllı boyut kontrolü

### 🤖 AI Destekli NSFW Filtreleme
- **TensorFlow.js & NSFWJS Entegrasyonu**: Gerçek zamanlı içerik analizi
- **Çoklu Kategori Desteği**: Porn, Sexy ve Hentai içerik tespiti
- **Format Destekli Analiz**:
  - 📸 **Resimler**: Doğrudan analiz
  - 🎞️ **GIF**: Frame-by-frame analiz
  - 🎬 **Video**: Akıllı sampling ile belirli karelerde analiz

### 📁 Kapsamlı Dosya Desteği
- **Resim Formatları**: JPG, JPEG, PNG, WebP, GIF, SVG
- **Video Formatları**: MP4, MKV, WebM
- **Otomatik Optimizasyon**: Resimleri WebP formatına dönüştürme
- **Boyut Limitleri**:
  - Resimler: 2MB
  - SVG/GIF: 1MB
  - Video: 30MB
  - Diğer dosyalar: 20MB

### 🎨 Kullanıcı Deneyimi
- **Gerçek Zamanlı Progress Bar**: Upload durumu gösterimi
- **Sürükle-Bırak Desteği**: Kolay dosya yükleme
- **Responsive Tasarım**: Tüm cihazlarda uyumlu
- **Loading Animasyonları**: Modern UI/UX

## 🔧 Kurulum

### Gereksinimler
- PHP 7.4 veya üzeri
- GD extension (resim işleme için)
- Modern web browser (JavaScript ES6+ desteği)

### Hızlı Başlangıç

1. **Projeyi klonlayın:**
```bash
git clone https://github.com/topluyo/cdn.git
cd cdn
```

2. **Yapılandırma:**
```php
// php/index.php dosyasını düzenleyin
$APPLICATION_KEY = "your_secure_application_key_here";
$FRONT_END_SOURCE = "https://your-domain.com/uploads/".date("Ymd")."/";
```

3. **Klasör izinleri:**
```bash
chmod 755 php/uploads/
```

## ⚙️ Yapılandırma

### NSFW Filtreleme Ayarları

#### 🔒 NSFW Kontrolünü Açma/Kapama
```javascript
const nfswIsEnabled = true;  // NSFW kontrolü aktif
const nfswIsEnabled = false; // NSFW kontrolü kapalı
```

#### 🎯 Tolerans Seviyesi Ayarlama
```javascript
let nfswTolerance = 0.3; // Varsayılan: 0.3 (0.0 - 1.0 arası)
// 0.1 = Çok hassas (daha fazla dosya reddedilir)
// 0.5 = Orta seviye
// 0.8 = Toleranslı (daha az dosya reddedilir)
```

#### 🧠 Kendi NSFW Modelinizi Kullanma
```javascript
// Varsayılan model
const modelUrl = 'https://raw.githubusercontent.com/nsfw-filter/nsfwjs/master/example/nsfw_demo/public/model/';

// Kendi modelinizi kullanın
const modelUrl = 'https://your-domain.com/path/to/your/custom/model/';

// Model yükleme konfigürasyonu
nfswModel = await nsfwjs.load(modelUrl, {
  size: 299,        // Resim boyutu (224, 299, 512)
  numThreads: 2,    // CPU thread sayısı
  onProgress: (progress) => {
    console.log(`Model yükleniyor: ${progress * 100}%`);
  }
});
```

### Dosya Yükleme Konfigürasyonu

#### 📏 Boyut Limitleri
```php
// Resim dosyaları için
if ($fileSize > 2 * 1024 * 1024) { // 2MB
    die("Dosya boyutu 2MB'den küçük olmalı.");
}

// Video dosyaları için
if ($fileSize > 30 * 1024 * 1024) { // 30MB
    die("Video dosyası 30MB'den küçük olmalı.");
}
```

#### 🖼️ Resim Optimizasyonu
```php
// WebP kalite ayarı
imagewebp($resizedImage, $webpFilePath, 80); // %80 kalite

// Yeniden boyutlandırma
$file = uploadImage("file", 720); // Yükseklik: 720px
```

## 🎮 Kullanım

### Frontend Entegrasyonu

#### Basit Dosya Yükleme
```html
<input type="file" id="fileInput" accept="*/*">
<script>
document.getElementById('fileInput').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await fetch('/php/index.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + yourAuthToken
        },
        body: formData
    });
    
    const result = await response.text();
    console.log('Dosya URL:', result);
});
</script>
```

#### İframe Entegrasyonu
```html
<iframe src="/php/index.php" width="100%" height="400"></iframe>
<script>
window.addEventListener('message', (event) => {
    if (event.data.action === '<share') {
        console.log('Yüklenen dosya:', event.data.data);
    }
});
</script>
```

### API Kullanımı

#### Kimlik Doğrulama Token Oluşturma
```php
function createAuthToken($userId, $groupId) {
    global $APPLICATION_KEY;
    
    $data = json_encode([
        'user_id' => $userId,
        'group_id' => $groupId,
        'timestamp' => time()
    ]);
    
    return encrypt($data, $APPLICATION_KEY);
}
```

#### cURL ile Dosya Yükleme
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -F "file=@/path/to/your/file.jpg" \
  https://your-domain.com/php/index.php
```

## 🧪 NSFW Model Detayları

### Desteklenen Kategoriler
- **Porn**: Pornografik içerik
- **Sexy**: Cinsel çekicilik içeren içerik
- **Hentai**: Anime/manga tarzı yetişkin içerik
- **Neutral**: Güvenli içerik
- **Drawing**: Çizim/illüstrasyon

### Analiz Stratejileri

#### 🖼️ Resim Analizi
```javascript
// Tek seferlik analiz
const predictions = await nfswModel.classify(imageElement);
```

#### 🎞️ GIF Analizi
```javascript
// Frame-by-frame analiz
const config = {
  topk: 1,     // En yüksek 1 tahmin
  fps: 1,      // Saniyede 1 frame
  onFrame: ({ index, totalFrames, predictions }) => {
    console.log(`Frame ${index}/${totalFrames}:`, predictions);
  }
};
const results = await nfswModel.classifyGif(gifElement, config);
```

#### 🎬 Video Analizi
```javascript
// Akıllı sampling
// ≤10s: Her saniye
// 10-60s: Her 5 saniye  
// >60s: Her 15 saniye
```

## 🛠️ Geliştirme

### Dosya Yapısı
```
cdn/
├── php/
│   ├── index.php          # Ana uygulama
│   └── uploads/           # Yüklenen dosyalar
├── README.md              # Bu dosya
└── LICENSE                # Lisans
```

### Özelleştirme

#### Yeni Dosya Formatı Ekleme
```php
// uploadImage() fonksiyonunda
if ($imageExtension === 'your_format') {
    // Özel işleme kodu
}
```

#### NSFW Kategorisi Ekleme
```javascript
// Yeni kategori kontrolü
const customScore = predictions.find(p => p.className === 'YourCategory')?.probability || 0;
const maxNsfwScore = Math.max(pornScore, sexyScore, hentaiScore, customScore);
```

## 📊 Performans

### Optimizasyon İpuçları
- NSFW modeli ~40MB boyutunda
- İlk yükleme 3-5 saniye sürebilir
- Cache kullanımı önerilir
- CDN üzerinde model hosting yapabilirsiniz

### Tarayıcı Desteği
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 🔒 Güvenlik

### En İyi Uygulamalar
- APPLICATION_KEY'i güçlü tutun (32+ karakter)
- HTTPS kullanın
- Upload klasörüne PHP execution'ı engelleyin
- Rate limiting uygulayın
- File type validation yapın

### Örnek .htaccess
```apache
# uploads/ klasörü için
<Files "*.php">
    Order Allow,Deny
    Deny from all
</Files>
```

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun: `git checkout -b feature/amazing-feature`
3. Commit yapın: `git commit -m 'Add amazing feature'`
4. Push yapın: `git push origin feature/amazing-feature`
5. Pull Request açın

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## 🙏 Teşekkürler

- [NSFWJS](https://github.com/infinitered/nsfwjs) - NSFW detection
- [TensorFlow.js](https://www.tensorflow.org/js) - Machine learning framework

---

⭐ Bu projeyi beğendiyseniz, yıldız vermeyi unutmayın!
