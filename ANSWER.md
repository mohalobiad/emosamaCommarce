# إجابة حول استيراد XLSX بدل CSV

## لماذا تعمل الإضافة فقط مع CSV؟
إضافة `alsaadrose-woocommerce-arabic` مبنية بالكامل على معرّفات WooCommerce الخاصة بمستورد ملفات **CSV**. الكود الحالي يضيف الحقول العربية إلى شاشة المطابقة الافتراضية عبر المرشّحات `woocommerce_csv_product_import_mapping_options` و`woocommerce_csv_product_import_mapping_default_columns`، ثم يحفظ البيانات أثناء عملية الاستيراد في المرشح `woocommerce_product_import_pre_insert_product_object`. كل هذه الخطوات تعتمد على مستورد WooCommerce القياسي الذي لا يقبل سوى CSV، لذلك لا يمكن تحميل ملف XLSX بشكل مباشر من خلال هذه الإضافة وحدها.【F:mystaging01/wp-content/plugins/alsaadrose-woocommerce-arabic/alsaadrose-woocommerce-arabic.php†L389-L440】

## ما الخيارات المتاحة لك؟
1. **تحويل XLSX إلى CSV قبل الاستيراد**: هذا هو الحل الأبسط ولا يحتاج أي تعديل برمجي. يمكنك تحويل الملف من Excel أو Google Sheets إلى CSV ثم استخدام المستورد المدمج كما تفعل الآن.
2. **توسيع الموقع لدعم XLSX** (تطوير مخصص): إن أردت قبول XLSX مباشرة، ستحتاج إلى واحد من الآتي:
   - تركيب إضافة أخرى تتكفّل بقراءة XLSX وتحويله إلى CSV تلقائيًا قبل تمريره إلى WooCommerce.
   - أو إنشاء صفحة/مهمة مخصصة داخل WordPress تقوم برفع ملف XLSX، قراءته بواسطة مكتبة مثل [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/) وتحويل محتواه إلى مصفوفة، ثم تمرير البيانات إلى كائنات `WC_Product` بنفس الطريقة التي يفعلها المرشح `tpplt_import_pre_insert_product_object`.

## الخلاصة
لا يمكنك استخدام XLSX مباشرة مع المستورد الحالي من دون خطوة تحويل أو تطوير إضافي. إن كنت تريد أقل جهد، قم بتحويل الملفات إلى CSV ثم استخدم الاستيراد المعتاد. أما إذا كانت لديك حاجة ملحّة لدعم XLSX، فالحل يتطلب إضافة جديدة أو تعديل برمجي يستخدم مكتبة لمعالجة XLSX قبل إدخال البيانات إلى WooCommerce.
