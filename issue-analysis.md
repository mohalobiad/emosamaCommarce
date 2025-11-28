# توضيح سبب استمرار الخطأ الحرج

ظهر الخطأ الحرج بعد إضافة دعم استيراد ملفات XLSX. مسار الاستيراد يستخدم الصنف `TPPLT_XLSX_Reader` الذي يرمي استثناءً غير مُعالج إذا لم يكن امتداد PHP `ZipArchive` مثبتاً أو إذا تعذر فتح ملف XLSX/قراءة أوراقه. عند حدوث هذا الاستثناء أثناء الاستيراد، ينتج عنه "Critical Error" في ووردبريس لأن الاستثناء لا يتم التقاطه في أي مكان آخر.

أهم نقطة فشل حاليّة:
- في `TPPLT_XLSX_Reader::get_rows()` يتم فحص امتداد `ZipArchive`، وإذا كان غير متوفر يتم رمي استثناء فوراً: «The PHP ZipArchive extension is required to parse XLSX imports.»【F:mystaging01/wp-content/plugins/alsaadrose-woocommerce-arabic/includes/class-tpplt-xlsx-importer.php†L40-L55】.
- أي استثناء لاحق في نفس الدالة (مثل عدم القدرة على فتح الملف أو عدم وجود أوراق) سيؤدي أيضاً إلى خطأ حرج لأن WooCommerce لا يحيط هذه الدعوات بـ try/catch.【F:mystaging01/wp-content/plugins/alsaadrose-woocommerce-arabic/includes/class-tpplt-xlsx-importer.php†L40-L120】

## ماذا تفعل الآن؟
1. **تأكد من تفعيل امتداد ZipArchive** على خادم PHP (غالباً عبر تمكينه في php.ini أو تثبيت الحزمة `php-zip`).
2. إذا لم تستطع تثبيت الامتداد، استخدم **ملفات CSV بدلاً من XLSX** لتفادي مسار الاستيراد الذي يرمي الاستثناء.
3. لتأكيد التشخيص، فعّل `WP_DEBUG` و `WP_DEBUG_LOG` في `wp-config.php` وأعد تنفيذ الاستيراد؛ ستجد رسالة الاستثناء في `wp-content/debug.log` توضح إن كان السبب هو غياب ZipArchive أو مشكلة في الملف.
