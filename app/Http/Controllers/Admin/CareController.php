<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HealthController;
use App\Models\HealthDocument;
use App\Models\Order;
use App\Models\RefillReminder;
use App\Services\CareService;
use App\Support\Ui;
use Illuminate\Http\Request;

/** العناية بالزبائن: تحاليل بانتظار القراءة، تذكيرات إعادة الشراء، والمتابعة بعد التسليم */
class CareController extends Controller
{
    public function index(CareService $care)
    {
        $care->sendDueReminders();

        return view('admin.care', [
            'documents' => Ui::on('health_documents')
                ? HealthDocument::with('profile.customer')->whereNull('reviewed_at')->oldest()->get() : collect(),
            'upcoming' => Ui::on('refill_reminders')
                ? RefillReminder::with(['customer', 'product'])->where('is_active', true)->where('next_at', '<=', today()->addDays(3))->orderBy('next_at')->get() : collect(),
            'reminded' => Ui::on('refill_reminders')
                ? RefillReminder::with(['customer', 'product'])->where('last_reminded_at', '>=', now()->subDays(7))->latest('last_reminded_at')->take(30)->get() : collect(),
            // سُلّمت قبل يومين إلى سبعة أيام ولم يُتابَع صاحبها بعد
            'followups' => Ui::on('followup')
                ? Order::with('healthProfile')->where('status', 'delivered')->whereNull('followed_up_at')
                    ->whereBetween('updated_at', [now()->subDays(7), now()->subDays(2)])->latest('updated_at')->take(40)->get() : collect(),
        ]);
    }

    public function documentFile(HealthDocument $document)
    {
        return HealthController::stream($document);
    }

    public function review(Request $request, HealthDocument $document, CareService $care)
    {
        $data = $request->validate(['pharmacist_note' => 'required|string|min:3|max:2000'], ['pharmacist_note.required' => 'اكتب قراءتك أو ردك على الزبون'], ['pharmacist_note' => 'الرد']);
        $document->update($data + ['reviewed_at' => now(), 'reviewed_by' => $request->user()->name]);
        $care->documentReviewed($document);

        return back()->with('ok', 'أُرسل ردك للزبون، ووصله إشعار.');
    }

    public function followedUp(Order $order)
    {
        $order->update(['followed_up_at' => now()]);

        return back()->with('ok', 'سُجلت المتابعة للطلب '.$order->number);
    }
}
