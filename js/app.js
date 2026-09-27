// Lógica compartida del prototipo El Anfitrión.
(function () {
  const D = window.ANFITRION;

  // ---------- Utilidades ----------
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const money = (n) => "$ " + Math.round(n).toLocaleString("es-AR");
  const initials = (nombre) => nombre.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase();
  const stars = (r) => "★".repeat(Math.round(r)) + "☆".repeat(5 - Math.round(r));
  const ratingFmt = (r) => r.toFixed(1).replace(".", ",");
  const param = (k) => new URLSearchParams(location.search).get(k);

  function toast(msg) {
    let el = $(".toast");
    if (!el) { el = document.createElement("div"); el.className = "toast"; document.body.appendChild(el); }
    el.textContent = msg;
    el.classList.add("is-visible");
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove("is-visible"), 2800);
  }

  // ---------- Header ----------
  const toggle = $(".nav-toggle");
  if (toggle) toggle.addEventListener("click", () => $(".nav").classList.toggle("is-open"));

  // ---------- Experiencias (landing) ----------
  function cardHTML(e) {
    return `
      <article class="card" data-cat="${e.categoria}">
        <a href="experiencia.html?id=${e.id}" class="card__media" style="background-image:url('${e.imagen}')">
          <span class="card__tag">${e.tipo} · ${e.lugar}</span>
        </a>
        <div class="card__body">
          <div class="card__meta">
            <span class="stars">${stars(e.rating)}<b>${ratingFmt(e.rating)}</b></span>
            <span>${e.opiniones} opiniones</span>
          </div>
          <h3 class="card__title"><a href="experiencia.html?id=${e.id}">${e.titulo}</a></h3>
          <p class="card__text">${e.resumen}</p>
          <div class="card__foot">
            <div class="card__host"><span class="avatar">${initials(e.anfitrion.nombre)}</span><span>${e.anfitrion.nombre}</span></div>
            <div class="price">${money(e.precio)} <small>por persona</small></div>
          </div>
        </div>
      </article>`;
  }

  const grid = $("#grid-experiencias");
  if (grid) {
    const chips = $("#chips");
    chips.innerHTML = D.categorias.map((c) => `<button class="chip${c.id === "todas" ? " is-active" : ""}" data-cat="${c.id}">${c.nombre}</button>`).join("");
    const render = (cat) => {
      const list = D.experiencias.filter((e) => cat === "todas" || e.categoria === cat);
      grid.innerHTML = list.length ? list.map(cardHTML).join("") : `<div class="empty">Todavía no hay experiencias en esta categoría. ¿Querés ser el primero en publicar una?</div>`;
    };
    chips.addEventListener("click", (ev) => {
      const b = ev.target.closest(".chip");
      if (!b) return;
      $$(".chip", chips).forEach((c) => c.classList.toggle("is-active", c === b));
      render(b.dataset.cat);
    });
    const initial = param("cat") && D.categorias.some((c) => c.id === param("cat")) ? param("cat") : "todas";
    $$(".chip", chips).forEach((c) => c.classList.toggle("is-active", c.dataset.cat === initial));
    render(initial);

    // Buscador: filtra por categoría y desplaza a la grilla.
    const search = $("#search-form");
    if (search) {
      const sel = $("#search-cat");
      sel.innerHTML = D.categorias.map((c) => `<option value="${c.id}">${c.nombre}</option>`).join("");
      search.addEventListener("submit", (ev) => {
        ev.preventDefault();
        const cat = sel.value;
        $$(".chip", chips).forEach((c) => c.classList.toggle("is-active", c.dataset.cat === cat));
        render(cat);
        $("#experiencias").scrollIntoView({ behavior: "smooth" });
      });
    }
  }

  // ---------- Calculadora de ingresos ----------
  const calc = $("#calc");
  if (calc) {
    const range = $("#calc-precio");
    const plansEl = $("#calc-planes");
    let plan = D.planes[0];
    plansEl.innerHTML = D.planes.map((p) => `<button type="button" data-plan="${p.id}"${p.id === plan.id ? ' class="is-active"' : ""}>${p.nombre} · ${Math.round(p.comision * 100)}%</button>`).join("");
    const update = () => {
      const precio = Number(range.value);
      const pct = ((precio - range.min) / (range.max - range.min)) * 100;
      range.style.setProperty("--pct", pct + "%");
      const com = precio * plan.comision;
      $("#calc-precio-label").textContent = money(precio);
      $("#calc-cobrado").textContent = money(precio);
      $("#calc-comision-pct").textContent = Math.round(plan.comision * 100) + "%";
      $("#calc-comision").textContent = money(com);
      $("#calc-neto").textContent = money(precio - com);
      $("#calc-plan-nombre").textContent = plan.nombre;
    };
    range.addEventListener("input", update);
    plansEl.addEventListener("click", (ev) => {
      const b = ev.target.closest("button");
      if (!b) return;
      plan = D.planes.find((p) => p.id === b.dataset.plan);
      $$("button", plansEl).forEach((x) => x.classList.toggle("is-active", x === b));
      update();
    });
    update();
  }

  // ---------- Planes ----------
  const plans = $("#planes");
  if (plans) {
    plans.innerHTML = D.planes.map((p) => `
      <article class="plan${p.destacado ? " plan--featured" : ""}">
        ${p.destacado ? '<span class="plan__badge">Recomendado</span>' : ""}
        <div class="plan__kind">${p.tipo}</div>
        <h3 class="plan__name">${p.nombre}</h3>
        <div class="plan__price">${money(p.precio)}<small>por mes</small></div>
        <div class="plan__fee">${Math.round(p.comision * 100)}% por reserva</div>
        <ul>${p.items.map((i) => `<li>${i}</li>`).join("")}</ul>
        <a class="btn ${p.destacado ? "btn--primary" : "btn--outline"}" href="registro.html?plan=${p.id}">Elegir ${p.nombre}</a>
      </article>`).join("");
  }

  // ---------- Testimonios ----------
  const quotes = $("#testimonios");
  if (quotes) {
    quotes.innerHTML = D.testimonios.map((t) => `
      <figure class="quote">
        <p>“${t.texto}”</p>
        <footer><span class="avatar">${initials(t.nombre)}</span><span><strong>${t.nombre}</strong><br>${t.rol}</span></footer>
      </figure>`).join("");
  }

  // ---------- Detalle de experiencia ----------
  const detail = $("#detalle");
  if (detail) {
    const e = D.experiencias.find((x) => x.id === param("id")) || D.experiencias[0];
    document.title = `${e.titulo} · El Anfitrión`;
    $("#d-hero").style.backgroundImage = `url('${e.imagen}')`;
    $("#d-tipo").textContent = `${e.tipo} · ${e.lugar}`;
    $("#d-titulo").textContent = e.titulo;
    $("#d-meta").innerHTML = `<span>${stars(e.rating)} ${ratingFmt(e.rating)} · ${e.opiniones} opiniones</span><span>⏱ ${e.duracion}</span><span>👥 Hasta ${e.cupos} personas</span>`;
    $("#d-desc").textContent = e.descripcion;
    $("#d-incluye").innerHTML = e.incluye.map(([k, v]) => `<li><strong>${k}</strong><span>${v}</span></li>`).join("");
    $("#d-host").innerHTML = `<span class="avatar">${initials(e.anfitrion.nombre)}</span><div><strong>${e.anfitrion.nombre}</strong> · Anfitrión desde ${e.anfitrion.desde}<p>${e.anfitrion.bio}</p></div>`;
    $("#d-reviews").innerHTML = e.reviews.map((r) => `
      <div class="review"><header><strong>${r.nombre}</strong><span>${r.fecha} · <span class="stars">${stars(r.rating)}</span></span></header><p>${r.texto}</p></div>`).join("");
    $("#b-precio").innerHTML = `${money(e.precio)} <small>por persona</small>`;
    const fecha = $("#b-fecha");
    fecha.innerHTML = e.fechas.map((f) => `<option>${f}</option>`).join("");
    const personas = $("#b-personas");
    personas.innerHTML = Array.from({ length: e.cupos }, (_, i) => `<option value="${i + 1}">${i + 1} ${i ? "personas" : "persona"}</option>`).join("");
    personas.value = 2;
    $("#b-cupos").textContent = `Quedan ${e.cupos - 2} lugares en esta fecha.`;
    const updateBook = () => {
      const n = Number(personas.value);
      const sub = e.precio * n;
      const servicio = sub * 0.05;
      $("#b-sub").textContent = `${money(e.precio)} × ${n}`;
      $("#b-sub-val").textContent = money(sub);
      $("#b-serv").textContent = money(servicio);
      $("#b-total").textContent = money(sub + servicio);
    };
    personas.addEventListener("change", updateBook);
    updateBook();
    $("#book-form").addEventListener("submit", (ev) => {
      ev.preventDefault();
      const n = personas.value;
      $("#book-form").innerHTML = `
        <div class="success">
          <div class="success__icon">✓</div>
          <h3 style="font-size:28px;margin-bottom:8px">Reserva enviada</h3>
          <p style="color:var(--muted);margin:0">Pedido de ${n} ${n > 1 ? "lugares" : "lugar"} para <strong>${fecha.value}</strong>. ${e.anfitrion.nombre.split(" ")[0]} confirma dentro de las 24 h y recién ahí se cobra.</p>
          <p style="font-size:13px;color:var(--muted);margin-top:14px">Prototipo: no se realizó ningún cobro real.</p>
        </div>`;
      toast("Reserva enviada al anfitrión");
    });
  }

  // ---------- Registro de anfitrión ----------
  const wizard = $("#wizard");
  if (wizard) {
    const steps = $$(".wizard__step");
    const prog = $$(".wizard__progress div");
    const data = { plan: param("plan") || "free" };
    let i = 0;

    const planSel = $("#r-plan");
    if (planSel) {
      planSel.innerHTML = D.planes.map((p) => `<option value="${p.id}">${p.nombre} · ${money(p.precio)} por mes · ${Math.round(p.comision * 100)}% por reserva</option>`).join("");
      planSel.value = D.planes.some((p) => p.id === data.plan) ? data.plan : "free";
    }
    const catSel = $("#r-categoria");
    if (catSel) catSel.innerHTML = D.categorias.filter((c) => c.id !== "todas").map((c) => `<option value="${c.id}">${c.nombre}</option>`).join("");

    const show = (n) => {
      i = n;
      steps.forEach((s, k) => (s.hidden = k !== n));
      prog.forEach((p, k) => { p.classList.toggle("is-done", k < n); p.classList.toggle("is-active", k === n); });
      window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const validate = (step) => {
      let ok = true;
      $$("[required]", step).forEach((f) => {
        const valid = f.type === "checkbox" ? f.checked : f.value.trim() !== "";
        f.classList.toggle("is-invalid", !valid);
        if (!valid) ok = false;
      });
      const err = $(".error", step);
      if (err) err.style.display = ok ? "none" : "block";
      return ok;
    };

    const collect = (step) => {
      $$("input, select, textarea", step).forEach((f) => {
        if (!f.name) return;
        data[f.name] = f.type === "checkbox" ? f.checked : f.value;
      });
    };

    const fillSummary = () => {
      const plan = D.planes.find((p) => p.id === data.plan) || D.planes[0];
      const cat = D.categorias.find((c) => c.id === data.categoria);
      const precio = Number(data.precio || 0);
      const neto = precio - precio * plan.comision;
      $("#r-summary").innerHTML = [
        ["Anfitrión", `${data.nombre} · ${data.ciudad}`],
        ["Experiencia", data.titulo],
        ["Categoría", cat ? cat.nombre : "-"],
        ["Precio por persona", money(precio)],
        ["Cupos", `${data.cupos} personas`],
        ["Plan", `${plan.nombre} · ${Math.round(plan.comision * 100)}% por reserva`],
        ["Recibís por persona*", money(neto)]
      ].map(([k, v]) => `<div><span>${k}</span><strong>${v}</strong></div>`).join("");
    };

    wizard.addEventListener("click", (ev) => {
      const next = ev.target.closest("[data-next]");
      const prev = ev.target.closest("[data-prev]");
      if (next) {
        const step = steps[i];
        if (!validate(step)) return;
        collect(step);
        if (i === 2) fillSummary();
        show(i + 1);
      }
      if (prev) show(i - 1);
    });

    $("#r-precio")?.addEventListener("input", (ev) => {
      const plan = D.planes.find((p) => p.id === (planSel ? planSel.value : "free"));
      const v = Number(ev.target.value || 0);
      $("#r-precio-hint").textContent = v ? `Con el plan ${plan.nombre} recibís ${money(v - v * plan.comision)} por persona.` : "Definí un precio para ver cuánto recibís.";
    });
    planSel?.addEventListener("change", () => $("#r-precio")?.dispatchEvent(new Event("input")));

    $("#r-publicar")?.addEventListener("click", () => {
      const step = steps[i];
      if (!validate(step)) return;
      collect(step);
      try { localStorage.setItem("anfitrion.registro", JSON.stringify(data)); } catch (_) {}
      show(4);
      toast("Tu experiencia quedó publicada");
    });

    show(0);
  }
})();
