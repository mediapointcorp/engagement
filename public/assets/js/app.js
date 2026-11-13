(function () {
  'use strict';

  var form = document.querySelector('.wish-form');
  if (!form) return;

  var maxMb = parseInt(form.getAttribute('data-max-mb'), 10) || 10;
  var inputs = form.querySelectorAll('[data-photo-input]');
  var box = document.getElementById('photoBox');
  var empty = box.querySelector('.photo-empty');
  var previewWrap = box.querySelector('.photo-preview');
  var preview = document.getElementById('photoPreview');
  var removeBtn = document.getElementById('photoRemove');
  var message = document.getElementById('message');
  var counter = document.getElementById('counter');
  var submitBtn = form.querySelector('.btn-submit');
  var previewUrl = null;
  var resized = null; // Blob of the downscaled photo, if we made one

  // ---- character counter ----
  function updateCount() {
    var n = Array.from(message.value).length;
    counter.firstElementChild.textContent = n;
  }
  message.addEventListener('input', updateCount);
  updateCount();

  // ---- photo selection ----
  function showPreview(file) {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(file);
    preview.src = previewUrl;
    empty.hidden = true;
    previewWrap.hidden = false;
    box.classList.add('has-photo');
  }

  function clearPhoto() {
    inputs.forEach(function (i) { i.value = ''; });
    resized = null;
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    preview.removeAttribute('src');
    previewWrap.hidden = true;
    empty.hidden = false;
    box.classList.remove('has-photo');
  }

  // Downscale big phone photos before upload so they send quickly on mobile data.
  function downscale(file) {
    return new Promise(function (resolve) {
      if (!window.createImageBitmap || !/^image\/(jpeg|png|webp)$/.test(file.type)) return resolve(null);
      createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var max = 1600;
        var scale = Math.min(1, max / Math.max(bmp.width, bmp.height));
        if (scale === 1 && file.size < 1.5 * 1024 * 1024) return resolve(null);
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * scale);
        c.height = Math.round(bmp.height * scale);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (b) { resolve(b); }, 'image/jpeg', 0.86);
      }).catch(function () { resolve(null); });
    });
  }

  inputs.forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      // keep only the most recently used input populated
      inputs.forEach(function (other) { if (other !== input) other.value = ''; });
      resized = null;
      if (!file) return clearPhoto();
      if (!/^image\//.test(file.type) && !/\.(heic|heif)$/i.test(file.name)) {
        alert('Please choose a photo (JPG, PNG or WEBP).');
        return clearPhoto();
      }
      showPreview(file);
      downscale(file).then(function (b) { resized = b; });
    });
  });

  removeBtn.addEventListener('click', clearPhoto);

  // ---- submit ----
  form.addEventListener('submit', function (ev) {
    var name = form.querySelector('#name');
    if (!name.value.trim()) { ev.preventDefault(); name.focus(); name.classList.add('invalid'); return; }
    if (!message.value.trim()) { ev.preventDefault(); message.focus(); message.classList.add('invalid'); return; }

    var chosen = Array.prototype.find.call(inputs, function (i) { return i.files && i.files[0]; });
    var original = chosen ? chosen.files[0] : null;
    var blob = resized || original;
    if (blob && blob.size > maxMb * 1024 * 1024) {
      ev.preventDefault();
      alert('That photo is too large. Please choose one under ' + maxMb + ' MB.');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.classList.add('is-loading');
    submitBtn.querySelector('.btn-text').textContent = 'Sending…';

    // If we produced a smaller photo, send it with fetch; otherwise let the browser post normally.
    if (!resized || !window.fetch || !window.FormData) return;
    ev.preventDefault();
    var data = new FormData(form);
    data.delete('photo_camera');
    data.set('photo', resized, 'photo.jpg');
    fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (res) {
        if (res.redirected) { window.location.href = res.url; return; }
        return res.text().then(function (html) {
          document.open(); document.write(html); document.close();
        });
      })
      .catch(function () {
        submitBtn.disabled = false;
        submitBtn.classList.remove('is-loading');
        submitBtn.querySelector('.btn-text').textContent = 'Submit Your Wishes';
        alert('Could not send your wishes — please check your connection and try again.');
      });
  });

  form.querySelectorAll('input, textarea').forEach(function (el) {
    el.addEventListener('input', function () { el.classList.remove('invalid'); });
  });
})();
