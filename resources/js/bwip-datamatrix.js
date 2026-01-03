import bwipjs from 'bwip-js';

export function renderDataMatrix(container, text = '123456789', opts = {}) {
  const { scale = 4, padding = 0, width = '48mm', height = '48mm' } = opts;
  try {
    const svg = bwipjs.toSVG({
      bcid: 'datamatrix',
      text: String(text),
      scale,
      padding,
    });
    container.innerHTML = svg;
    const svgEl = container.querySelector('svg');
    if (svgEl) {
      svgEl.setAttribute('width', width);
      svgEl.setAttribute('height', height);
      svgEl.style.display = 'block';
    }
  } catch (err) {
    // Keep errors visible in console for debugging
    // but do not throw to avoid breaking page render
    // when bwip-js can't render.
    // eslint-disable-next-line no-console
    console.error('bwip-js render error', err);
  }
}

export function renderQRCode(container, text = 'https://example.org', opts = {}) {
  const { scale = 4, padding = 0, width = '48mm', height = '48mm' } = opts;
  try {
    const svg = bwipjs.toSVG({
      bcid: 'qrcode',
      text: String(text),
      scale,
      padding,
    });
    container.innerHTML = svg;
    const svgEl = container.querySelector('svg');
    if (svgEl) {
      svgEl.setAttribute('width', width);
      svgEl.setAttribute('height', height);
      svgEl.style.display = 'block';
    }
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('bwip-js render error', err);
  }
}

// Expose for simple usage from inline scripts in Blade.
window.renderDataMatrix = renderDataMatrix;

function initFromDataAttrs() {
  const nodes = document.querySelectorAll('[data-bwip-datamatrix], [data-bwip-qr]');
  nodes.forEach((el) => {
    const scale = parseInt(el.getAttribute('data-bwip-scale')) || 4;
    const padding = parseInt(el.getAttribute('data-bwip-padding')) || 0;
    const width = el.getAttribute('data-bwip-width') || '48mm';
    const height = el.getAttribute('data-bwip-height') || '48mm';

    if (el.hasAttribute('data-bwip-datamatrix')) {
      const text = el.getAttribute('data-bwip-datamatrix') || el.textContent.trim() || '123456789';
      renderDataMatrix(el, text, { scale, padding, width, height });
      return;
    }

    if (el.hasAttribute('data-bwip-qr')) {
      const text = el.getAttribute('data-bwip-qr') || el.textContent.trim() || 'https://example.org';
      renderQRCode(el, text, { scale, padding, width, height });
      return;
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initFromDataAttrs);
} else {
  initFromDataAttrs();
}
