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

  // الوضع الداكن والفاتح
  document.querySelectorAll('[data-theme-toggle]').forEach((btn) => btn.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('theme', next); } catch {}
  }));

  // المفضلة (أعلمني عند انخفاض السعر) بدون إعادة تحميل الصفحة
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.js-fav');
    if (!form) return;
    e.preventDefault();
    const btn = form.querySelector('button');
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      const data = await res.json();
      if (res.status === 401 && data.login) { location.href = data.login; return; }
      if (!res.ok) throw new Error();
      btn.classList.toggle('on', data.on);
      const label = btn.querySelector('[data-fav-label] span, span');
      if (btn.hasAttribute('data-fav-label') && label) label.textContent = data.on ? 'سنعلمك عند انخفاض السعر' : 'أعلمني عند انخفاض السعر';
      document.querySelectorAll('[data-fav-count]').forEach((el) => { el.textContent = data.count; el.hidden = !data.count; });
      showToast(data.message);
    } catch {
      showToast('تعذر الحفظ. تحقق من الاتصال وحاول مرة أخرى.', true);
    }
  });

  // ---------- إحصاءات مجهولة: ما يُنقر عليه وما يُشاهَد ----------
  const trackUrl = document.body.dataset.trackUrl;
  const csrf = document.querySelector('meta[name=csrf-token]')?.content;
  if (trackUrl && csrf) {
    let queue = [];
    const flush = () => {
      if (!queue.length) return;
      const data = new FormData();
      data.append('_token', csrf);
      data.append('events', JSON.stringify(queue.splice(0, 25)));
      if (!navigator.sendBeacon || !navigator.sendBeacon(trackUrl, data)) {
        fetch(trackUrl, { method: 'POST', body: data, keepalive: true }).catch(() => {});
      }
    };
    // النقرات على العناصر المعلّمة
    document.addEventListener('click', (e) => {
      const el = e.target.closest('[data-track]');
      if (!el) return;
      queue.push({ k: 'click', n: el.dataset.track });
      if (queue.length >= 5) flush();
    }, true);
    document.addEventListener('submit', (e) => {
      const el = e.target.closest('form[data-track]');
      if (el) queue.push({ k: 'click', n: el.dataset.track });
    }, true);
    // زمن بقاء كل قسم ظاهراً أمام الزائر: مؤشر على ما يلفت انتباهه
    const seen = new Map();
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        const now = performance.now();
        entries.forEach((entry) => {
          const state = seen.get(entry.target);
          if (entry.isIntersecting && state.since === null) state.since = now;
          else if (!entry.isIntersecting && state.since !== null) { state.total += now - state.since; state.since = null; }
        });
      }, { threshold: 0.4 });
      document.querySelectorAll('[data-section]').forEach((el) => { seen.set(el, { since: null, total: 0 }); io.observe(el); });
    }
    const report = () => {
      const now = performance.now();
      seen.forEach((state, el) => {
        const total = state.total + (state.since !== null ? now - state.since : 0);
        state.total = 0;
        state.since = state.since !== null ? now : null;
        if (total >= 2000) queue.push({ k: 'dwell', n: el.dataset.section, v: Math.round(total / 1000) });
      });
      flush();
    };
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') report(); });
    addEventListener('pagehide', report);
  }

  // مسح الباركود بكاميرا الموبايل (في المتصفحات التي تدعمه)
  const scanBtn = document.querySelector('[data-scan]');
  if (scanBtn && 'BarcodeDetector' in window && navigator.mediaDevices?.getUserMedia) {
    const box = document.querySelector('[data-scanner]');
    const video = box.querySelector('video');
    const note = document.querySelector('[data-scan-note]');
    scanBtn.hidden = false;
    let stream = null;
    const stop = () => { stream?.getTracks().forEach((t) => t.stop()); stream = null; box.hidden = true; };
    scanBtn.addEventListener('click', async () => {
      if (stream) { stop(); return; }
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        video.srcObject = stream;
        box.hidden = false;
        await video.play();
        const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128'] });
        const tick = async () => {
          if (!stream) return;
          try {
            const codes = await detector.detect(video);
            if (codes.length) {
              document.querySelector('#code').value = codes[0].rawValue;
              stop();
              document.querySelector('#code').form.submit();
              return;
            }
          } catch {}
          setTimeout(tick, 350);
        };
        tick();
      } catch {
        stop();
        note.textContent = 'لم نتمكن من فتح الكاميرا. اسمح للموقع باستعمالها، أو اكتب الأرقام يدوياً.';
      }
    });
  }

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
    const subtotal = Number(area.dataset.subtotal); // بعد خصم الكود إن وُجد
    const free = Number(area.dataset.free) || 0;
    const feeEl = document.querySelector('[data-fee-out]');
    const totalEl = document.querySelector('[data-total-out]');
    const address = document.querySelector('[data-address]');
    const optional = document.querySelector('[data-address-optional]');
    const update = () => {
      const opt = area.selectedOptions[0];
      // عند الاستلام من الصيدلية لا نطلب عنواناً
      const pickup = !!opt && opt.value !== '' && opt.value === area.dataset.pickup;
      if (address) address.required = !pickup;
      if (optional) optional.hidden = !pickup;
      if (!opt || !opt.value) { feeEl.textContent = 'اختر المنطقة'; totalEl.textContent = fmt(subtotal); return; }
      const fee = free && subtotal >= free ? 0 : Number(opt.dataset.fee);
      feeEl.textContent = fee ? fmt(fee) : (pickup ? 'بدون أجور' : 'مجاني');
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
