# Laravel Toon

Standart JSON'u **TOON** formatına dönüştüren hafif bir Laravel paketi - insan tarafından okunabilir, ultra-minimal, satır tabanlı bir veri formatı.

[![Son Sürüm](https://img.shields.io/badge/sürüm-0.4.0-mavi.svg)](https://github.com/digitalcorehub/laravel-toon)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-kırmızı.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-mavi.svg)](https://php.net)

**🇬🇧 [English Documentation](README.md)**

## Özellikler

- ✅ JSON'u TOON formatına dönüştürme
- ✅ Ultra-minimal, insan tarafından okunabilir çıktı
- ✅ JSON anahtar sıralamasını korur
- ✅ İç içe diziler ve nesneleri destekler
- ✅ Dosya dönüştürme için CLI komutu
- ✅ Laravel Facade desteği
- ✅ Tam test kapsamı

## Kurulum

Paketi Composer ile kurun:

```bash
composer require digitalcorehub/laravel-toon
```

Paket otomatik olarak service provider ve facade'ını kaydedecektir.

## Gereksinimler

- PHP 8.3 veya üzeri
- Laravel 10.x, 11.x veya 12.x

## Kullanım

### Helper Fonksiyonlar

Paket, kolay erişim için global helper fonksiyonlar sağlar:

```php
// TOON'a kodla
$toon = toon_encode(['id' => 1, 'name' => 'Test']);
// veya
$toon = toon_encode('{"id": 1, "name": "Test"}');

// TOON'dan çöz
$array = toon_decode("id, name;\n1, Test");
```

### Facade Kullanımı

```php
use DigitalCoreHub\Toon\Facades\Toon;

// Diziden kodlama
$json = [
    'id' => 1,
    'name' => 'Test Ürünü',
    'price' => 99.99
];

$toon = Toon::encode($json);
// Çıktı:
// id, name, price;
// 1, Test Ürünü, 99.99
```

### JSON String'den Kodlama

```php
$jsonString = '{"id": 1, "name": "Test Ürünü", "price": 99.99}';
$toon = Toon::encode($jsonString);
```

### Nesnelerle Diziler

```php
$json = [
    'reviews' => [
        [
            'id' => 1,
            'customer' => 'Ahmet Yılmaz',
            'rating' => 5
        ],
        [
            'id' => 2,
            'customer' => 'Ayşe Demir',
            'rating' => 4
        ]
    ]
];

$toon = Toon::encode($json);
// Çıktı:
// reviews[2]{
//   id, customer, rating;
//   1, Ahmet Yılmaz, 5
//   2, Ayşe Demir, 4
// }
```

### İç İçe Yapılar

```php
$json = [
    'product' => 'Laptop',
    'specs' => [
        'cpu' => 'Intel i7',
        'ram' => '16GB'
    ],
    'reviews' => [
        ['id' => 1, 'rating' => 5],
        ['id' => 2, 'rating' => 4]
    ]
];

$toon = Toon::encode($json);
```

### Fluent Interface

Paket, akıcı builder-style API destekler:

```php
// JSON string'den
$toon = Toon::fromJson('{"id": 1, "name": "Test"}')->encode();

// Diziden
$toon = Toon::fromArray(['id' => 1, 'name' => 'Test'])->encode();

// TOON string'den
$array = Toon::fromToon("id, name;\n1, Test")->decode();
```

Fluent interface özellikle method chaining ve okunabilirlik için kullanışlıdır.

### Blade Directive

Blade şablonlarınızda TOON çıktısını göstermek için `@toon()` direktifini kullanın:

```blade
@toon($data)
```

Direktif otomatik olarak:
- Veriyi TOON formatına kodlar
- `<pre>` etiketi ile sarar
- Güvenli çıktı için HTML'i escape eder

**Örnek:**

```blade
<!-- Blade şablonunuzda -->
<div class="toon-output">
    @toon(['id' => 1, 'name' => 'Test Ürünü', 'price' => 99.99])
</div>
```

**Çıktı:**
```html
<div class="toon-output">
    <pre>id, name, price;
1, Test Ürünü, 99.99</pre>
</div>
```

### Logging Desteği

`Log::toon()` macro'sunu kullanarak verileri TOON formatında loglayın:

```php
use Illuminate\Support\Facades\Log;

$data = ['id' => 1, 'name' => 'Test'];
Log::toon($data); // 'info' seviyesinde loglar

// Log seviyesi belirt
Log::toon($data, 'debug');

// Kanal belirt
Log::toon($data, 'info', 'daily');
```

Macro verinizi TOON formatına kodlar ve Laravel'in logging sistemi üzerinden loglar.

### Console Styling

Konsol/terminal için renkli TOON çıktısı alın:

```php
use DigitalCoreHub\Toon\Facades\Toon;

$data = ['id' => 1, 'name' => 'Test', 'active' => true];
$colored = Toon::console($data, $output); // $output opsiyonel OutputInterface

// Artisan komutlarında
$this->line(Toon::console($data, $this->output));
```

**Syntax Highlighting:**
- Anahtarlar: Sarı
- Stringler: Yeşil
- Sayılar: Mavi
- Boolean'lar: Magenta
- Parantezler: Cyan

### Laravel Debugbar Entegrasyonu

[Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) yüklüyse, paket otomatik olarak şunları gösteren bir TOON paneli kaydeder:

- Son encode/decode işlemleri
- Performans zamanlaması (milisaniye cinsinden süre)
- Metadata (anahtar sayısı, satır sayısı, satır sayısı)
- Giriş/çıkış önizlemesi

Entegrasyon **otomatik** - yapılandırma gerekmez. Debugbar yüklü değilse, paket normal şekilde çalışmaya devam eder.

**Not:** Debugbar entegrasyonu opsiyoneldir ve Debugbar yüklü değilse paket işlevselliğini etkilemez.

### TOON'u Diziye Dönüştürme (Decode)

```php
use DigitalCoreHub\Toon\Facades\Toon;

// TOON string'inden decode
$toon = "reviews[1]{
  id, customer, rating, comment, verified;
  101, Alex Rivera, 5, Excellent!, true
}";

$array = Toon::decode($toon);
// Döndürür:
// [
//     [
//         'id' => 101,
//         'customer' => 'Alex Rivera',
//         'rating' => 5,
//         'comment' => 'Excellent!',
//         'verified' => true
//     ]
// ]
```

### Çoklu Satır Decode

```php
$toon = "reviews[2]{
  id, customer, rating;
  1, Ali, 5
  2, Ayşe, 4
}";

$array = Toon::decode($toon);
// 2 elemanlı dizi döndürür
```

### İç İçe Yapıları Decode Etme

```php
$toon = "product, reviews;
Laptop
reviews[2]{
  id, customer, rating;
  1, Ali, 5
  2, Ayşe, 4
}";

$array = Toon::decode($toon);
// Döndürür:
// [
//     'product' => 'Laptop',
//     'reviews' => [
//         ['id' => 1, 'customer' => 'Ali', 'rating' => 5],
//         ['id' => 2, 'customer' => 'Ayşe', 'rating' => 4]
//     ]
// ]
```

### Hata Yönetimi

Decode metodu geçersiz TOON formatları için `InvalidToonFormatException` fırlatır:

```php
use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
use DigitalCoreHub\Toon\Facades\Toon;

try {
    $array = Toon::decode($toon);
} catch (InvalidToonFormatException $e) {
    // Geçersiz TOON formatını işle
    echo "Hata: " . $e->getMessage();
}
```

Yaygın hatalar:
- Keys satırında eksik noktalı virgül (satır numaraları ile)
- Eşleşmeyen anahtar/değer sayıları (satır numaraları ile)
- Kapatılmamış parantezler `{` veya `}` (açıklayıcı mesajlarla)
- Geçersiz dizi blok formatları

**Örnek Hata Mesajları:**

```php
// Önce: "Mismatched key/value count"
// Sonra: "Key count (4) does not match value count (3) at line 5."

// Önce: "Keys line must end with semicolon"
// Sonra: "Missing semicolon in header block at line 2. Found: id, name, price"
```

### Dependency Injection Kullanımı

```php
use DigitalCoreHub\Toon\Toon;

class ProductController extends Controller
{
    public function __construct(
        private Toon $toon
    ) {}

    public function export()
    {
        $data = Product::all()->toArray();
        return $this->toon->encode($data);
    }
}
```

## CLI Komutları

### Encode: JSON → TOON

JSON dosyalarını TOON formatına dönüştürmek için Artisan komutunu kullanın:

```bash
php artisan toon:encode input.json output.toon
```

**Seçenekler:**
- `--preview` veya `-p`: Renkli önizleme göster

**Örnek:**

```bash
# Bir JSON dosyasını dönüştür
php artisan toon:encode storage/data.json storage/data.toon

# Renkli önizleme ile
php artisan toon:encode storage/data.json storage/data.toon --preview

# Komut şunları yapacak:
# - input.json'dan JSON okur
# - TOON formatına dönüştürür
# - output.toon'a kaydeder
# - --preview bayrağı kullanılırsa renkli önizleme gösterir
```

### Decode: TOON → JSON

TOON dosyalarını JSON formatına dönüştürmek için Artisan komutunu kullanın:

```bash
php artisan toon:decode input.toon output.json
```

**Seçenekler:**
- `--preview` veya `-p`: Girişin renkli önizlemesini göster

**Örnek:**

```bash
# Bir TOON dosyasını dönüştür
php artisan toon:decode storage/data.toon storage/data.json

# Komut şunları yapacak:
# - input.toon'dan TOON okur
# - JSON formatına dönüştürür (güzel yazdırılmış)
# - output.json'a kaydeder
# - Geçersiz girişte anlamlı hatalar gösterir
```

**Hata Yönetimi:**

TOON dosyası geçersiz formatta ise, komut bir hata mesajı gösterecektir:

```bash
$ php artisan toon:decode invalid.toon output.json
Invalid TOON format: Keys line must end with semicolon
```

## TOON Format Kuralları

TOON formatı şu kuralları takip eder:

1. **Nesneler**: Anahtarlar ilk satırda listelenir, ardından değerler bir sonraki satırda gelir
   ```
   id, name, price;
   1, Ürün Adı, 99.99
   ```

2. **Diziler**: Boyut göstergesi ile gösterilir `arrayName[count]{...}`
   ```
   reviews[2]{
     id, customer, rating;
     1, Ahmet, 5
     2, Ayşe, 4
   }
   ```

3. **Minimal Sözdizimi**: Gereksiz `{}`, `[]`, virgüller ve tırnak işaretlerini mümkün olduğunca kaldırır

4. **Sıra Koruma**: Orijinal JSON anahtar sıralamasını korur

5. **İç İçe Destek**: İç içe diziler ve nesneleri tam olarak destekler

## Yapılandırma

### Yapılandırma Dosyasını Yayınlama

Paket ayarlarını özelleştirmek için yapılandırma dosyasını Laravel uygulamanıza yayınlamanız gerekir:

```bash
php artisan vendor:publish --tag=toon-config
```

Bu komut, Laravel projenizin `config` dizininde bir `config/toon.php` dosyası oluşturacaktır.

### Yapılandırma Dosyası Konumu

Yayınlama işleminden sonra, yapılandırma dosyası şu konumda bulunur:
```
config/toon.php
```

### Yapılandırma Seçenekleri

Yayınlanan yapılandırma dosyası aşağıdaki seçenekleri içerir:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Girinti
    |--------------------------------------------------------------------------
    |
    | TOON çıktısında girinti için kullanılan boşluk sayısı.
    |
    */
    'indentation' => 4,

    /*
    |--------------------------------------------------------------------------
    | Anahtar Ayırıcı
    |--------------------------------------------------------------------------
    |
    | TOON formatında anahtarlar arasında kullanılan ayırıcı.
    |
    */
    'key_separator' => ', ',

    /*
    |--------------------------------------------------------------------------
    | Satır Sonu
    |--------------------------------------------------------------------------
    |
    | TOON çıktısında kullanılan satır sonu karakteri.
    |
    */
    'line_break' => PHP_EOL,

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Etkinleştirildiğinde, çözümleme herhangi bir formatlama sorununda exception fırlatır.
    | Devre dışı bırakıldığında, daha esnek bir şekilde parse etmeye çalışır.
    |
    */
    'strict_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Sırayı Koru
    |--------------------------------------------------------------------------
    |
    | Çıktıda orijinal JSON anahtar sıralamasının korunup korunmayacağı.
    |
    */
    'preserve_order' => true,
];
```

### Yapılandırma Değerlerini Kullanma

Kodunuzda yapılandırma değerlerine şu şekilde erişebilirsiniz:

```php
use Illuminate\Support\Facades\Config;

$indentSize = config('toon.indent_size');
$preserveOrder = config('toon.preserve_order');
```

**Not:** Yapılandırma dosyası isteğe bağlıdır. Yayınlamazsanız, paket varsayılan değerleri kullanacaktır.

## Test

Test paketini çalıştırın:

```bash
composer test
# veya
vendor/bin/phpunit
```

## Örnekler

### Örnek 1: Basit Nesne

**Girdi (JSON):**
```json
{
  "id": 1,
  "name": "Laptop",
  "price": 1299.99
}
```

**Çıktı (TOON):**
```
id, name, price;
1, Laptop, 1299.99
```

### Örnek 2: Nesne Dizisi

**Girdi (JSON):**
```json
[
  {
    "id": 1,
    "customer": "Ali",
    "rating": 5
  }
]
```

**Çıktı (TOON):**
```
array[1]{
  id, customer, rating;
  1, Ali, 5
}
```

### Örnek 3: Karmaşık İç İçe Yapı

**Girdi (JSON):**
```json
{
  "product": "Akıllı Telefon",
  "reviews": [
    {"id": 1, "customer": "Mehmet", "rating": 5},
    {"id": 2, "customer": "Zeynep", "rating": 4}
  ]
}
```

**Çıktı (TOON):**
```
product, reviews;
Akıllı Telefon
reviews[2]{
  id, customer, rating;
  1, Mehmet, 5
  2, Zeynep, 4
}
```

## Sürüm

Mevcut sürüm: **v0.4.0**

Bu sürüm şunları içerir:
- ✅ JSON → TOON kodlama
- ✅ TOON → JSON çözümleme
- ✅ CLI komutları (encode & decode) renkli önizleme ile
- ✅ Global helper fonksiyonlar (`toon_encode`, `toon_decode`)
- ✅ Fluent interface (`fromJson`, `fromArray`, `fromToon`)
- ✅ Blade directive `@toon()` kolay şablon entegrasyonu için
- ✅ Laravel Debugbar entegrasyonu (otomatik algılanır)
- ✅ Log::toon() macro logging desteği için
- ✅ Syntax highlighting ile console styling
- ✅ Yapılandırılabilir formatlama (girinti, ayırıcılar, satır sonları)
- ✅ Satır numaraları ile iyileştirilmiş exception mesajları
- ✅ Facade ve DI desteği
- ✅ Kapsamlı test kapsamı
- ✅ Özel exception'larla hata yönetimi

## Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen bir Pull Request göndermekten çekinmeyin.

## Lisans

MIT Lisansı (MIT). Daha fazla bilgi için [Lisans Dosyasına](LICENSE) bakın.

## Krediler

[DigitalCoreHub](https://github.com/digitalcorehub) tarafından geliştirilmiştir

---

**Laravel topluluğu için ❤️ ile yapıldı**

