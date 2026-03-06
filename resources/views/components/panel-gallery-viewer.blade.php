@props([
    'modalId' => 'panel-gallery-viewer',
    'openSelector' => '[data-gallery-open]',
    'titleDefault' => 'Gallery Viewer',
])

@php
    $titleId = $modalId . '-title';
    $mediaId = $modalId . '-media';
    $metaId = $modalId . '-meta';
    $prevId = $modalId . '-prev';
    $nextId = $modalId . '-next';
@endphp

<div id="{{ $modalId }}" class="panel-modal" hidden>
  <div class="panel-modal-backdrop" data-gallery-close></div>
  <div class="panel-modal-dialog">
    <div class="panel-modal-head">
      <h3 class="panel-modal-title" id="{{ $titleId }}">{{ $titleDefault }}</h3>
      <button class="panel-modal-close" type="button" data-gallery-close aria-label="Close gallery">×</button>
    </div>

    <div class="panel-modal-body">
      <div id="{{ $mediaId }}" class="panel-card"></div>
    </div>

    <div class="panel-modal-foot" style="justify-content: space-between; align-items: center;">
      <button class="panel-btn" type="button" id="{{ $prevId }}">Previous</button>
      <span class="panel-muted" id="{{ $metaId }}">1 / 1</span>
      <button class="panel-btn" type="button" id="{{ $nextId }}">Next</button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById(@json($modalId));
    const mediaWrap = document.getElementById(@json($mediaId));
    const title = document.getElementById(@json($titleId));
    const meta = document.getElementById(@json($metaId));
    const prevBtn = document.getElementById(@json($prevId));
    const nextBtn = document.getElementById(@json($nextId));

    if (!modal || !mediaWrap || !title || !meta || !prevBtn || !nextBtn) {
      return;
    }

    let currentItems = [];
    let currentIndex = 0;

    const closeViewer = function () {
      modal.hidden = true;
      document.body.classList.remove('panel-modal-open');
      mediaWrap.innerHTML = '';
      currentItems = [];
      currentIndex = 0;
    };

    const render = function () {
      if (!currentItems.length) {
        mediaWrap.innerHTML = '<p class="panel-muted">No media files.</p>';
        meta.textContent = '0 / 0';
        return;
      }

      const item = currentItems[currentIndex];
      const safeName = item.name || 'Media file';
      title.textContent = safeName;
      meta.textContent = (currentIndex + 1) + ' / ' + currentItems.length;

      if (item.type === 'video') {
        mediaWrap.innerHTML = '<video controls style="width:100%; max-height:70vh;"><source src="' + item.url + '" type="' + (item.mime || 'video/mp4') + '"></video>';
      } else {
        mediaWrap.innerHTML = '<img src="' + item.url + '" alt="' + safeName.replace(/"/g, '&quot;') + '" style="width:100%; max-height:70vh; object-fit:contain;">';
      }
    };

    prevBtn.addEventListener('click', function () {
      if (!currentItems.length) return;
      currentIndex = (currentIndex - 1 + currentItems.length) % currentItems.length;
      render();
    });

    nextBtn.addEventListener('click', function () {
      if (!currentItems.length) return;
      currentIndex = (currentIndex + 1) % currentItems.length;
      render();
    });

    document.querySelectorAll(@json($openSelector)).forEach(function (btn) {
      btn.addEventListener('click', function () {
        const raw = btn.getAttribute('data-gallery-items') || '[]';
        try {
          const parsed = JSON.parse(raw);
          if (!Array.isArray(parsed) || parsed.length === 0) {
            return;
          }

          currentItems = parsed;
          currentIndex = 0;
          modal.hidden = false;
          document.body.classList.add('panel-modal-open');
          render();
        } catch (error) {
          console.error('Gallery data parse error', error);
        }
      });
    });

    modal.querySelectorAll('[data-gallery-close]').forEach(function (btn) {
      btn.addEventListener('click', closeViewer);
    });

    document.addEventListener('keydown', function (event) {
      if (modal.hidden) return;
      if (event.key === 'Escape') {
        closeViewer();
      } else if (event.key === 'ArrowRight') {
        nextBtn.click();
      } else if (event.key === 'ArrowLeft') {
        prevBtn.click();
      }
    });
  })();
</script>
