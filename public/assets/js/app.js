(function () {
  'use strict';

  var form = document.querySelector('.wish-form');
  if (!form) return;

  var OUTPUT_SIZE = 1080;   // final square photo, in pixels
  var JPEG_QUALITY = 0.82;  // compression used when saving the crop

  var maxMb = parseInt(form.getAttribute('data-max-mb'), 10) || 10;
  var input = document.getElementById('photoInput');
  var box = document.getElementById('photoBox');
  var empty = box.querySelector('.photo-empty');
  var previewWrap = box.querySelector('.photo-preview');
  var preview = document.getElementById('photoPreview');
  var removeBtn = document.getElementById('photoRemove');
  var recropBtn = document.getElementById('photoRecrop');
  var uploadText = box.querySelector('.upload-text');
  var message = document.getElementById('message');
  var counter = document.getElementById('counter');
  var submitBtn = form.querySelector('.btn-submit');

  var cropper = document.getElementById('cropper');
  var stage = document.getElementById('cropStage');
  var cropImg = document.getElementById('cropImg');
  var zoom = document.getElementById('cropZoom');
  var cropDone = document.getElementById('cropDone');
  var cropCancel = document.getElementById('cropCancel');

  var sourceUrl = null;   // object URL of the original photo picked from the gallery
  var previewUrl = null;  // object URL of the cropped result
  var cropped = null;     // Blob (square JPEG) that will be uploaded
  var lastCrop = null;    // remembers position/zoom so "Adjust crop" reopens where it was

  // ---------- character counter ----------
  function updateCount() {
    counter.firstElementChild.textContent = Array.from(message.value).length;
  }
  message.addEventListener('input', updateCount);
  updateCount();

  // ---------- cropper ----------
  var st = { iw: 0, ih: 0, V: 0, base: 1, z: 1, x: 0, y: 0 };
  var pointers = new Map();
  var gesture = null;

  function scale() { return st.base * st.z; }

  function clamp() {
    var s = scale();
    var w = st.iw * s, h = st.ih * s;
    st.x = Math.min(0, Math.max(st.V - w, st.x));
    st.y = Math.min(0, Math.max(st.V - h, st.y));
  }

  function render() {
    clamp();
    var s = scale();
    cropImg.style.width = st.iw + 'px';
    cropImg.style.height = st.ih + 'px';
    cropImg.style.transform = 'translate(' + st.x + 'px,' + st.y + 'px) scale(' + s + ')';
  }

  // zoom to z keeping the stage point (cx, cy) fixed
  function zoomTo(z, cx, cy) {
    z = Math.min(4, Math.max(1, z));
    if (cx === undefined) { cx = st.V / 2; cy = st.V / 2; }
    var old = scale();
    st.z = z;
    var k = scale() / old;
    st.x = cx - (cx - st.x) * k;
    st.y = cy - (cy - st.y) * k;
    zoom.value = z;
    render();
  }

  function openCropper() {
    cropper.hidden = false;
    document.body.classList.add('no-scroll');
    st.V = stage.clientWidth;
    st.iw = cropImg.naturalWidth;
    st.ih = cropImg.naturalHeight;
    st.base = st.V / Math.min(st.iw, st.ih); // smallest scale that still fills the square
    if (lastCrop) {
      st.z = lastCrop.z;
      st.x = lastCrop.fx * st.V;
      st.y = lastCrop.fy * st.V;
    } else {
      st.z = 1;
      st.x = (st.V - st.iw * st.base) / 2;
      st.y = (st.V - st.ih * st.base) / 2;
    }
    zoom.value = st.z;
    render();
    cropDone.focus();
  }

  function closeCropper() {
    cropper.hidden = true;
    document.body.classList.remove('no-scroll');
    pointers.clear();
    gesture = null;
  }

  function loadSource(file) {
    if (sourceUrl) URL.revokeObjectURL(sourceUrl);
    sourceUrl = URL.createObjectURL(file);
    lastCrop = null;
    cropImg.onload = openCropper;
    cropImg.onerror = function () {
      alert('Sorry, this photo format is not supported. Please choose a JPG or PNG photo.');
      input.value = '';
    };
    cropImg.src = sourceUrl;
  }

  stage.addEventListener('pointerdown', function (e) {
    stage.setPointerCapture(e.pointerId);
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    startGesture();
  });
  stage.addEventListener('pointermove', function (e) {
    if (!pointers.has(e.pointerId)) return;
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    var pts = Array.from(pointers.values());
    if (pts.length === 1 && gesture) {
      st.x = gesture.x + (pts[0].x - gesture.px);
      st.y = gesture.y + (pts[0].y - gesture.py);
      render();
    } else if (pts.length >= 2 && gesture && gesture.dist) {
      var r = stage.getBoundingClientRect();
      var d = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
      var cx = (pts[0].x + pts[1].x) / 2 - r.left;
      var cy = (pts[0].y + pts[1].y) / 2 - r.top;
      zoomTo(gesture.z * d / gesture.dist, cx, cy);
    }
  });
  function endPointer(e) { pointers.delete(e.pointerId); startGesture(); }
  stage.addEventListener('pointerup', endPointer);
  stage.addEventListener('pointercancel', endPointer);

  function startGesture() {
    var pts = Array.from(pointers.values());
    if (pts.length === 1) gesture = { px: pts[0].x, py: pts[0].y, x: st.x, y: st.y };
    else if (pts.length >= 2) gesture = { z: st.z, dist: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y) };
    else gesture = null;
  }

  stage.addEventListener('wheel', function (e) {
    e.preventDefault();
    var r = stage.getBoundingClientRect();
    zoomTo(st.z * (e.deltaY < 0 ? 1.08 : 1 / 1.08), e.clientX - r.left, e.clientY - r.top);
  }, { passive: false });

  zoom.addEventListener('input', function () { zoomTo(parseFloat(zoom.value)); });

  cropCancel.addEventListener('click', function () {
    closeCropper();
    if (!cropped) clearPhoto();
  });
  cropper.addEventListener('keydown', function (e) { if (e.key === 'Escape') cropCancel.click(); });

  cropDone.addEventListener('click', function () {
    var s = scale();
    var side = st.V / s;                 // crop size in original-image pixels
    var sx = -st.x / s, sy = -st.y / s;
    var out = Math.round(Math.min(OUTPUT_SIZE, side));
    var c = document.createElement('canvas');
    c.width = c.height = out;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, out, out);
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(cropImg, sx, sy, side, side, 0, 0, out, out);
    cropDone.disabled = true;
    c.toBlob(function (blob) {
      cropDone.disabled = false;
      if (!blob) { alert('Could not process this photo. Please try another one.'); return; }
      cropped = blob;
      lastCrop = { z: st.z, fx: st.x / st.V, fy: st.y / st.V };
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(blob);
      preview.src = previewUrl;
      empty.hidden = true;
      previewWrap.hidden = false;
      box.classList.add('has-photo');
      uploadText.textContent = 'Change Photo';
      closeCropper();
    }, 'image/jpeg', JPEG_QUALITY);
  });

  // ---------- photo selection ----------
  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    if (!file) return;
    if (file.type && !/^image\//.test(file.type)) {
      alert('Please choose a photo.');
      input.value = '';
      return;
    }
    loadSource(file);
  });

  recropBtn.addEventListener('click', function () { if (sourceUrl) openCropper(); });
  preview.addEventListener('click', function () { if (sourceUrl) openCropper(); });

  function clearPhoto() {
    input.value = '';
    cropped = null;
    lastCrop = null;
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    if (sourceUrl) URL.revokeObjectURL(sourceUrl);
    previewUrl = sourceUrl = null;
    preview.removeAttribute('src');
    previewWrap.hidden = true;
    empty.hidden = false;
    box.classList.remove('has-photo');
    uploadText.textContent = 'Upload Photo';
  }
  removeBtn.addEventListener('click', clearPhoto);

  // ---------- submit ----------
  function resetSubmit() {
    submitBtn.disabled = false;
    submitBtn.classList.remove('is-loading');
    submitBtn.querySelector('.btn-text').textContent = 'Submit Your Wishes';
  }

  form.addEventListener('submit', function (ev) {
    var name = form.querySelector('#name');
    if (!name.value.trim()) { ev.preventDefault(); name.focus(); name.classList.add('invalid'); return; }
    if (!message.value.trim()) { ev.preventDefault(); message.focus(); message.classList.add('invalid'); return; }
    if (!window.fetch || !window.FormData) return; // very old browser: normal post, server crops to square

    ev.preventDefault();
    if (cropped && cropped.size > maxMb * 1024 * 1024) {
      alert('That photo is too large. Please choose another one.');
      return;
    }
    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');
    submitBtn.querySelector('.btn-text').textContent = 'Sending…';

    var data = new FormData(form);
    data.delete('photo'); // never send the original full-size file
    if (cropped) data.set('photo', cropped, 'photo.jpg');
    fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (res) {
        if (res.redirected) { window.location.href = res.url; return; }
        return res.text().then(function (html) {
          document.open(); document.write(html); document.close();
        });
      })
      .catch(function () {
        resetSubmit();
        alert('Could not send your wishes — please check your connection and try again.');
      });
  });

  form.querySelectorAll('input, textarea').forEach(function (el) {
    el.addEventListener('input', function () { el.classList.remove('invalid'); });
  });
})();
