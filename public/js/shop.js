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
      document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = data.count;
        el.hidden = false;
        el.classList.remove('bump');
        void el.offsetWidth;
        el.classList.add('bump');
      });
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

  // تناثر الكبسولات من موضع النقر أو اللمس
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (document.body.dataset.capsules === '1' && !calm && 'animate' in Element.prototype) {
    const layer = document.createElement('div');
    layer.className = 'capsule-layer';
    layer.setAttribute('aria-hidden', 'true');
    document.body.append(layer);
    const colors = [['#d8452b', '#ffffff'], ['#13838f', '#f4b63f'], ['#0b2545', '#5ed3d9'], ['#f4b63f', '#ffffff'], ['#5ed3d9', '#ffffff'], ['#1faa59', '#ffffff']];
    const rand = (a, b) => a + Math.random() * (b - a);
    let live = 0;
    addEventListener('pointerdown', (e) => {
      if (e.button > 0 || live > 70) return;
      const count = e.pointerType === 'touch' ? 10 : 12;
      for (let i = 0; i < count; i++) {
        const el = document.createElement('span');
        const tablet = Math.random() < 0.22;
        const w = tablet ? rand(10, 15) : rand(18, 28);
        const [a, b] = colors[Math.floor(Math.random() * colors.length)];
        el.className = 'capsule' + (tablet ? ' capsule--tab' : '');
        el.style.cssText = `left:${e.clientX}px;top:${e.clientY}px;width:${w}px;height:${tablet ? w : w * 0.44}px;background:` +
          (tablet ? a : `linear-gradient(90deg, ${a} 50%, ${b} 50%)`);
        const angle = (Math.PI * 2 * i) / count + rand(-0.35, 0.35);
        const reach = rand(56, 140);
        const dx = Math.cos(angle) * reach;
        const dy = Math.sin(angle) * reach - 26;
        const spin = rand(-420, 420);
        layer.append(el);
        live++;
        el.animate([
          { transform: 'translate(-50%, -50%) rotate(0deg) scale(.3)', opacity: 1 },
          { transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) rotate(${spin * 0.6}deg) scale(1)`, opacity: 1, offset: 0.42, easing: 'cubic-bezier(.4, 0, .9, .6)' },
          { transform: `translate(calc(-50% + ${dx * 1.18}px), calc(-50% + ${dy + rand(70, 130)}px)) rotate(${spin}deg) scale(.85)`, opacity: 0 },
        ], { duration: rand(650, 950), easing: 'cubic-bezier(.15, .8, .3, 1)' }).onfinish = () => { el.remove(); live--; };
      }
    }, { passive: true });
  }

  // اسم ملف الوصفة بعد اختياره
  document.querySelectorAll('.drop input[type=file]').forEach((input) => {
    input.addEventListener('change', () => {
      const b = input.closest('.drop').querySelector('b');
      if (input.files[0]) b.textContent = 'تم اختيار: ' + input.files[0].name;
    });
  });
})();
