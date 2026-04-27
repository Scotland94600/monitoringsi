const canvas = document.getElementById('widgetCanvas');
const tools = document.querySelectorAll('.tool');
const saveBtn = document.getElementById('saveBtn');
const message = document.getElementById('message');

let draggedEl = null;

function buildWidget(type, title = 'Nouveau widget', data = []) {
  const wrapper = document.createElement('div');
  wrapper.className = 'widget';
  wrapper.setAttribute('draggable', 'true');
  wrapper.dataset.widgetType = type;
  wrapper.innerHTML = `
    <label>Titre</label>
    <input class="widget-title" value="${title}">
    <label>Type</label>
    <select class="widget-type">
      <option value="status_list" ${type === 'status_list' ? 'selected' : ''}>status_list</option>
      <option value="metric_bars" ${type === 'metric_bars' ? 'selected' : ''}>metric_bars</option>
      <option value="custom" ${type === 'custom' ? 'selected' : ''}>custom</option>
    </select>
    <label>Data JSON</label>
    <textarea class="widget-data" rows="4">${JSON.stringify(data, null, 2)}</textarea>
    <button class="remove-widget" type="button">Supprimer</button>
  `;
  addWidgetEvents(wrapper);
  return wrapper;
}

function addWidgetEvents(el) {
  el.addEventListener('dragstart', () => {
    draggedEl = el;
  });
  el.addEventListener('dragover', (e) => e.preventDefault());
  el.addEventListener('drop', (e) => {
    e.preventDefault();
    if (!draggedEl || draggedEl === el) return;
    canvas.insertBefore(draggedEl, el);
  });

  el.querySelector('.remove-widget').addEventListener('click', () => el.remove());
}

tools.forEach((tool) => {
  tool.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('new-widget-type', tool.dataset.widgetType);
  });
});

canvas.addEventListener('dragover', (e) => e.preventDefault());
canvas.addEventListener('drop', (e) => {
  e.preventDefault();
  const type = e.dataTransfer.getData('new-widget-type');
  if (type) {
    canvas.appendChild(buildWidget(type));
  }
});

document.querySelectorAll('.widget').forEach(addWidgetEvents);

saveBtn.addEventListener('click', async () => {
  const widgets = [...canvas.querySelectorAll('.widget')].map((el) => {
    let data = [];
    try {
      data = JSON.parse(el.querySelector('.widget-data').value || '[]');
    } catch (e) {
      data = [{ error: 'JSON invalide' }];
    }

    return {
      title: el.querySelector('.widget-title').value,
      widget_type: el.querySelector('.widget-type').value,
      data,
    };
  });

  const payload = {
    csrf: window.MONITORING_ADMIN.csrf,
    name: document.getElementById('dashboardName').value,
    widgets,
  };

  const res = await fetch('save_dashboard.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  const body = await res.json();
  message.textContent = body.ok ? 'Sauvegardé ✅' : `Erreur: ${body.error}`;
});
