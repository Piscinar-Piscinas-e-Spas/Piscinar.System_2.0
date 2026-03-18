
    const API_LIST = "list.php";
    const API_SAVE = "save.php";
    const DEBOUNCE_MS = 900;

    let qr = null;
    let running = false;
    let lastScanAt = 0;
    let selectedCameraId = null;

    // códigos existentes no arquivo
    let existing = new Set();

    // códigos já mostrados na lista (para não duplicar na UI)
    // Map: code -> <li>
    const seenUi = new Map();

    const elLast = document.getElementById("lastCode");
    const elList = document.getElementById("list");

    function normalize(code) {
    return String(code)
        .replace(/\u0000/g, "")
        .replace(/\s+/g, "")
        .trim();
    }

    function pulse(li) {
      li.classList.add("pulse");
      setTimeout(() => li.classList.remove("pulse"), 220);
    }

    async function loadExisting() {
      const res = await fetch(API_LIST, { cache: "no-store" });
      const data = await res.json();
      existing = new Set((data.codes || []).map(normalize).filter(Boolean));
    }

    async function saveCode(code) {
      const body = new URLSearchParams();
      body.set("code", code);

      const res = await fetch(API_SAVE, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
      });

      const data = await res.json().catch(() => ({}));
      return { ok: res.ok && data.ok === true, saved: data.saved === true, exists: data.exists === true };
    }

    function updateItemToSaved(li) {
      const icon = li.querySelector(".icon");
      const tag = li.querySelector(".tag");
      const btn = li.querySelector("button.plusBtn");

      icon.textContent = "✅";
      icon.classList.add("ok");
      tag.textContent = "salvo";

      if (btn) {
        btn.textContent = "✓";
        btn.classList.add("saved");
        btn.disabled = true;
      }
    }

    function makeItem(code, isKnown) {
      const li = document.createElement("li");
      li.dataset.code = code;

      const icon = document.createElement("div");
      icon.className = "icon";
      icon.textContent = isKnown ? "✅" : "＋";
      if (isKnown) icon.classList.add("ok");

      const mid = document.createElement("div");
      const codeEl = document.createElement("div");
      codeEl.className = "code";
      codeEl.textContent = code;

      const tagEl = document.createElement("div");
      tagEl.className = "tag";
      tagEl.textContent = isKnown ? "já existe no arquivo" : "novo (não salvo)";

      mid.appendChild(codeEl);
      mid.appendChild(tagEl);

      const right = document.createElement("div");

      if (!isKnown) {
        const btn = document.createElement("button");
        btn.className = "plusBtn";
        btn.textContent = "+";
        btn.title = "Salvar este código";

        btn.addEventListener("click", async () => {
          // proteção extra: se já existe, não salva e já atualiza visual
          if (existing.has(code)) {
            updateItemToSaved(li);
            return;
          }

          btn.disabled = true;
          btn.textContent = "…";

          const result = await saveCode(code);

          // se salvou OU já existia no arquivo, vira ✅ e atualiza sets
          if (result.ok && (result.saved || result.exists)) {
            existing.add(code);
            updateItemToSaved(li);
            pulse(li);
          } else {
            btn.disabled = false;
            btn.textContent = "+";
            tagEl.textContent = "erro ao salvar";
          }
        });

        right.appendChild(btn);
      }

      li.appendChild(icon);
      li.appendChild(mid);
      li.appendChild(right);

      return li;
    }

    function onScanSuccess(decodedText) {
      const now = Date.now();
      if (now - lastScanAt < DEBOUNCE_MS) return;
      lastScanAt = now;

      const code = normalize(decodedText);
      if (!code) return;

      elLast.textContent = code;

      // 1) Se já está na lista da UI, NÃO duplica: só destaca
      if (seenUi.has(code)) {
        pulse(seenUi.get(code));
        // se já existe no arquivo mas o item antigo não estava atualizado, corrige
        if (existing.has(code)) updateItemToSaved(seenUi.get(code));
        return;
      }

      // 2) Se não está na UI, cria item novo
      const isKnown = existing.has(code);
      const li = makeItem(code, isKnown);
      elList.appendChild(li);
      seenUi.set(code, li);

      // 3) pausa/resume para estabilidade
      try {
        if (qr && running) {
          qr.pause(true);
          setTimeout(() => { try { qr.resume(); } catch(_){} }, 350);
        }
      } catch(_) {}
    }

    function onScanFailure(_) {}

    async function pickBackCameraId() {
      const cams = await Html5Qrcode.getCameras();
      if (!cams || !cams.length) throw new Error("Nenhuma câmera encontrada");

      const preferred = cams.find(c =>
        (c.label || "").toLowerCase().match(/back|rear|environment|traseira/)
      ) || cams[cams.length - 1];

      return preferred.id;
    }

    async function start() {
      if (running) return;

      await loadExisting();

      if (!qr) {
        const cfg = {};
        if (window.Html5QrcodeSupportedFormats) {
          cfg.formatsToSupport = [
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF
          ].filter(Boolean);
        }
        qr = new Html5Qrcode("reader", cfg);
      }

      if (!selectedCameraId) selectedCameraId = await pickBackCameraId();

      running = true;

      try {
        await qr.start(
          selectedCameraId,
          { fps: 12, qrbox: { width: 320, height: 140 }, disableFlip: true },
          onScanSuccess,
          onScanFailure
        );
      } catch (e) {
        running = false;
        console.error(e);
        alert("Falha ao iniciar câmera. Use Chrome e permita acesso à câmera.");
      }
    }

    async function stop() {
      if (!qr || !running) return;
      try { await qr.stop(); } catch(_) {}
      running = false;
    }

    async function restart() {
      await stop();
      setTimeout(() => start(), 250);
    }

    document.getElementById("btnStart").addEventListener("click", start);
    document.getElementById("btnStop").addEventListener("click", stop);
    document.getElementById("btnRestart").addEventListener("click", restart);*/
