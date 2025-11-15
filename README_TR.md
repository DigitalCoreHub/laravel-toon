# Laravel Toon

Standart JSON'u **TOON** formatına dönüştüren hafif bir Laravel paketi - insan tarafından okunabilir, ultra-minimal, satır tabanlı bir veri formatı.

[![Son Sürüm](https://img.shields.io/badge/sürüm-0.1.0-mavi.svg)](https://github.com/digitalcorehub/laravel-toon)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-kırmızı.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-mavi.svg)](https://php.net)

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

## CLI Komutu

JSON dosyalarını TOON formatına dönüştürmek için Artisan komutunu kullanın:

```bash
php artisan toon:encode input.json output.toon
```

**Örnek:**

```bash
# Bir JSON dosyasını dönüştür
php artisan toon:encode storage/data.json storage/data.toon

# Komut şunları yapacak:
# - input.json'dan JSON okur
# - TOON formatına dönüştürür
# - output.toon'a kaydeder
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
    | Girinti Boyutu
    |--------------------------------------------------------------------------
    |
    | TOON çıktısında girinti için kullanılan boşluk sayısı.
    |
    */
    'indent_size' => 2,

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

Mevcut sürüm: **v0.1.0**

Bu sürüm şunları içerir:
- ✅ JSON → TOON kodlama
- ✅ CLI komut desteği
- ✅ Facade ve DI desteği
- ✅ Temel test kapsamı

**Not:** TOON → JSON çözümleme gelecekteki bir sürümde kullanılabilir olacak.

## Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen bir Pull Request göndermekten çekinmeyin.

## Lisans

MIT Lisansı (MIT). Daha fazla bilgi için [Lisans Dosyasına](LICENSE) bakın.

## Krediler

[DigitalCoreHub](https://github.com/digitalcorehub) tarafından geliştirilmiştir

---

**Laravel topluluğu için ❤️ ile yapıldı**

