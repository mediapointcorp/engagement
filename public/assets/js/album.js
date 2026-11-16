(function () {
  'use strict';

  var album = document.getElementById('album');
  var pool = document.getElementById('cardPool');
  var tpl = document.getElementById('pageTpl');
  var auto = album.getAttribute('data-auto') === '1';
  var fileName = album.getAttribute('data-file') || 'Engagement-Wishes.pdf';
  var downloadBtn = document.getElementById('downloadBtn');
  var againBtn = document.getElementById('againBtn');
  var statusEl = document.getElementById('status');
  var statusText = document.getElementById('statusText');
  var statusFill = document.getElementById('statusFill');
  var statusDone = document.getElementById('statusDone');
  var statusTitle = statusEl.querySelector('.status-title');
  var busy = false;

  function setStatus(text, pct) {
    statusText.textContent = text;
    if (pct !== undefined) statusFill.style.width = Math.round(pct) + '%';
  }

  function waitForImages() {
    var imgs = Array.prototype.slice.call(document.images);
    return Promise.all(imgs.map(function (img) {
      if (img.complete) return Promise.resolve();
      return new Promise(function (res) { img.onload = img.onerror = res; });
    }));
  }

  // ---- 1. Flow the wish cards into A4 pages ----
  function paginate() {
    var cards = Array.prototype.slice.call(pool.children);
    var back = album.querySelector('.sheet.back');
    var page = null, box = null, pages = [];

    function newPage() {
      page = tpl.content.firstElementChild.cloneNode(true);
      album.insertBefore(page, back);
      box = page.querySelector('.cards');
      pages.push(page);
    }

    cards.forEach(function (card) {
      if (!page) newPage();
      box.appendChild(card);
      if (box.scrollHeight > box.clientHeight + 1 && box.children.length > 1) {
        box.removeChild(card);
        newPage();
        box.appendChild(card);
      }
      // A single very long wish: shrink its text until it fits on the page.
      var msg = card.querySelector('.msg');
      var size = parseFloat(getComputedStyle(msg).fontSize);
      while (box.scrollHeight > box.clientHeight + 1 && size > 10) {
        size -= 0.5;
        msg.style.fontSize = size + 'px';
      }
    });

    pages.forEach(function (p, i) {
      p.querySelector('.num').textContent = (i + 2); // page 1 is the cover
      if (i === pages.length - 1) {
        p.querySelector('.inner-foot').classList.add('is-last');
        p.querySelector('.best').hidden = false;
      }
    });
    pool.remove();
    var total = album.querySelectorAll('.sheet').length;
    document.getElementById('pageCount').textContent = total;
    return total;
  }

  // html2canvas sometimes skips inline SVG ornaments, so swap each one for an
  // <img> of the same SVG (colours resolved, exact size) before rendering.
  function svgsToImages() {
    var svgs = Array.prototype.slice.call(album.querySelectorAll('svg'));
    return Promise.all(svgs.map(function (svg) {
      var r = svg.getBoundingClientRect();
      if (!r.width || !r.height) return Promise.resolve();
      var zoom = parseFloat(getComputedStyle(album).zoom) || 1;
      var w = r.width / zoom, h = r.height / zoom;
      var color = getComputedStyle(svg).color;
      var clone = svg.cloneNode(true);
      clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
      clone.setAttribute('width', w);
      clone.setAttribute('height', h);
      var markup = new XMLSerializer().serializeToString(clone).replace(/currentColor/g, color);
      var img = new Image();
      img.width = w; img.height = h;
      img.className = (svg.getAttribute('class') || '') + ' svg-img';
      img.style.width = w + 'px';
      img.style.height = h + 'px';
      img.alt = '';
      return new Promise(function (res) {
        img.onload = img.onerror = res;
        img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(markup);
        svg.parentNode.replaceChild(img, svg);
      });
    }));
  }

  // ---- 2. Render every page to an image and build the PDF ----
  function buildPdf() {
    if (busy) return Promise.resolve();
    busy = true;
    downloadBtn.disabled = true;
    var wasPreview = document.body.classList.contains('mode-preview');
    album.style.zoom = 1; // capture at full size
    statusEl.style.display = 'flex';
    statusDone.hidden = true;
    statusTitle.textContent = 'Preparing your album…';

    var sheets = Array.prototype.slice.call(album.querySelectorAll('.sheet'));
    var jsPDF = window.jspdf.jsPDF;
    var pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true });
    var W = 210, H = 297;
    var scale = Math.min(2, Math.max(1.5, window.devicePixelRatio || 1));

    var chain = Promise.resolve();
    sheets.forEach(function (sheet, i) {
      chain = chain.then(function () {
        setStatus('Creating page ' + (i + 1) + ' of ' + sheets.length, (i / sheets.length) * 100);
        return html2canvas(sheet, {
          scale: scale,
          useCORS: true,
          backgroundColor: '#fbf3ec',
          logging: false,
          windowWidth: 1000,
          scrollX: 0,
          scrollY: -window.scrollY
        }).then(function (canvas) {
          if (i > 0) pdf.addPage('a4', 'portrait');
          pdf.addImage(canvas.toDataURL('image/jpeg', 0.9), 'JPEG', 0, 0, W, H, undefined, 'FAST');
          // keep links clickable inside the PDF (e.g. "Powered by Playtown")
          var sr = sheet.getBoundingClientRect();
          sheet.querySelectorAll('a[data-pdf-link]').forEach(function (a) {
            var r = a.getBoundingClientRect();
            pdf.link((r.left - sr.left) / sr.width * W, (r.top - sr.top) / sr.height * H,
                     r.width / sr.width * W, r.height / sr.height * H, { url: a.href });
          });
          canvas.width = canvas.height = 0; // free memory
        });
      });
    });

    return chain.then(function () {
      setStatus('Saving PDF…', 100);
      pdf.setProperties({ title: fileName.replace(/\.pdf$/, '').replace(/-/g, ' '), creator: 'Playtown' });
      pdf.save(fileName);
      statusTitle.textContent = 'Your album is ready!';
      setStatus('The PDF has been downloaded (' + sheets.length + ' pages).', 100);
      statusDone.hidden = false;
      if (wasPreview) {
        setTimeout(function () { statusEl.style.display = ''; }, 1600);
      }
    }).catch(function (err) {
      console.error(err);
      statusTitle.textContent = 'Something went wrong';
      setStatus('Could not create the PDF. Please reload the page and try again.');
      statusDone.hidden = false;
    }).then(function () {
      album.style.zoom = '';
      busy = false;
      downloadBtn.disabled = false;
    });
  }

  function fitPreview() {
    var z = Math.min(1, (window.innerWidth - 24) / 794);
    document.documentElement.style.setProperty('--preview-zoom', z);
  }

  downloadBtn.addEventListener('click', buildPdf);
  againBtn.addEventListener('click', buildPdf);

  var fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
  Promise.all([fontsReady, waitForImages()]).then(function () {
    paginate();
    return svgsToImages();
  }).then(function () {
    if (auto) {
      buildPdf();
    } else {
      fitPreview();
      window.addEventListener('resize', fitPreview);
      downloadBtn.disabled = false;
    }
  });
})();
