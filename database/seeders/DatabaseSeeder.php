<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SearchSynonym;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // حساب المدير. كلمة المرور من ADMIN_PASSWORD في .env، وإن لم توجد تُولَّد عشوائياً وتُطبع مرة واحدة.
        $email = env('ADMIN_EMAIL', 'admin@aldalu.iq');
        if (! User::where('email', $email)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(12, symbols: false);
            User::create(['name' => 'مدير الصيدلية', 'email' => $email, 'password' => Hash::make($password)]);
            $this->command?->warn("حساب المدير: {$email}  |  كلمة المرور: {$password}");
        }

        if (Product::exists()) {
            return;
        }

        $cats = [];
        foreach ([
            ['أدوية', 'adwiya', 'pill', '#13838F'],
            ['فيتامينات ومكملات', 'vitamins', 'vitamin', '#D98A00'],
            ['الضغط والسكري', 'chronic', 'heart', '#C8442B'],
            ['العناية بالبشرة', 'skin-care', 'skin', '#B4577E'],
            ['الأم والطفل', 'mother-baby', 'baby', '#5B74C9'],
            ['العناية الشخصية', 'personal-care', 'care', '#3D8F5F'],
            ['أجهزة طبية', 'devices', 'device', '#1B4E8C'],
            ['إسعافات ومستلزمات', 'first-aid', 'firstaid', '#7A5A3C'],
        ] as $i => [$name, $slug, $icon, $color]) {
            $cats[$slug] = Category::create(['name' => $name, 'slug' => $slug, 'icon' => $icon, 'color' => $color, 'sort' => $i + 1])->id;
        }

        // [القسم، الاسم، الاسم العلمي، الشركة، الشكل، السعر، السعر قبل الخصم، الكمية، وصفة، مميز، وصف]
        $items = [
            ['adwiya', 'Panadol Extra 24 قرص', 'Paracetamol + Caffeine', 'GSK', 'أقراص', 2500, null, 150, 0, 1, 'مسكن للصداع وآلام الجسم وخافض للحرارة.'],
            ['adwiya', 'Panadol Advance 500mg 24 قرص', 'Paracetamol', 'GSK', 'أقراص', 2000, null, 200, 0, 0, 'مسكن وخافض حرارة سريع الامتصاص.'],
            ['adwiya', 'Paracetol 500mg 20 قرص', 'Paracetamol', 'سامراء SDI', 'أقراص', 750, null, 300, 0, 0, 'باراسيتامول إنتاج عراقي، مسكن وخافض حرارة.'],
            ['adwiya', 'Brufen 400mg 30 قرص', 'Ibuprofen', 'Abbott', 'أقراص', 3500, null, 90, 0, 0, 'مضاد التهاب ومسكن لآلام المفاصل والأسنان.'],
            ['adwiya', 'Profinal 400mg 20 قرص', 'Ibuprofen', 'Julphar', 'أقراص', 2250, null, 110, 0, 0, 'مسكن ومضاد التهاب.'],
            ['adwiya', 'Augmentin 1g 14 قرص', 'Amoxicillin + Clavulanic acid', 'GSK', 'أقراص', 12500, 14000, 45, 1, 1, 'مضاد حيوي واسع الطيف. يصرف بوصفة طبية.'],
            ['adwiya', 'Julmentin 1g 14 قرص', 'Amoxicillin + Clavulanic acid', 'Julphar', 'أقراص', 8500, null, 60, 1, 0, 'مضاد حيوي واسع الطيف. يصرف بوصفة طبية.'],
            ['adwiya', 'Zithromax 500mg 3 أقراص', 'Azithromycin', 'Pfizer', 'أقراص', 11000, null, 30, 1, 0, 'مضاد حيوي لالتهابات الجهاز التنفسي. يصرف بوصفة طبية.'],
            ['adwiya', 'Azi-Once 500mg 3 أقراص', 'Azithromycin', 'Hikma', 'أقراص', 6000, 7000, 55, 1, 0, 'مضاد حيوي. يصرف بوصفة طبية.'],
            ['adwiya', 'Nexium 40mg 14 قرص', 'Esomeprazole', 'AstraZeneca', 'أقراص', 13500, null, 35, 0, 0, 'لعلاج حموضة المعدة والارتجاع المريئي.'],
            ['adwiya', 'Omepral 20mg 14 كبسولة', 'Omeprazole', 'Pharma International', 'كبسول', 3000, null, 120, 0, 0, 'مثبط لإفراز حمض المعدة.'],
            ['adwiya', 'Gaviscon Advance شراب 300ml', 'Sodium alginate', 'Reckitt', 'شراب', 9500, 11000, 28, 0, 1, 'يخفف الحرقة وعسر الهضم بسرعة.'],
            ['adwiya', 'Buscopan 10mg 20 قرص', 'Hyoscine butylbromide', 'Sanofi', 'أقراص', 4000, null, 75, 0, 0, 'لتخفيف تقلصات البطن والمغص.'],
            ['adwiya', 'Flagyl 500mg 20 قرص', 'Metronidazole', 'Sanofi', 'أقراص', 3500, null, 60, 1, 0, 'مضاد للجراثيم والطفيليات. يصرف بوصفة طبية.'],
            ['adwiya', 'Claritine 10mg 10 أقراص', 'Loratadine', 'Bayer', 'أقراص', 5500, null, 80, 0, 0, 'مضاد للحساسية لا يسبب النعاس.'],
            ['adwiya', 'Zyrtec 10mg 20 قرص', 'Cetirizine', 'UCB', 'أقراص', 6500, null, 50, 0, 0, 'لحساسية الأنف والجلد.'],
            ['adwiya', 'Otrivin بخاخ أنف للبالغين 10ml', 'Xylometazoline', 'Haleon', 'بخاخ', 4500, null, 65, 0, 0, 'يزيل احتقان الأنف خلال دقائق.'],
            ['adwiya', 'Prospan شراب للسعال 100ml', 'Ivy leaf extract', 'Engelhard', 'شراب', 7500, null, 40, 0, 1, 'شراب عشبي للسعال مناسب للأطفال والكبار.'],
            ['adwiya', 'Voltaren Emulgel 1% 50g', 'Diclofenac', 'Haleon', 'جل', 6500, 7500, 70, 0, 0, 'جل موضعي لآلام العضلات والمفاصل.'],
            ['adwiya', 'Fucidin كريم 15g', 'Fusidic acid', 'LEO Pharma', 'كريم', 5000, null, 45, 0, 0, 'مضاد حيوي موضعي لالتهابات الجلد.'],
            ['adwiya', 'Systane Ultra قطرة عين 10ml', 'Polyethylene glycol', 'Alcon', 'قطرة', 9000, null, 22, 0, 0, 'قطرة مرطبة لجفاف العين.'],
            ['adwiya', 'Imodium 2mg 6 كبسولات', 'Loperamide', 'Janssen', 'كبسول', 3500, null, 0, 0, 0, 'لعلاج الإسهال الحاد.'],

            ['chronic', 'Glucophage 500mg 50 قرص', 'Metformin', 'Merck', 'أقراص', 4500, null, 100, 1, 0, 'لتنظيم سكر الدم. يصرف بوصفة طبية.'],
            ['chronic', 'Glucophage XR 1000mg 30 قرص', 'Metformin', 'Merck', 'أقراص', 8000, null, 60, 1, 0, 'ممتد المفعول لتنظيم سكر الدم. يصرف بوصفة طبية.'],
            ['chronic', 'Concor 5mg 30 قرص', 'Bisoprolol', 'Merck', 'أقراص', 6500, null, 85, 1, 1, 'لعلاج ارتفاع ضغط الدم. يصرف بوصفة طبية.'],
            ['chronic', 'Amlor 5mg 30 كبسولة', 'Amlodipine', 'Pfizer', 'كبسول', 9000, 10000, 40, 1, 0, 'لعلاج ارتفاع ضغط الدم. يصرف بوصفة طبية.'],
            ['chronic', 'Lipitor 20mg 30 قرص', 'Atorvastatin', 'Pfizer', 'أقراص', 16000, null, 30, 1, 0, 'لخفض الكولسترول. يصرف بوصفة طبية.'],
            ['chronic', 'Aspirin Protect 100mg 30 قرص', 'Acetylsalicylic acid', 'Bayer', 'أقراص', 3500, null, 130, 0, 0, 'جرعة منخفضة لوقاية القلب.'],
            ['chronic', 'Accu-Chek Active شرائط فحص السكر 50', null, 'Roche', 'مستلزمات', 22000, 25000, 35, 0, 1, 'شرائط لجهاز أكيو تشيك أكتيف.'],

            ['vitamins', 'Centrum Adults 30 قرص', 'Multivitamin', 'Haleon', 'أقراص', 18000, null, 40, 0, 1, 'فيتامينات ومعادن متكاملة للبالغين.'],
            ['vitamins', 'Vitamin D3 5000 IU 60 كبسولة', 'Cholecalciferol', 'Now Foods', 'كبسول', 14000, 16500, 55, 0, 1, 'لدعم العظام والمناعة.'],
            ['vitamins', 'Vitamin C 1000mg فوار 20 قرص', 'Ascorbic acid', 'Sunlife', 'فوار', 5500, null, 90, 0, 0, 'أقراص فوارة بطعم البرتقال.'],
            ['vitamins', 'Omega 3 1000mg 60 كبسولة', 'Fish oil', 'Nature Made', 'كبسول', 19500, null, 30, 0, 0, 'زيت السمك لصحة القلب.'],
            ['vitamins', 'Feroglobin 30 كبسولة', 'Iron + B12 + Folic acid', 'Vitabiotics', 'كبسول', 12000, null, 45, 0, 0, 'حديد لطيف على المعدة لعلاج فقر الدم.'],
            ['vitamins', 'Neurobion 30 قرص', 'Vitamin B1 + B6 + B12', 'P&G Health', 'أقراص', 6000, null, 70, 0, 0, 'فيتامينات ب لصحة الأعصاب.'],
            ['vitamins', 'Zinc 50mg 100 قرص', 'Zinc gluconate', 'Now Foods', 'أقراص', 11000, null, 4, 0, 0, 'لدعم المناعة وصحة الشعر.'],
            ['vitamins', 'Pregnacare Original 30 قرص', 'Prenatal multivitamin', 'Vitabiotics', 'أقراص', 15000, 17000, 38, 0, 0, 'فيتامينات للحامل تحتوي حمض الفوليك.'],

            ['skin-care', 'La Roche-Posay Effaclar غسول 200ml', null, 'La Roche-Posay', 'غسول', 28000, 32000, 18, 0, 1, 'غسول للبشرة الدهنية والمعرضة لحب الشباب.'],
            ['skin-care', 'CeraVe مرطب للوجه والجسم 340g', null, 'CeraVe', 'كريم', 26000, null, 25, 0, 1, 'مرطب بالسيراميد وحمض الهيالورونيك.'],
            ['skin-care', 'Bioderma Photoderm واقي شمس SPF50+', null, 'Bioderma', 'كريم', 30000, null, 15, 0, 0, 'حماية عالية من الشمس للبشرة الحساسة.'],
            ['skin-care', 'Bepanthen كريم 30g', 'Dexpanthenol', 'Bayer', 'كريم', 6500, null, 60, 0, 0, 'يرطب ويرمم البشرة الجافة والمتشققة.'],
            ['skin-care', 'Vichy Dercos شامبو ضد القشرة 200ml', null, 'Vichy', 'شامبو', 24000, 27000, 12, 0, 0, 'شامبو علاجي للقشرة.'],

            ['mother-baby', 'Aptamil 1 حليب أطفال 400g', null, 'Nutricia', 'حليب', 13500, null, 48, 0, 1, 'من الولادة حتى 6 أشهر.'],
            ['mother-baby', 'Novalac AC حليب للمغص 400g', null, 'Novalac', 'حليب', 14500, null, 26, 0, 0, 'تركيبة تخفف المغص والغازات.'],
            ['mother-baby', 'Sudocrem كريم الحفاض 125g', 'Zinc oxide', 'Teva', 'كريم', 8500, 9500, 50, 0, 0, 'يحمي من التهاب منطقة الحفاض.'],
            ['mother-baby', 'Pampers Premium Care مقاس 3 عدد 62', null, 'P&G', 'مستلزمات', 21000, null, 20, 0, 0, 'حفاضات ناعمة للأطفال 6 إلى 10 كغم.'],
            ['mother-baby', 'Adol شراب للأطفال 100ml', 'Paracetamol', 'Julphar', 'شراب', 2500, null, 95, 0, 0, 'خافض حرارة ومسكن للأطفال.'],

            ['personal-care', 'Sensodyne معجون أسنان 75ml', null, 'Haleon', 'أخرى', 5000, null, 85, 0, 0, 'للأسنان الحساسة.'],
            ['personal-care', 'Listerine Cool Mint غسول فم 250ml', null, 'Kenvue', 'غسول', 6000, null, 44, 0, 0, 'غسول فم مطهر بنكهة النعناع.'],
            ['personal-care', 'Oral-B فرشاة أسنان ناعمة', null, 'P&G', 'أخرى', 3000, null, 100, 0, 0, 'شعيرات ناعمة للثة الحساسة.'],
            ['personal-care', 'Dettol معقم يدين 200ml', null, 'Reckitt', 'أخرى', 4000, 5000, 75, 0, 0, 'يقضي على 99.9% من الجراثيم.'],

            ['devices', 'Omron M2 جهاز قياس الضغط', null, 'Omron', 'جهاز', 55000, 62000, 14, 0, 1, 'جهاز قياس ضغط رقمي من الذراع مع ضمان.'],
            ['devices', 'Accu-Chek Instant جهاز قياس السكر', null, 'Roche', 'جهاز', 30000, null, 16, 0, 1, 'نتيجة خلال 4 ثوان مع 10 شرائط.'],
            ['devices', 'Braun ThermoScan ميزان حرارة للأذن', null, 'Braun', 'جهاز', 68000, null, 6, 0, 0, 'قراءة دقيقة خلال ثوان.'],
            ['devices', 'Omron C28P جهاز تبخير', null, 'Omron', 'جهاز', 75000, 85000, 5, 0, 0, 'جهاز تبخير للربو والحساسية الصدرية.'],
            ['devices', 'Pulse Oximeter جهاز قياس الأوكسجين', null, 'Jumper', 'جهاز', 18000, null, 22, 0, 0, 'يقيس نسبة الأوكسجين والنبض من الإصبع.'],

            ['first-aid', 'كمامات طبية 3 طبقات عدد 50', null, 'Medica', 'مستلزمات', 3000, null, 200, 0, 0, 'كمامات تستعمل لمرة واحدة.'],
            ['first-aid', 'Betadine محلول مطهر 120ml', 'Povidone-iodine', 'Mundipharma', 'أخرى', 4500, null, 55, 0, 0, 'مطهر للجروح.'],
            ['first-aid', 'Hansaplast لاصق جروح عدد 20', null, 'Beiersdorf', 'مستلزمات', 2500, null, 140, 0, 0, 'لاصق طبي مقاوم للماء.'],
            ['first-aid', 'شاش معقم 10×10 عدد 10', null, 'Medica', 'مستلزمات', 1500, null, 180, 0, 0, 'شاش قطني معقم.'],
        ];

        foreach ($items as $n => [$cat, $name, $sci, $company, $form, $price, $old, $stock, $rx, $featured, $desc]) {
            Product::create([
                'category_id' => $cats[$cat], 'name' => $name, 'scientific_name' => $sci, 'company' => $company,
                'dosage_form' => $form, 'price' => $price, 'old_price' => $old, 'stock' => $stock,
                'requires_prescription' => (bool) $rx, 'is_featured' => (bool) $featured, 'description' => $desc,
                'barcode' => '62'.str_pad((string) (1000000 + $n * 7919), 11, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ([
            ['كيف أطلب من الموقع؟', 'أضف ما تحتاجه إلى السلة ثم اكتب اسمك ورقم موبايلك وعنوانك. لا تحتاج إلى حساب. يصل طلبك لفريق الصيدلية فوراً ونتصل بك للتأكيد.'],
            ['ما طرق الدفع المتاحة؟', 'الدفع نقداً عند الاستلام فقط. تدفع للمندوب بعد أن تستلم طلبك وتتأكد منه.'],
            ['كم يستغرق التوصيل وكم أجرته؟', 'داخل زيونة خلال ساعة تقريباً، وباقي مناطق بغداد من ساعتين إلى أربع ساعات. الأجرة تبدأ من 1,000 دينار حسب المنطقة وتظهر لك قبل تأكيد الطلب، والتوصيل مجاني للطلبات فوق 50,000 دينار.'],
            ['هل أحتاج وصفة طبية؟', 'بعض الأدوية، مثل المضادات الحيوية وأدوية الضغط والسكري، تصرف بوصفة فقط. ستجد عليها علامة «بوصفة طبية»، ونطلب منك إرفاق صورة الوصفة عند إتمام الطلب.'],
            ['لم أجد دوائي في الموقع، ماذا أفعل؟', 'ارسل صورة الوصفة من صفحة «اطلب بوصفة» أو راسل الصيدلانية على واتساب. نوفر أغلب الأدوية خلال يوم، ونقترح عليك البديل المتوفر بنفس المادة الفعالة.'],
            ['هل أستطيع استشارة الصيدلانية قبل الشراء؟', 'نعم، الاستشارة مجانية. اضغط زر «استشر الصيدلانية» في صفحة أي منتج وسيفتح واتساب برسالة جاهزة فيها اسم المنتج، فاكتب سؤالك فقط.'],
            ['هل الأدوية أصلية؟', 'كل أدويتنا من مذاخر ووكلاء معتمدين ومحفوظة حسب شروط الخزن. تاريخ الانتهاء مثبت على كل علبة ويمكنك التأكد منه عند الاستلام.'],
            ['هل يمكن إرجاع أو تبديل الطلب؟', 'يمكنك رفض الطلب عند الاستلام إذا لم يطابق ما طلبته. بعد الاستلام لا تُرجع الأدوية حفاظاً على سلامتها، أما الأجهزة فتُبدَّل خلال 3 أيام إذا كان فيها عيب.'],
        ] as $i => [$question, $answer]) {
            Faq::create(['question' => $question, 'answer' => $answer, 'sort' => $i + 1]);
        }

        // مرادفات تساعد من يبحث بالعرَض أو بالاسم الشائع
        foreach ([
            ['صداع، وجع راس، وجع رأس', 'paracetamol, ibuprofen'],
            ['حرارة، سخونة، حمى', 'paracetamol'],
            ['حموضة، حرقة، ارتجاع', 'omeprazole, esomeprazole, gaviscon'],
            ['حساسية، حكة، رشح', 'loratadine, cetirizine'],
            ['سعال، كحة، قحة', 'prospan'],
            ['مغص، تقلصات', 'buscopan'],
            ['اسهال', 'imodium'],
            ['مضاد حيوي، انتي بايوتك', 'amoxicillin, azithromycin'],
            ['فقر دم، حديد', 'feroglobin'],
            ['مسكن', 'paracetamol, ibuprofen, diclofenac'],
            ['ضغط الدم', 'bisoprolol, amlodipine, omron'],
            ['سكر، سكري', 'metformin, accu'],
            ['واقي شمس، صن بلوك', 'photoderm'],
            ['حفاضات', 'pampers'],
        ] as [$term, $expandsTo]) {
            SearchSynonym::create(['term' => $term, 'expands_to' => $expandsTo]);
        }

        // تواريخ انتهاء تجريبية ليظهر تنبيه الصلاحية في اللوحة (واحد منتهٍ فيختفي من المتجر)
        foreach ([[4, 25], [17, 48], [33, 70], [45, 85], [58, 400], [2, 600], [50, -6]] as [$offset, $days]) {
            Product::orderBy('id')->skip($offset)->first()?->update(['expires_at' => today()->addDays($days)]);
        }

        // كوبونات تجريبية
        \App\Models\Coupon::create(['code' => 'DALU10', 'type' => 'percent', 'value' => 10, 'min_total' => 10000]);
        \App\Models\Coupon::create(['code' => 'WELCOME2000', 'type' => 'fixed', 'value' => 2000, 'min_total' => 15000, 'max_uses' => 100, 'expires_at' => today()->addMonths(3)]);

        // موظفون تجريبيون، استبدلهم بأرقام موظفيك من لوحة التحكم
        foreach ([['د. أحمد الدلو', '07700000001', 'صيدلاني مسؤول'], ['زينب كريم', '07800000002', 'مساعد صيدلاني'], ['علي حسين', '07500000003', 'مندوب توصيل']] as [$name, $phone, $role]) {
            Staff::create(compact('name', 'phone', 'role'));
        }
    }
}
