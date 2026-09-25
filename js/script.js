 // ---- Filter ----
  const filterButtons = document.querySelectorAll('.gallery-filters button');
  const galleryItems = document.querySelectorAll('.gallery-item');

  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      filterButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;

      galleryItems.forEach(item => {
        const match = filter === 'all' || item.dataset.category === filter;
        item.classList.toggle('d-none-item', !match);
      });
    });
  });

  // ---- Lightbox ----
  const lightboxModalEl = document.getElementById('lightboxModal');
  const lightboxModal = new bootstrap.Modal(lightboxModalEl);
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxCaption = document.getElementById('lightboxCaption');
  let currentIndex = 0;

  function visibleItems() {
    return Array.from(galleryItems).filter(item => !item.classList.contains('d-none-item'));
  }

  function openLightbox(index) {
    const items = visibleItems();
    if (!items.length) return;
    currentIndex = (index + items.length) % items.length;
    const item = items[currentIndex];
    const img = item.querySelector('img');
    const caption = item.querySelector('.gallery-overlay span').textContent;
    lightboxImg.src = img.src;
    lightboxImg.alt = img.alt;
    lightboxCaption.textContent = caption;
    lightboxModal.show();
  }

  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const items = visibleItems();
      const index = items.indexOf(item);
      openLightbox(index);
    });
  });

  document.getElementById('lightboxPrev').addEventListener('click', () => openLightbox(currentIndex - 1));
  document.getElementById('lightboxNext').addEventListener('click', () => openLightbox(currentIndex + 1));

  document.addEventListener('keydown', (e) => {
    if (!lightboxModalEl.classList.contains('show')) return;
    if (e.key === 'ArrowLeft') openLightbox(currentIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(currentIndex + 1);
  });

// reports

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 15, font: { size: 12 } }
            }
        }
    };

 