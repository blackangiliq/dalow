(() => {
  const toast = document.querySelector('[data-toast]');
  let timer;
  const showToast = (text, bad = false, withCart = false) => {
    if (!toast) return;
    toast.textContent = text;
    toast.classList.toggle('bad', bad);
    if (withCart) {
      const a = document.createElement('a');
      a.href = toast.dataset.cartUrl;
      a.textContent = 'عرض السلة';
      toast.append(a);
    }
    toast.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(() => toast.classList.remove('show'), 4200);
  };
  if (toast?.dataset.initial) showToast(toast.dataset.initial, toast.classList.contains('bad'));

  // إضافة للسلة بدون إعادة تحميل الصفحة
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.js-add');
    if (!form) return;
    e.preventDefault();
    const btn = form.querySelector('button');
    btn.disabled = true;
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      if (!res.ok) throw new Error();
      const data = await res.json();
      document.querySelectorAll('[data-cart-count]').forEach((el) => (el.textContent = data.count));
      showToast(data.message, !data.ok, data.ok);
    } catch {
      showToast('تعذرت الإضافة. تحقق من الاتصال وحاول مرة أخرى.', true);
    } finally {
      btn.disabled = false;
    }
  });

  // اقتراحات البحث
  document.querySelectorAll('form[data-suggest]').forEach((form) => {
    const input = form.querySelector('input[type=search]');
    const box = document.createElement('div');
    box.className = 'suggest';
    box.hidden = true;
    form.append(box);
    let t, last = '';
    input.addEventListener('input', () => {
      clearTimeout(t);
      const q = input.value.trim();
      if (q.length < 2) { box.hidden = true; return; }
      t = setTimeout(async () => {
        last = q;
        try {
          const res = await fetch(form.dataset.suggest + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
          const items = await res.json();
          if (last !== q) return;
          box.replaceChildren(...items.map((it) => {
            const a = document.createElement('a');
            a.href = it.url;
            const left = document.createElement('span');
            left.textContent = it.name;
            if (it.sub) { const s = document.createElement('small'); s.textContent = it.sub; left.append(s); }
            const price = document.createElement('b');
            price.textContent = it.price;
            a.append(left, price);
            return a;
          }));
          box.hidden = items.length === 0;
        } catch { box.hidden = true; }
      }, 220);
    });
    document.addEventListener('click', (e) => { if (!form.contains(e.target)) box.hidden = true; });
    input.addEventListener('keydown', (e) => { if (e.key === 'Escape') box.hidden = true; });
  });

  // عداد الكمية
  document.querySelectorAll('.qty').forEach((q) => {
    const input = q.querySelector('input');
    q.addEventListener('click', (e) => {
      const step = e.target.dataset.step;
      if (!step) return;
      const max = Number(input.max) || 99;
      input.value = Math.min(max, Math.max(1, Number(input.value || 1) + Number(step)));
    });
  });

  // أجور التوصيل حسب المنطقة في صفحة إتمام الطلب
  const area = document.querySelector('[data-area]');
  if (area) {
    const fmt = (n) => Number(n).toLocaleString('en-US') + ' د.ع';
    const subtotal = Number(area.dataset.subtotal);
    const free = Number(area.dataset.free) || 0;
    const feeEl = document.querySelector('[data-fee-out]');
    const totalEl = document.querySelector('[data-total-out]');
    const update = () => {
      const opt = area.selectedOptions[0];
      if (!opt || !opt.value) { feeEl.textContent = 'اختر المنطقة'; totalEl.textContent = fmt(subtotal); return; }
      const fee = free && subtotal >= free ? 0 : Number(opt.dataset.fee);
      feeEl.textContent = fee ? fmt(fee) : 'مجاني';
      totalEl.textContent = fmt(subtotal + fee);
    };
    area.addEventListener('change', update);
    update();
  }

  // اسم ملف الوصفة بعد اختياره
  document.querySelectorAll('.drop input[type=file]').forEach((input) => {
    input.addEventListener('change', () => {
      const b = input.closest('.drop').querySelector('b');
      if (input.files[0]) b.textContent = 'تم اختيار: ' + input.files[0].name;
    });
  });
})();
