<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** من يدخل لوحة التحكم وما صلاحيته */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => User::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,staff',
        ], ['email.unique' => 'هذا البريد مستخدم مسبقاً', 'password.min' => 'كلمة المرور 8 أحرف على الأقل'], ['role' => 'الصلاحية']);

        User::create(['password' => Hash::make($data['password'])] + $data);

        return back()->with('ok', 'أضيف '.$data['name'].' بصلاحية '.User::ROLES[$data['role']]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => 'required|in:admin,staff', 'password' => 'nullable|string|min:8'], ['password.min' => 'كلمة المرور 8 أحرف على الأقل']);

        if ($data['role'] === 'staff' && $user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'يجب أن يبقى مدير واحد على الأقل.');
        }
        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('ok', 'حُفظت بيانات '.$user->name);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'لا يمكنك حذف حسابك وأنت داخل به.');
        }
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'يجب أن يبقى مدير واحد على الأقل.');
        }
        $user->delete();

        return back()->with('ok', 'حُذف المستخدم');
    }

    /** حسابي: متاح لكل من يدخل اللوحة */
    public function profile(Request $request)
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function profileUpdate(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة',
            'current_password.required_with' => 'اكتب كلمة المرور الحالية لتغييرها',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            'password.min' => 'كلمة المرور الجديدة 8 أحرف على الأقل',
        ]);
        $user->name = $data['name'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('ok', 'حُفظ حسابك');
    }
}
